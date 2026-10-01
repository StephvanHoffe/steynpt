import { and, asc, count, eq, gt, gte, isNotNull, isNull, lt } from "drizzle-orm";
import { alias } from "drizzle-orm/sqlite-core";
import { ArrowRight, CalendarDays, CalendarPlus, CheckCircle2, ClipboardCheck, Gift, Inbox, UserPlus } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { agendaHref, typeColor } from "@/components/admin/calendar/shared";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { markReferralRewardAction } from "@/lib/actions/admin";
import { adminCounts } from "@/lib/admin";
import { addDays, formatDayLong, formatTime, getAgendaLocation, getAppointmentType, zonedParts, zonedTimeToUtc } from "@/lib/agenda";
import { startOfWeek } from "@/lib/agenda-calendar";
import { requireAdmin } from "@/lib/auth";
import { appointments, checkIns, db, users } from "@/lib/db";
import { REFERRAL } from "@/lib/referral-program";
import { isoWeekKey } from "@/lib/weeks";

export const metadata: Metadata = { title: "Overzicht" };

const longDate = new Intl.DateTimeFormat("nl-NL", { weekday: "long", day: "numeric", month: "long", timeZone: "Europe/Amsterdam" });

function greeting(now: Date) {
  const hour = Number(zonedParts(now).time.slice(0, 2));
  return hour < 12 ? "Goedemorgen" : hour < 18 ? "Goedemiddag" : "Goedenavond";
}

