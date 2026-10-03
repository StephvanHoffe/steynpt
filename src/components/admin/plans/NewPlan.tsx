import { asc, eq } from "drizzle-orm";
import { AlertTriangle, CopyPlus, FilePlus2, Info, Sparkles } from "lucide-react";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { createManualPlanAction, generatePlanAction } from "@/lib/actions/plans";
import { db, intakes, type PlanType, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { STAGES, relativeDay } from "@/lib/plans/pipeline";
import { loadPlanPipeline } from "@/lib/plans/pipeline-server";
import { PLAN_SECTION, planHref } from "@/lib/plans/sections";
import { getOnlinePlan } from "@/lib/site";
import { MemberPicker, type PickerGroup } from "./MemberPicker";
import { formatPlanDayLong, StageBadge } from "./stage";

const sinceFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", timeZone: "Europe/Amsterdam" });

function Option({ icon: Icon, title, children }: { icon: typeof Sparkles; title: string; children: React.ReactNode }) {
  return (
    <section className="card p-5 sm:p-6">
      <h2 className="flex items-center gap-2 text-lg font-semibold">
        <Icon className="size-5 text-accent" aria-hidden="true" /> {title}
      </h2>
      <div className="mt-3">{children}</div>
    </section>
  );
}

/** Nieuw schema maken: klant kiezen, dan AI-concept, verder met het huidige schema of leeg beginnen. */
export async function NewPlan({ type, searchParams }: { type: PlanType; searchParams: Record<string, string | string[] | undefined> }) {
  const section = PLAN_SECTION[type];
  const lid = typeof searchParams.lid === "string" ? searchParams.lid : undefined;

  const [members, pipeline] = await Promise.all([
    db
      .select({ id: users.id, firstName: users.firstName, lastName: users.lastName })
      .from(users)
      .where(eq(users.role, "member"))
      .orderBy(asc(users.firstName), asc(users.lastName)),
    loadPlanPipeline(),
  ]);
  const member = lid ? members.find((m) => m.id === lid) : undefined;

  // Wie op een schema wacht staat bovenaan de keuzelijst.
  const stageBy = new Map(pipeline.rows[type].map((r) => [r.member.id, r.stage]));
  const name = (m: (typeof members)[number]) => `${m.firstName} ${m.lastName}`;
  const groupOf = (id: string) => {
    const stage = stageBy.get(id);
    return stage ? STAGES[stage].group : null;
  };
  const inGroup = (group: "wacht" | "binnenkort") =>
    members.filter((m) => groupOf(m.id) === group).map((m) => ({ id: m.id, name: name(m), note: STAGES[stageBy.get(m.id)!].label.toLowerCase() }));
  const groups: PickerGroup[] = [
    { label: "Wacht op nieuw schema", members: inGroup("wacht") },
    { label: "Komende week", members: inGroup("binnenkort") },
    { label: "Overige klanten", members: members.filter((m) => groupOf(m.id) !== "wacht" && groupOf(m.id) !== "binnenkort").map((m) => ({ id: m.id, name: name(m) })) },
  ];

  let details: React.ReactNode = null;
  if (member) {
    const [{ rows, today }, [intakeRow], [full]] = await Promise.all([
      loadPlanPipeline(member.id),
      db.select().from(intakes).where(eq(intakes.userId, member.id)),
      db.select({ plan: users.plan, coachingStatus: users.coachingStatus }).from(users).where(eq(users.id, member.id)),
    ]);
    const row = rows[type][0];
    const intake = intakeSchema.safeParse(intakeRow?.data);
    const ai = aiConfigured();
    const hidden = (
      <>
        <input type="hidden" name="userId" value={member.id} />
        <input type="hidden" name="type" value={type} />
      </>
    );

    details = (
      <div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div className="grid min-w-0 grid-cols-1 content-start gap-5">
          {/* Waar staat deze klant? */}
          <section className="card p-5 sm:p-6" aria-label="Huidige situatie">
            <div className="flex flex-wrap items-center gap-x-3 gap-y-2">
              <Link href={`/admin/leden/${member.id}`} className="text-lg font-semibold hover:underline">
                {name(member)}
              </Link>
              {row && <StageBadge stage={row.stage} coachingStatus={full?.coachingStatus} />}
              {getOnlinePlan(full?.plan) && <span className="text-sm text-muted">Online {getOnlinePlan(full?.plan)!.name}</span>}
            </div>
            <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt className="text-muted">Huidig {section.one}</dt>
                <dd className="font-medium">
                  {row?.current ? (
                    <Link href={planHref(type, row.current.id)} className="underline decoration-accent underline-offset-4">
                      {row.current.title || "Bekijken"}
                    </Link>
                  ) : (
                    "Nog geen schema"
                  )}
                  {row?.current && <span className="block font-normal text-muted">gepubliceerd {sinceFmt.format(row.current.publishedAt)}</span>}
                </dd>
              </div>
              <div>
                <dt className="text-muted">Toe aan nieuw schema</dt>
                <dd className="font-medium">
                  {row?.dueOn ? (
                    <>
                      <span className="first-letter:uppercase">{formatPlanDayLong(row.dueOn)}</span>
                      <span className={`block font-normal ${row.dueOn <= today && row.stage !== "pauze" ? "text-danger" : "text-muted"}`}>{relativeDay(today, row.dueOn)}</span>
                    </>
                  ) : (
                    "Zo snel mogelijk"
                  )}
                </dd>
              </div>
            </dl>
            {row?.open && (
              <p className="mt-4 flex gap-2 rounded-lg bg-accent-tint p-3 text-sm">
                <AlertTriangle className="size-4 shrink-0 text-accent" aria-hidden="true" />
                <span>
                  Er staat al een concept klaar ({STAGES[row.stage].label.toLowerCase()}).{" "}
                  <Link href={planHref(type, row.open.id)} className="font-semibold underline">
                    Concept openen
                  </Link>
                  . Een nieuw concept vervangt het bestaande.
                </span>
              </p>
            )}
          </section>

          <Option icon={Sparkles} title="Laat de AI een concept maken">
            {ai && intake.success ? (
              <form action={generatePlanAction} className="grid gap-3">
                {hidden}
                <p className="text-sm text-muted">Op basis van de intake. Je controleert het concept altijd eerst; de klant ziet niets tot je publiceert.</p>
                <label className="block">
                  <span className="label">Instructie voor de AI (optioneel)</span>
                  <textarea
                    name="instruction"
                    rows={3}
                    maxLength={1500}
                    className="input min-h-0 py-2 text-sm"
                    placeholder={type === "training" ? "Bijv. 'volgende fase: meer kracht, 4 dagen, geen squats vanwege de knie'" : "Bijv. 'calorieën 100 kcal omlaag, meer warme lunches'"}
                  />
                </label>
                <button type="submit" className="btn btn-primary justify-self-start">
                  <Sparkles className="size-4" aria-hidden="true" /> Concept laten maken
                </button>
              </form>
            ) : (
              <p className="flex gap-2 text-sm text-muted">
                <Info className="size-4 shrink-0" aria-hidden="true" />
                {!intake.success
                  ? "De klant heeft de intake nog niet ingevuld. Die heeft de AI nodig; je kunt het schema wel zelf opstellen."
                  : "AI staat uit: stel ANTHROPIC_API_KEY in om concepten te laten maken. Je kunt het schema wel zelf opstellen."}
              </p>
            )}
          </Option>

          {row?.current && (
            <Option icon={CopyPlus} title="Verder met het huidige schema">
              <form action={createManualPlanAction} className="grid gap-3">
                {hidden}
                <input type="hidden" name="start" value="huidig" />
                <p className="text-sm text-muted">Maakt een kopie van het huidige {section.one} als concept. Pas aan wat er verandert en publiceer opnieuw.</p>
                <button type="submit" className="btn btn-outline justify-self-start">
                  <CopyPlus className="size-4" aria-hidden="true" /> Kopie als concept
                </button>
              </form>
            </Option>
          )}

          <Option icon={FilePlus2} title="Zelf een leeg schema opstellen">
            <form action={createManualPlanAction} className="grid gap-3">
              {hidden}
              <input type="hidden" name="start" value="leeg" />
              <p className="text-sm text-muted">
                {type === "training" ? "Met het aantal trainingsdagen uit de intake al klaargezet." : "Met de richtwaarden uit de intake al ingevuld."}
              </p>
              <button type="submit" className="btn btn-outline justify-self-start">
                <FilePlus2 className="size-4" aria-hidden="true" /> Leeg schema starten
              </button>
            </form>
          </Option>
        </div>

        <aside className="card self-start p-6 xl:sticky xl:top-20" aria-labelledby="intake">
          <h2 id="intake" className="text-lg font-semibold">
            Intake
          </h2>
          <div className="mt-4">
            {intake.success ? <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} /> : <p className="text-sm text-muted">Nog geen intake ingevuld.</p>}
          </div>
        </aside>
      </div>
    );
  }

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        back={{ href: section.href, label: section.title }}
        title={`Nieuw ${section.one}`}
        description="Kies een klant en hoe je het schema wilt maken. Een nieuw schema vervangt het huidige pas als je het publiceert."
      />
      <div className="max-w-xl">
        <MemberPicker action={`${section.href}/nieuw`} groups={groups} selected={member?.id} />
      </div>
      {lid && !member && <p className="mt-4 text-sm text-danger">Deze klant bestaat niet (meer).</p>}
      {details}
    </div>
  );
}
