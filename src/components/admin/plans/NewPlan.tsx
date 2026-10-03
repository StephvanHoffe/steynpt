import { asc, eq } from "drizzle-orm";
import { AlertTriangle, CalendarClock } from "lucide-react";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { db, intakes, type PlanType, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { STAGES, relativeDay } from "@/lib/plans/pipeline";
import { loadPlanPipeline } from "@/lib/plans/pipeline-server";
import { PLAN_SECTION, planHref } from "@/lib/plans/sections";
import { getOnlinePlan } from "@/lib/site";
import { MemberPicker, type PickerGroup } from "./MemberPicker";
import { NewPlanForm } from "./NewPlanForm";
import { formatPlanDayLong, StageBadge } from "./stage";

const sinceFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", timeZone: "Europe/Amsterdam" });

/** Nieuw schema maken: klant kiezen, startdatum, en dan AI-concept, verder met het huidige schema of leeg beginnen. */
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
    // Standaard start het nieuwe schema waar het huidige ophoudt (of op de al ingeplande dag).
    const defaultStart = row?.scheduled?.startsOn ?? (row?.currentDueOn && row.currentDueOn > today ? row.currentDueOn : today);

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
                  {row?.currentDueOn ? (
                    <>
                      <span className="first-letter:uppercase">{formatPlanDayLong(row.currentDueOn)}</span>
                      <span className={`block font-normal ${row.currentDueOn <= today && row.stage !== "pauze" ? "text-danger" : "text-muted"}`}>
                        {relativeDay(today, row.currentDueOn)}
                      </span>
                    </>
                  ) : (
                    "Zo snel mogelijk"
                  )}
                </dd>
              </div>
            </dl>
            {row?.scheduled && (
              <p className="mt-4 flex gap-2 rounded-lg bg-[#eef0ff] p-3 text-sm">
                <CalendarClock className="size-4 shrink-0 text-[#3730a3]" aria-hidden="true" />
                <span>
                  Er staat al een schema ingepland vanaf {formatPlanDayLong(row.scheduled.startsOn)}.{" "}
                  <Link href={planHref(type, row.scheduled.id)} className="font-semibold underline">
                    Ingepland schema openen
                  </Link>
                  . Een nieuw schema dat je inplant, vervangt die planning.
                </span>
              </p>
            )}
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

          <NewPlanForm
            key={member.id}
            userId={member.id}
            type={type}
            today={today}
            defaultStart={defaultStart}
            currentEndsOn={row?.currentDueOn ?? null}
            hasCurrent={Boolean(row?.current || row?.scheduled)}
            ai={
              ai && intake.success
                ? { available: true }
                : {
                    available: false,
                    reason: !intake.success
                      ? "Kan nog niet: de klant heeft de intake nog niet ingevuld."
                      : "AI staat uit: stel ANTHROPIC_API_KEY in om concepten te laten maken.",
                  }
            }
          />
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
        description="Kies een klant, de startdatum en hoe je het schema wilt maken. De klant ziet het pas na jouw controle, en niet voor de startdatum."
      />
      <div className="max-w-xl">
        <MemberPicker action={`${section.href}/nieuw`} groups={groups} selected={member?.id} />
      </div>
      {lid && !member && <p className="mt-4 text-sm text-danger">Deze klant bestaat niet (meer).</p>}
      {details}
    </div>
  );
}