export default async function AdminOverviewPage() {
  const admin = await requireAdmin();
  const referrer = alias(users, "referrer");
  const now = new Date();
  const today = zonedParts(now).day;
  const dayStart = zonedTimeToUtc(today, "00:00");
  const dayEnd = zonedTimeToUtc(addDays(today, 1), "00:00");
  const weekStart = zonedTimeToUtc(startOfWeek(today), "00:00");
  const weekEnd = zonedTimeToUtc(addDays(startOfWeek(today), 7), "00:00");

  const withClient = { a: appointments, firstName: users.firstName, lastName: users.lastName, phone: users.phone };
  const [counts, todays, upcoming, rewardsDue, [week], [active], [newMembers], [checks]] = await Promise.all([
    adminCounts(),
    db
      .select(withClient)
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(and(eq(appointments.status, "gepland"), gte(appointments.startsAt, dayStart), lt(appointments.startsAt, dayEnd)))
      .orderBy(asc(appointments.startsAt)),
    db
      .select(withClient)
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(and(eq(appointments.status, "gepland"), gte(appointments.startsAt, dayEnd)))
      .orderBy(asc(appointments.startsAt))
      .limit(5),
    db
      .select({ id: users.id, firstName: users.firstName, lastName: users.lastName, referrerFirst: referrer.firstName, referrerLast: referrer.lastName })
      .from(users)
      .innerJoin(referrer, eq(users.referredById, referrer.id))
      .where(and(eq(users.coachingStatus, "actief"), isNotNull(users.referredById), isNull(users.referralRewardAt))),
    db
      .select({ n: count() })
      .from(appointments)
      .where(and(eq(appointments.status, "gepland"), gte(appointments.startsAt, weekStart), lt(appointments.startsAt, weekEnd))),
    db.select({ n: count() }).from(users).where(eq(users.coachingStatus, "actief")),
    db.select({ n: count() }).from(users).where(and(eq(users.role, "member"), gt(users.createdAt, new Date(now.getTime() - 30 * 864e5)))),
    db.select({ n: count() }).from(checkIns).where(eq(checkIns.week, isoWeekKey(now))),
  ]);

  const todo = [
    { n: counts.schemas, href: "/admin/schemas", icon: ClipboardCheck, one: "schema te controleren", many: "schema's te controleren" },
    { n: counts.requests, href: "/admin/aanvragen", icon: Inbox, one: "nieuwe contactaanvraag", many: "nieuwe contactaanvragen" },
    { n: counts.applied, href: "/admin/leden?status=aangevraagd", icon: UserPlus, one: "lid wacht op een intake", many: "leden wachten op een intake" },
  ].filter((t) => t.n > 0);

  const stats = [
    { label: "Afspraken deze week", value: week.n, href: agendaHref({ view: "week", day: today, cancelled: false }) },
    { label: "Actieve coachingklanten", value: active.n, href: "/admin/leden?status=actief" },
    { label: "Check-ins deze week", value: checks.n, href: "/admin/leden?status=actief" },
    { label: "Nieuwe leden (30 dagen)", value: newMembers.n, href: "/admin/leden" },
  ];

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        title={`${greeting(now)}, ${admin.firstName}`}
        description={<span className="first-letter:uppercase">{longDate.format(now)}</span>}
        actions={
          <Link href={`/admin/agenda/nieuw?datum=${today}`} className="btn btn-sm btn-primary">
            <CalendarPlus className="size-4" aria-hidden="true" /> Nieuwe afspraak
          </Link>
        }
      />

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        {/* Vandaag */}
        <section aria-labelledby="vandaag" className="card overflow-hidden">
          <div className="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
            <h2 id="vandaag" className="flex items-center gap-2 text-lg font-semibold">
              <CalendarDays className="size-5 text-muted" aria-hidden="true" /> Vandaag
            </h2>
            <Link href={agendaHref({ view: "dag", day: today, cancelled: false })} className="text-sm font-semibold text-accent hover:underline">
              Dagweergave
            </Link>
          </div>
          {todays.length === 0 ? (
            <p className="px-5 py-8 text-center text-sm text-muted">Geen afspraken vandaag.</p>
          ) : (
            <ul className="divide-y divide-line">
              {todays.map(({ a, firstName, lastName, phone }) => {
                const past = a.endsAt < now;
                return (
                  <li key={a.id}>
                    <Link
                      href={agendaHref({ view: "dag", day: today, cancelled: false }, { afspraak: a.id })}
                      className={`flex items-center gap-4 px-5 py-3.5 hover:bg-surface ${past ? "opacity-55" : ""}`}
                    >
                      <span className="w-14 shrink-0 text-sm font-semibold tabular-nums">{formatTime(a.startsAt)}</span>
                      <span className="h-9 w-1 shrink-0 rounded-full" style={{ background: typeColor(a.type) }} aria-hidden="true" />
                      <span className="min-w-0 flex-1">
                        <span className="block truncate font-semibold">
                          {firstName} {lastName}
                        </span>
                        <span className="block truncate text-sm text-muted">
                          {getAppointmentType(a.type)?.label ?? a.type} · {getAgendaLocation(a.location)?.label ?? a.location}
                          {phone ? ` · ${phone}` : ""}
                        </span>
                      </span>
                      {past && <span className="text-xs text-muted">Geweest</span>}
                    </Link>
                  </li>
                );
              })}
            </ul>
          )}
          {upcoming.length > 0 && (
            <div className="border-t border-line bg-[#f6f7f8] px-5 py-4">
              <h3 className="text-xs font-semibold uppercase tracking-wide text-muted">Hierna</h3>
              <ul className="mt-2 grid gap-1.5 text-sm">
                {upcoming.map(({ a, firstName, lastName }) => (
                  <li key={a.id}>
                    <Link
                      href={agendaHref({ view: "dag", day: zonedParts(a.startsAt).day, cancelled: false }, { afspraak: a.id })}
                      className="flex flex-wrap items-center gap-x-2 rounded-md hover:underline"
                    >
                      <span className="size-2 rounded-full" style={{ background: typeColor(a.type) }} aria-hidden="true" />
                      <span className="font-medium first-letter:uppercase">
                        {formatDayLong(a.startsAt)} {formatTime(a.startsAt)}
                      </span>
                      <span className="text-muted">
                        · {firstName} {lastName}, {getAppointmentType(a.type)?.label.toLowerCase()}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </section>

        {/* Te doen */}
        <section aria-labelledby="te-doen" className="card self-start overflow-hidden">
          <h2 id="te-doen" className="border-b border-line px-5 py-4 text-lg font-semibold">
            Te doen
          </h2>
          {todo.length === 0 && rewardsDue.length === 0 ? (
            <p className="flex items-center justify-center gap-2 px-5 py-8 text-sm text-muted">
              <CheckCircle2 className="size-4 text-success" aria-hidden="true" /> Alles is bijgewerkt.
            </p>
          ) : (
            <ul className="divide-y divide-line">
              {todo.map((t) => (
                <li key={t.href}>
                  <Link href={t.href} className="flex items-center gap-3 px-5 py-3.5 hover:bg-surface">
                    <span className="grid size-9 place-items-center rounded-lg bg-accent-tint text-accent">
                      <t.icon className="size-4" aria-hidden="true" />
                    </span>
                    <span className="flex-1 text-sm">
                      <strong className="tabular-nums">{t.n}</strong> {t.n === 1 ? t.one : t.many}
                    </span>
                    <ArrowRight className="size-4 text-muted" aria-hidden="true" />
                  </Link>
                </li>
              ))}
              {rewardsDue.map((r) => (
                <li key={r.id} className="flex items-center gap-3 px-5 py-3.5">
                  <span className="grid size-9 shrink-0 place-items-center rounded-lg bg-accent-tint text-accent">
                    <Gift className="size-4" aria-hidden="true" />
                  </span>
                  <span className="flex-1 text-sm">
                    Vriendenkorting verrekenen: <strong>{r.referrerFirst} {r.referrerLast}</strong> bracht {r.firstName} {r.lastName} aan
                    <span className="block text-xs text-muted">{REFERRAL.referrerReward}</span>
                  </span>
                  <form action={markReferralRewardAction}>
                    <input type="hidden" name="friendId" value={r.id} />
                    <button type="submit" className="btn btn-sm btn-outline">
                      Verrekend
                    </button>
                  </form>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>

      <dl className="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        {stats.map((s) => (
          <Link key={s.label} href={s.href} className="card block p-5 transition-colors hover:border-ink/40">
            <dt className="text-sm text-muted">{s.label}</dt>
            <dd className="mt-1 text-3xl font-semibold tabular-nums">{s.value}</dd>
          </Link>
        ))}
      </dl>
    </div>
  );
}
