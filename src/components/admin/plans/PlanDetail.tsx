import { and, desc, eq } from "drizzle-orm";
import { AlertTriangle, CalendarClock, LoaderCircle } from "lucide-react";
import Link from "next/link";
import { notFound, redirect } from "next/navigation";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { AutoRefresh } from "@/components/plans/AutoRefresh";
import { GenerateForms } from "@/components/plans/GenerateForms";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { PlanEditor } from "@/components/plans/PlanEditor";
import { NutritionPlanView, TrainingPlanView } from "@/components/plans/PlanViews";
import { unschedulePlanAction } from "@/lib/actions/plans";
import { zonedParts } from "@/lib/agenda";
import { db, intakes, plans, type PlanType, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { defaultRenewOn, relativeDay } from "@/lib/plans/pipeline";
import { loadPlanPipeline } from "@/lib/plans/pipeline-server";
import { activateDuePlans } from "@/lib/plans/schedule";
import { planSchemaFor, type PlanContent } from "@/lib/plans/schema";
import { newPlanHref, PLAN_SECTION, planHref } from "@/lib/plans/sections";
import { formatPlanDayLong, StageBadge } from "./stage";

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit", timeZone: "Europe/Amsterdam" });
const shortFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", timeZone: "Europe/Amsterdam" });

/** Controleren, bewerken en publiceren van één schema, met planning en eerdere versies ernaast. */
export async function PlanDetail({ type, id }: { type: PlanType; id: string }) {
  const planId = Number(id);
  if (!Number.isInteger(planId)) notFound();

  await activateDuePlans();
  const [plan] = await db.select().from(plans).where(eq(plans.id, planId));
  if (!plan) notFound();
  if (plan.type !== type) redirect(planHref(plan.type, plan.id));

  const [[member], [intakeRow], versions, pipeline] = await Promise.all([
    db.select().from(users).where(eq(users.id, plan.userId)),
    db.select().from(intakes).where(eq(intakes.userId, plan.userId)),
    db
      .select({ id: plans.id, status: plans.status, source: plans.source, createdAt: plans.createdAt, publishedAt: plans.publishedAt, updatedAt: plans.updatedAt, startsOn: plans.startsOn })
      .from(plans)
      .where(and(eq(plans.userId, plan.userId), eq(plans.type, type)))
      .orderBy(desc(plans.createdAt)),
    loadPlanPipeline(plan.userId),
  ]);
  if (!member) notFound();

  const section = PLAN_SECTION[type];
  const { today } = pipeline;
  const row = pipeline.rows[type][0];
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const parsed = plan.content ? planSchemaFor(plan.type).safeParse(plan.content) : null;
  const content = parsed?.success ? (parsed.data as PlanContent) : null;
  const stuck = isStuck(plan.status, plan.updatedAt);
  const status = stuck ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[plan.status];
  const intakeChanged = intakeRow && plan.status !== "vervangen" && intakeRow.updatedAt > plan.createdAt;
  const edited = plan.source === "ai" && plan.aiDraft != null && JSON.stringify(plan.aiDraft) !== JSON.stringify(plan.content);
  const editable = (plan.status === "concept" || plan.status === "gepland" || plan.status === "gepubliceerd") && content;
  const live = plan.status === "gepubliceerd";
  const durationWeeks = content && "days" in content ? content.durationWeeks : null;
  const startsOn = live ? (plan.startsOn ?? (plan.publishedAt ? zonedParts(plan.publishedAt).day : today)) : plan.startsOn && plan.startsOn > today ? plan.startsOn : today;
  const renewOn = plan.renewOn ?? (live && row?.currentDueOn ? row.currentDueOn : defaultRenewOn(type, startsOn, durationWeeks));
  const futureStart = plan.startsOn && plan.startsOn > today ? plan.startsOn : null;

  return (
    <div className={ADMIN_PAGE}>
      {plan.status === "genereren" && !stuck && <AutoRefresh />}
      <AdminPageHeader
        back={{ href: section.href, label: section.title }}
        title={
          <>
            {PLAN_TYPE_LABEL[type]} ·{" "}
            <Link href={`/admin/leden/${member.id}`} className="hover:underline">
              {member.firstName} {member.lastName}
            </Link>
          </>
        }
        description={
          <span className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${status.tone}`}>{status.label}</span>
            {edited && <span className="rounded-full bg-surface px-2.5 py-0.5 text-xs font-semibold text-ink">Aangepast door Steyn</span>}
            <span>
              Versie #{plan.id} · {plan.source === "ai" ? `AI-concept (${plan.model ?? "AI"})` : "handmatig"} · gemaakt {dateFmt.format(plan.createdAt)}
              {plan.publishedAt ? ` · gepubliceerd ${dateFmt.format(plan.publishedAt)}` : ""}
              {!live && futureStart ? ` · start ${shortFmt.format(new Date(`${futureStart}T12:00:00Z`))}` : ""}
            </span>
            {plan.instruction && <span>Instructie: &ldquo;{plan.instruction}&rdquo;</span>}
          </span>
        }
      />

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div className="grid min-w-0 grid-cols-1 content-start gap-6">
          {plan.status === "gepland" && futureStart && (
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#c7cbf5] bg-[#eef0ff] p-4 text-sm">
              <p className="flex gap-2">
                <CalendarClock className="size-5 shrink-0 text-[#3730a3]" aria-hidden="true" />
                <span>
                  <strong className="font-semibold">Ingepland.</strong> De klant ziet dit schema vanaf {formatPlanDayLong(futureStart)} ({relativeDay(today, futureStart)}).
                  {row?.current ? " Tot dan blijft het huidige schema zichtbaar." : ""} Wijzigen kan nog.
                </span>
              </p>
              <form action={unschedulePlanAction}>
                <input type="hidden" name="planId" value={plan.id} />
                <button type="submit" className="btn btn-sm btn-outline bg-white">
                  Terugzetten naar concept
                </button>
              </form>
            </div>
          )}

          {intakeChanged && (
            <p className="flex gap-2 rounded-xl border border-accent/30 bg-accent-tint p-4 text-sm">
              <AlertTriangle className="size-5 shrink-0 text-accent" aria-hidden="true" />
              De klant heeft de intake gewijzigd nadat dit concept is gemaakt. Controleer extra goed of laat een nieuw concept maken.
            </p>
          )}

          {plan.status === "genereren" && !stuck && (
            <div className="card flex items-center gap-4 p-8">
              <LoaderCircle className="size-8 animate-spin text-accent" aria-hidden="true" />
              <div>
                <p className="font-semibold">De AI maakt het concept…</p>
                <p className="text-sm text-muted">Dit duurt meestal één à twee minuten. De pagina ververst vanzelf.</p>
              </div>
            </div>
          )}

          {(plan.status === "fout" || stuck) && (
            <div className="card p-6">
              <p className="flex items-center gap-2 font-semibold text-danger">
                <AlertTriangle className="size-5" aria-hidden="true" /> Het concept kon niet gemaakt worden
              </p>
              <p className="mt-1 text-sm text-muted">{stuck ? "De generatie is niet afgerond (mogelijk door een herstart van de server)." : plan.error}</p>
              <div className="mt-5">
                <GenerateForms userId={member.id} type={plan.type} aiEnabled={aiConfigured()} hasIntake={intake.success} startsOn={futureStart} />
              </div>
            </div>
          )}

          {plan.status === "vervangen" && content && (
            <>
              <p className="rounded-xl bg-surface p-4 text-sm">
                Dit is een oudere versie en is niet meer bewerkbaar. De actuele versie vind je rechts onder Versies.
              </p>
              <div className="card p-6">{"days" in content ? <TrainingPlanView plan={content} /> : <NutritionPlanView plan={content} />}</div>
            </>
          )}

          {editable && (
            <>
              <PlanEditor
                planId={plan.id}
                initial={content}
                status={plan.status as "concept" | "gepland" | "gepubliceerd"}
                allergyContext={intake.success ? { allergies: intake.data.allergies, diet: intake.data.diet } : null}
                today={today}
                startsOn={startsOn}
                renewOn={renewOn}
                followDuration={plan.renewOn == null && !live}
              />
              <details className="card p-6">
                <summary className="cursor-pointer font-semibold">Nieuw concept laten maken</summary>
                <div className="mt-4">
                  <GenerateForms userId={member.id} type={plan.type} aiEnabled={aiConfigured()} hasIntake={intake.success} startsOn={live ? null : futureStart} regenerate />
                </div>
              </details>
            </>
          )}
        </div>

        <aside className="grid min-w-0 grid-cols-1 content-start gap-6">
          <section className="card p-6" aria-labelledby="planning">
            <div className="flex items-center justify-between gap-3">
              <h2 id="planning" className="text-lg font-semibold">
                Planning
              </h2>
              {row && <StageBadge stage={row.stage} coachingStatus={member.coachingStatus} />}
            </div>
            <dl className="mt-3 grid gap-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted">Huidig schema</dt>
                <dd className="text-right font-medium">{row?.current ? `sinds ${shortFmt.format(row.current.publishedAt)}` : "nog geen"}</dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted">Nieuw schema nodig</dt>
                <dd className="text-right font-medium">
                  {row?.currentDueOn ? (
                    <>
                      <span className="first-letter:uppercase">{formatPlanDayLong(row.currentDueOn)}</span>
                      <span className={`block font-normal ${row.currentDueOn <= today && row.stage !== "pauze" && row.stage !== "gepland" ? "text-danger" : "text-muted"}`}>
                        {relativeDay(today, row.currentDueOn)}
                      </span>
                    </>
                  ) : (
                    "zo snel mogelijk"
                  )}
                </dd>
              </div>
              {row?.scheduled && (
                <div className="flex justify-between gap-3">
                  <dt className="text-muted">Volgend schema</dt>
                  <dd className="text-right font-medium">
                    {row.scheduled.id === plan.id ? (
                      "dit schema"
                    ) : (
                      <Link href={planHref(type, row.scheduled.id)} className="underline decoration-accent underline-offset-4">
                        ingepland
                      </Link>
                    )}
                    <span className="block font-normal text-muted">start {formatPlanDayLong(row.scheduled.startsOn)}</span>
                  </dd>
                </div>
              )}
            </dl>
            {plan.status === "gepubliceerd" && <p className="mt-3 text-xs text-muted">De datum pas je aan onder het schema, bij &ldquo;Nieuw schema op&rdquo;.</p>}
            {plan.status !== "concept" && plan.status !== "genereren" && !row?.open && (
              <Link href={newPlanHref(type, member.id)} className="btn btn-sm btn-outline mt-4 w-full">
                Nieuw {section.one} maken
              </Link>
            )}
          </section>

          <section className="card p-6" aria-labelledby="versies">
            <h2 id="versies" className="text-lg font-semibold">
              Versies
            </h2>
            <ul className="mt-2 divide-y divide-line text-sm">
              {versions.map((v) => {
                const s = isStuck(v.status, v.updatedAt) ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[v.status];
                const here = v.id === plan.id;
                return (
                  <li key={v.id}>
                    <Link
                      href={planHref(type, v.id)}
                      aria-current={here ? "page" : undefined}
                      className={`-mx-2 flex items-center justify-between gap-3 rounded-md px-2 py-2 ${here ? "bg-surface" : "hover:bg-surface"}`}
                    >
                      <span>
                        <span className="font-semibold">#{v.id}</span>
                        <span className="ml-2 text-muted">
                          {v.status === "gepland" && v.startsOn ? `start ${shortFmt.format(new Date(`${v.startsOn}T12:00:00Z`))}` : shortFmt.format(v.publishedAt ?? v.createdAt)} ·{" "}
                          {v.source === "ai" ? "AI" : "handmatig"}
                        </span>
                      </span>
                      <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${s.tone}`}>{s.label}</span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          </section>

          <section className="card p-6" aria-labelledby="intake">
            <h2 id="intake" className="text-lg font-semibold">
              Intake
            </h2>
            <div className="mt-4">
              {intake.success ? <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} /> : <p className="text-sm text-muted">Geen intake ingevuld.</p>}
            </div>
          </section>
        </aside>
      </div>
    </div>
  );
}
