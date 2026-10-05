import { and, asc, eq, gt } from "drizzle-orm";
import { CalendarPlus, Dumbbell, LineChart, Salad, Trash2 } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { MemberCoachingForm } from "@/components/admin/MemberCoachingForm";
import { ADMIN_PAGE, AdminPageHeader, CoachingBadge } from "@/components/admin/ui";
import { agendaHref } from "@/components/admin/calendar/shared";
import { MeasurementForm } from "@/components/progress/MeasurementForm";
import { ProgressOverview } from "@/components/progress/ProgressOverview";
import { formatPlanDay, StageBadge } from "@/components/admin/plans/stage";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { PLAN_TYPE_LABEL } from "@/components/plans/labels";
import { requireAdmin } from "@/lib/auth";
import { formatDayLong, formatTime, getAgendaLocation, getAppointmentType, zonedParts } from "@/lib/agenda";
import { deleteMeasurementAction } from "@/lib/actions/progress";
import { resetMemberTwoFactorAction } from "@/lib/actions/two-factor";
import { appointments, db, intakes, measurements, PLAN_TYPES, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { relativeDay } from "@/lib/plans/pipeline";
import { loadPlanPipeline } from "@/lib/plans/pipeline-server";
import { newPlanHref, planHref } from "@/lib/plans/sections";
import { formatNumber, MEASUREMENT_FIELDS } from "@/lib/progress";
import { algemeen } from "@/lib/content/registry";
import { getTexts, onlinePlanName } from "@/lib/content/texts";
import { GOALS } from "@/lib/site";
import { passwordDaysLeft, passwordExpiresAt } from "@/lib/totp";
import { remainingRecoveryCodes } from "@/lib/two-factor";

export const metadata: Metadata = { title: "Lid" };

const PLAN_ICON = { training: Dumbbell, voeding: Salad };
const shortFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", timeZone: "Europe/Amsterdam" });
const sinceFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", year: "numeric", timeZone: "Europe/Amsterdam" });

export default async function MemberPage({ params }: PageProps<"/admin/leden/[id]">) {
  await requireAdmin();
  const { id } = await params;
  const [member] = await db.select().from(users).where(eq(users.id, id));
  if (!member) notFound();

  const now = new Date();
  const [planName, { vriendenactie }, [intakeRow], pipeline, rows, upcoming, [inviter]] = await Promise.all([
    onlinePlanName(member.plan),
    getTexts(algemeen),
    db.select().from(intakes).where(eq(intakes.userId, id)),
    loadPlanPipeline(id),
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
  const { today } = pipeline;
  const codesLeft = member.totpEnabledAt ? await remainingRecoveryCodes(member.id) : 0;
  const passwordChanged = member.passwordChangedAt ?? member.createdAt;

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        back={{ href: "/admin/leden", label: "Leden" }}
        title={`${member.firstName} ${member.lastName}`}
        description={
          <span className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <CoachingBadge status={member.coachingStatus} />
            {member.plan && <span className="font-medium text-ink">{planName}</span>}
            <a href={`mailto:${member.email}`} className="hover:text-ink hover:underline">
              {member.email}
            </a>
            {member.phone && (
              <a href={`tel:${member.phone.replace(/\s/g, "")}`} className="hover:text-ink hover:underline">
                {member.phone}
              </a>
            )}
            <span>Doel: {GOALS.find((g) => g.id === member.goal)?.label ?? "–"}</span>
            <span>Lid sinds {sinceFmt.format(member.createdAt)}</span>
          </span>
        }
        actions={
          <>
            <a href="#metingen" className="btn btn-sm btn-outline">
              <LineChart className="size-4" aria-hidden="true" /> Meting toevoegen
            </a>
            <Link href={`/admin/agenda/nieuw?lid=${member.id}`} className="btn btn-sm btn-primary">
              <CalendarPlus className="size-4" aria-hidden="true" /> Afspraak inplannen
            </Link>
          </>
        }
      />
      {inviter && (
        <p className="mb-6 inline-flex rounded-lg bg-accent-tint px-3 py-2 text-sm">
          Uitgenodigd door {inviter.firstName} {inviter.lastName}: krijgt {vriendenactie.friendReward}.
        </p>
      )}

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div className="grid min-w-0 grid-cols-1 content-start gap-6">
          <section className="card p-6" aria-labelledby="coaching">
            <h2 id="coaching" className="text-lg font-semibold">
              Coaching
            </h2>
            <div className="mt-4">
              <MemberCoachingForm userId={member.id} status={member.coachingStatus} note={member.coachNote} />
            </div>
          </section>

          <section className="card p-6" aria-labelledby="schemas">
            <h2 id="schemas" className="text-lg font-semibold">
              Schema&apos;s
            </h2>
            <ul className="mt-3 divide-y divide-line">
              {PLAN_TYPES.map((type) => {
                const row = pipeline.rows[type][0];
                const Icon = PLAN_ICON[type];
                const open = row?.open ? planHref(type, row.open.id) : null;
                return (
                  <li key={type} className="flex flex-wrap items-center gap-x-4 gap-y-2 py-3.5">
                    <Icon className="size-5 shrink-0 text-muted" aria-hidden="true" />
                    <div className="min-w-0 flex-1">
                      <p className="flex flex-wrap items-center gap-2 font-semibold">
                        {PLAN_TYPE_LABEL[type]} {row && <StageBadge stage={row.stage} coachingStatus={member.coachingStatus} />}
                      </p>
                      <p className="text-sm text-muted">
                        {row?.current ? `${row.current.title || "Schema"} · sinds ${shortFmt.format(row.current.publishedAt)}` : "Nog geen schema"}
                        {row?.dueOn && (
                          <span className={row.dueOn <= today && row.stage !== "pauze" ? "text-danger" : ""}>
                            {" "}
                            · nieuw schema {row.stage === "gepland" ? "start " : ""}
                            {formatPlanDay(row.dueOn)} ({relativeDay(today, row.dueOn)})
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {open ? (
                        <Link href={open} className="btn btn-sm btn-primary">
                          {row?.stage === "controleren" ? "Controleren" : "Concept openen"}
                        </Link>
                      ) : (
                        <>
                          {row?.current && (
                            <Link href={planHref(type, row.current.id)} className="btn btn-sm btn-outline">
                              Bekijken
                            </Link>
                          )}
                          {row?.scheduled && (
                            <Link href={planHref(type, row.scheduled.id)} className="btn btn-sm btn-outline">
                              Ingepland
                            </Link>
                          )}
                          <Link href={newPlanHref(type, member.id)} className="btn btn-sm btn-outline">
                            Nieuw schema
                          </Link>
                        </>
                      )}
                    </div>
                  </li>
                );
              })}
            </ul>
          </section>

          <section id="metingen-blok" className="card scroll-mt-20 p-6" aria-labelledby="metingen">
            <h2 id="metingen" className="scroll-mt-20 text-lg font-semibold">
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
        </div>

        <aside className="grid min-w-0 grid-cols-1 content-start gap-6">
          <section className="card p-6" aria-labelledby="afspraken">
            <h2 id="afspraken" className="text-lg font-semibold">
              Komende afspraken
            </h2>
            {upcoming.length === 0 ? (
              <p className="mt-3 text-sm text-muted">Geen afspraken gepland.</p>
            ) : (
              <ul className="mt-3 divide-y divide-line text-sm">
                {upcoming.map((a) => (
                  <li key={a.id}>
                    <Link
                      href={agendaHref({ view: "dag", day: zonedParts(a.startsAt).day, cancelled: false }, { afspraak: a.id })}
                      className="-mx-2 block rounded-md px-2 py-2.5 hover:bg-surface"
                    >
                      <span className="block font-medium first-letter:uppercase">
                        {formatDayLong(a.startsAt)}, {formatTime(a.startsAt)}
                      </span>
                      <span className="text-muted">
                        {getAppointmentType(a.type)?.label ?? a.type} · {getAgendaLocation(a.location)?.label ?? a.location}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </section>
          <section className="card p-6" aria-labelledby="beveiliging">
            <h2 id="beveiliging" className="text-lg font-semibold">
              Inloggen en beveiliging
            </h2>
            <dl className="mt-3 grid gap-2 text-sm">
              <div className="flex justify-between gap-3">
                <dt className="text-muted">Tweestapsverificatie</dt>
                <dd className="text-right font-medium">
                  {member.totpEnabledAt ? (
                    <>
                      aan sinds {shortFmt.format(member.totpEnabledAt)}
                      <span className="block font-normal text-muted">
                        {codesLeft} {codesLeft === 1 ? "herstelcode" : "herstelcodes"} over
                      </span>
                    </>
                  ) : (
                    <>
                      nog niet ingesteld
                      <span className="block font-normal text-muted">gebeurt bij de volgende keer inloggen</span>
                    </>
                  )}
                </dd>
              </div>
              <div className="flex justify-between gap-3">
                <dt className="text-muted">Wachtwoord</dt>
                <dd className="text-right font-medium">
                  {passwordDaysLeft(passwordChanged) <= 0 ? "verlopen" : `verloopt ${shortFmt.format(passwordExpiresAt(passwordChanged))}`}
                  <span className="block font-normal text-muted">gewijzigd {shortFmt.format(passwordChanged)}</span>
                </dd>
              </div>
            </dl>
            {member.totpEnabledAt && (
              <details className="mt-4 rounded-lg border border-line p-3 text-sm">
                <summary className="cursor-pointer font-semibold">Tweestapsverificatie resetten…</summary>
                <p className="mt-2 text-muted">
                  Alleen als {member.firstName} de telefoon én de herstelcodes kwijt is. {member.firstName} wordt overal uitgelogd en koppelt bij de volgende keer
                  inloggen een nieuwe telefoon. Controleer eerst of je echt met {member.firstName} zelf spreekt.
                </p>
                <form action={resetMemberTwoFactorAction} className="mt-3">
                  <input type="hidden" name="userId" value={member.id} />
                  <button type="submit" className="btn btn-sm btn-outline border-danger/40 text-danger hover:border-danger">
                    Ja, resetten
                  </button>
                </form>
              </details>
            )}
          </section>

          <section className="card p-6" aria-labelledby="intake">
            <h2 id="intake" className="text-lg font-semibold">Intake</h2>
            <div className="mt-4">
              {intake.success ? (
                <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} />
              ) : (
                <p className="text-sm text-muted">Dit lid heeft nog geen intake ingevuld.</p>
              )}
            </div>
          </section>
        </aside>
      </div>

    </div>
  );
}