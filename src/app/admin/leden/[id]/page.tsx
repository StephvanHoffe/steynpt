import { and, asc, desc, eq, gt } from "drizzle-orm";
import { ArrowLeft, Info, Trash2 } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { MeasurementForm } from "@/components/progress/MeasurementForm";
import { ProgressOverview } from "@/components/progress/ProgressOverview";
import { GenerateForms } from "@/components/plans/GenerateForms";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { requireAdmin } from "@/lib/auth";
import { formatDayLong, formatTime, getAgendaLocation, getAppointmentType, zonedParts } from "@/lib/agenda";
import { deleteMeasurementAction } from "@/lib/actions/progress";
import { appointments, db, intakes, measurements, PLAN_TYPES, plans, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { formatNumber, MEASUREMENT_FIELDS } from "@/lib/progress";
import { REFERRAL } from "@/lib/referral-program";
import { getOnlinePlan } from "@/lib/site";

export const metadata: Metadata = { title: "Lid", robots: { index: false } };
export const maxDuration = 300;

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

export default async function MemberPage({ params }: PageProps<"/admin/leden/[id]">) {
  await requireAdmin();
  const { id } = await params;
  const [member] = await db.select().from(users).where(eq(users.id, id));
  if (!member) notFound();

  const now = new Date();
  const [[intakeRow], memberPlans, rows, upcoming, [inviter]] = await Promise.all([
    db.select().from(intakes).where(eq(intakes.userId, id)),
    db.select().from(plans).where(eq(plans.userId, id)).orderBy(desc(plans.createdAt)),
    db.select().from(measurements).where(eq(measurements.userId, id)).orderBy(asc(measurements.measuredAt)),
    db
      .select()
      .from(appointments)
      .where(and(eq(appointments.userId, id), eq(appointments.status, "gepland"), gt(appointments.endsAt, now)))
      .orderBy(asc(appointments.startsAt)),
    member.referredById
      ? db.select({ firstName: users.firstName, lastName: users.lastName }).from(users).where(eq(users.id, member.referredById))
      : Promise.resolve([] as { firstName: string; lastName: string }[]),
  ]);
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const aiEnabled = aiConfigured();

  return (
    <div className="container-site py-10 lg:py-14">
      <Link href="/admin" className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> Beheer
      </Link>
      <h1 className="display display-lg mt-3">
        {member.firstName} {member.lastName}
      </h1>
      <p className="mt-2 text-muted">
        {member.email}
        {member.phone ? ` · ${member.phone}` : ""} · coaching: <strong className="text-ink">{member.coachingStatus}</strong>
        {member.plan ? ` (${getOnlinePlan(member.plan)?.name})` : ""}
      </p>
      {inviter && (
        <p className="mt-3 inline-flex rounded-lg bg-accent-tint px-3 py-2 text-sm">
          Uitgenodigd door {inviter.firstName} {inviter.lastName}: krijgt {REFERRAL.friendReward}.
        </p>
      )}

      {!aiEnabled && (
        <p className="mt-6 flex gap-2 rounded-xl bg-surface p-4 text-sm">
          <Info className="size-5 shrink-0" aria-hidden="true" />
          AI staat uit: stel ANTHROPIC_API_KEY in om concepten automatisch te laten maken. Je kunt schema&apos;s wel zelf opstellen.
        </p>
      )}

      <div className="mt-10 grid gap-8 lg:grid-cols-[1.2fr_1fr]">
        <div className="grid content-start gap-6">
          {PLAN_TYPES.map((type) => {
            const list = memberPlans.filter((p) => p.type === type);
            const hasOpen = list.some((p) => p.status === "genereren" || p.status === "concept");
            return (
              <section key={type} className="card p-6" aria-labelledby={`type-${type}`}>
                <h2 id={`type-${type}`} className="display text-2xl">
                  {PLAN_TYPE_LABEL[type]}
                </h2>
                {list.length === 0 ? (
                  <p className="mt-3 text-sm text-muted">Nog geen schema.</p>
                ) : (
                  <ul className="mt-4 divide-y divide-line">
                    {list.map((p) => {
                      const status = isStuck(p.status, p.updatedAt) ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[p.status];
                      return (
                        <li key={p.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                          <span>
                            <Link href={`/admin/schemas/${p.id}`} className="font-semibold underline decoration-accent underline-offset-4">
                              Versie #{p.id}
                            </Link>
                            <span className="ml-2 text-muted">
                              {dateFmt.format(p.createdAt)} · {p.source === "ai" ? "AI-concept" : "handmatig"}
                            </span>
                          </span>
                          <span className={`rounded-full px-3 py-1 text-xs font-semibold ${status.tone}`}>{status.label}</span>
                        </li>
                      );
                    })}
                  </ul>
                )}
                {!hasOpen && (
                  <div className="mt-5 border-t border-line pt-5">
                    <GenerateForms userId={member.id} type={type} aiEnabled={aiEnabled} hasIntake={intake.success} />
                  </div>
                )}
              </section>
            );
          })}
        </div>
        <aside className="card self-start p-6 lg:row-span-2">
          <h2 className="display text-2xl">Intake</h2>
          <div className="mt-4">
            {intake.success ? (
              <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} />
            ) : (
              <p className="text-sm text-muted">Dit lid heeft nog geen intake ingevuld.</p>
            )}
          </div>
        </aside>
      </div>

      <section className="mt-10 card p-6" aria-labelledby="metingen">
        <h2 id="metingen" className="display text-2xl">
          Metingen
        </h2>
        <p className="mt-1 text-sm text-muted">Wat je hier invoert, ziet de klant direct onder Mijn voortgang.</p>
        <div className="mt-5">
          <MeasurementForm userId={member.id} today={zonedParts(now).day} />
        </div>
        {rows.length > 0 && (
          <>
            <div className="mt-8">
              <ProgressOverview rows={rows} />
            </div>
            <div className="mt-6 relative overflow-x-auto rounded-lg border border-line">
              <table className="w-full min-w-[640px] text-left text-sm">
                <caption className="sr-only">Metingen van {member.firstName}</caption>
                <thead className="bg-surface text-xs uppercase tracking-wider text-muted">
                  <tr>
                    <th className="px-3 py-2 font-semibold">Datum</th>
                    {MEASUREMENT_FIELDS.map((f) => (
                      <th key={f.key} className="px-3 py-2 font-semibold">
                        {f.label}
                      </th>
                    ))}
                    <th className="px-3 py-2 font-semibold">
                      <span className="sr-only">Acties</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {[...rows].reverse().map((r) => (
                    <tr key={r.id} className="border-t border-line">
                      <td className="whitespace-nowrap px-3 py-2 font-medium">{formatDayLong(r.measuredAt)}</td>
                      {MEASUREMENT_FIELDS.map((f) => (
                        <td key={f.key} className="px-3 py-2 tabular-nums">
                          {r[f.key] != null ? formatNumber(r[f.key] as number) : "–"}
                        </td>
                      ))}
                      <td className="px-3 py-2 text-right">
                        <form action={deleteMeasurementAction}>
                          <input type="hidden" name="id" value={r.id} />
                          <input type="hidden" name="userId" value={member.id} />
                          <button type="submit" aria-label={`Meting van ${formatDayLong(r.measuredAt)} verwijderen`} className="grid size-8 place-items-center rounded-md text-muted hover:bg-surface hover:text-danger">
                            <Trash2 className="size-4" aria-hidden="true" />
                          </button>
                        </form>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </>
        )}
      </section>

      <section className="mt-6 card p-6" aria-labelledby="afspraken">
        <h2 id="afspraken" className="display text-2xl">
          Komende afspraken
        </h2>
        {upcoming.length === 0 ? (
          <p className="mt-3 text-sm text-muted">Geen afspraken gepland.</p>
        ) : (
          <ul className="mt-3 divide-y divide-line text-sm">
            {upcoming.map((a) => (
              <li key={a.id} className="flex flex-wrap justify-between gap-2 py-2.5">
                <span className="font-medium first-letter:uppercase">
                  {formatDayLong(a.startsAt)}, {formatTime(a.startsAt)}
                </span>
                <span className="text-muted">
                  {getAppointmentType(a.type)?.label} · {getAgendaLocation(a.location)?.label}
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}