import { and, asc, desc, eq, gt, inArray, isNotNull, isNull } from "drizzle-orm";
import { alias } from "drizzle-orm/sqlite-core";
import type { Metadata } from "next";
import Link from "next/link";
import { requireAdmin } from "@/lib/auth";
import { CalendarDays } from "lucide-react";
import { markReferralRewardAction, toggleContactHandledAction, updateMemberAction } from "@/lib/actions/admin";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { formatDayLong, formatTime, getAppointmentType } from "@/lib/agenda";
import { appointments, COACHING_STATUSES, contactRequests, db, plans, users } from "@/lib/db";
import { REFERRAL } from "@/lib/referral-program";
import { getOnlinePlan, GOALS, INTERESTS } from "@/lib/site";

export const metadata: Metadata = { title: "Beheer", robots: { index: false } };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric" });

export default async function AdminPage() {
  await requireAdmin();
  const referrer = alias(users, "referrer");

  const now = new Date();
  const [members, rewardsDue, contacts, openPlans, nextAppointments] = await Promise.all([
    db
      .select({
        id: users.id,
        firstName: users.firstName,
        lastName: users.lastName,
        email: users.email,
        phone: users.phone,
        goal: users.goal,
        plan: users.plan,
        coachingStatus: users.coachingStatus,
        coachNote: users.coachNote,
        createdAt: users.createdAt,
        referrerName: referrer.firstName,
      })
      .from(users)
      .leftJoin(referrer, eq(users.referredById, referrer.id))
      .orderBy(desc(users.createdAt)),
    // Vriendenactie: vrienden die gestart zijn en waarvan de korting voor de uitnodiger nog open staat.
    db
      .select({ id: users.id, firstName: users.firstName, lastName: users.lastName, referrerFirst: referrer.firstName, referrerLast: referrer.lastName, referrerEmail: referrer.email })
      .from(users)
      .innerJoin(referrer, eq(users.referredById, referrer.id))
      .where(and(eq(users.coachingStatus, "actief"), isNotNull(users.referredById), isNull(users.referralRewardAt))),
    db.select().from(contactRequests).orderBy(contactRequests.handled, desc(contactRequests.createdAt)).limit(100),
    db
      .select({ id: plans.id, type: plans.type, status: plans.status, createdAt: plans.createdAt, updatedAt: plans.updatedAt, firstName: users.firstName, lastName: users.lastName })
      .from(plans)
      .innerJoin(users, eq(plans.userId, users.id))
      .where(inArray(plans.status, ["genereren", "concept", "fout"]))
      .orderBy(plans.createdAt),
    db
      .select({ a: appointments, firstName: users.firstName, lastName: users.lastName })
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(and(eq(appointments.status, "gepland"), gt(appointments.endsAt, now)))
      .orderBy(asc(appointments.startsAt))
      .limit(5),
  ]);

  const stats = [
    { label: "Schema's te controleren", value: openPlans.filter((p) => p.status === "concept").length },
    { label: "Komende afspraken", value: nextAppointments.length === 5 ? "5+" : nextAppointments.length },
    { label: "Leden", value: members.length },
    { label: "Coaching aangevraagd", value: members.filter((m) => m.coachingStatus === "aangevraagd").length },
    { label: "Coaching actief", value: members.filter((m) => m.coachingStatus === "actief").length },
    { label: "Open aanvragen", value: contacts.filter((c) => !c.handled).length },
  ];

  return (
    <div className="container-site py-10 lg:py-14">
      <Link href="/account" className="text-sm font-semibold text-muted hover:text-ink">
        ← Naar mijn dashboard
      </Link>
      <div className="mt-3 flex flex-wrap items-end justify-between gap-4">
        <h1 className="display display-lg">Beheer</h1>
        <Link href="/admin/agenda" className="btn btn-primary">
          <CalendarDays className="size-4" aria-hidden="true" /> Agenda &amp; beschikbaarheid
        </Link>
      </div>
      <dl className="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        {stats.map((s) => (
          <div key={s.label} className="card p-5">
            <dt className="text-sm text-muted">{s.label}</dt>
            <dd className="mt-1 text-3xl font-semibold tabular-nums">{s.value}</dd>
          </div>
        ))}
      </dl>

      <section className="mt-12" aria-labelledby="eerstvolgend">
        <div className="flex items-center justify-between gap-3">
          <h2 id="eerstvolgend" className="display display-sm">
            Eerstvolgende afspraken
          </h2>
          <Link href="/admin/agenda" className="text-sm font-semibold underline decoration-accent underline-offset-4">
            Hele agenda
          </Link>
        </div>
        {nextAppointments.length === 0 ? (
          <p className="mt-4 text-muted">Geen geplande afspraken.</p>
        ) : (
          <ul className="mt-4 divide-y divide-line rounded-lg border border-line bg-white text-sm">
            {nextAppointments.map(({ a, firstName, lastName }) => (
              <li key={a.id} className="flex flex-wrap justify-between gap-2 px-4 py-3">
                <span className="font-medium first-letter:uppercase">
                  {formatDayLong(a.startsAt)}, {formatTime(a.startsAt)}
                </span>
                <span className="text-muted">
                  {getAppointmentType(a.type)?.label} · {firstName} {lastName}
                </span>
              </li>
            ))}
          </ul>
        )}
      </section>

      {rewardsDue.length > 0 && (
        <section className="mt-12" aria-labelledby="vriendenkorting">
          <h2 id="vriendenkorting" className="display display-sm">
            Vriendenkorting te verrekenen
          </h2>
          <p className="mt-1 text-sm text-muted">Deze vrienden zijn gestart. De uitnodiger krijgt {REFERRAL.referrerReward}.</p>
          <ul className="mt-4 grid gap-3 md:grid-cols-2">
            {rewardsDue.map((r) => (
              <li key={r.id} className="card flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                <span>
                  <span className="block font-semibold">
                    {r.referrerFirst} {r.referrerLast}
                  </span>
                  <span className="block text-muted">
                    bracht {r.firstName} {r.lastName} aan · {r.referrerEmail}
                  </span>
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
        </section>
      )}

      <section className="mt-12" aria-labelledby="schemas">
        <h2 id="schemas" className="display display-sm">
          Schema&apos;s ter controle
        </h2>
        {openPlans.length === 0 ? (
          <p className="mt-4 text-muted">Geen schema&apos;s die op je wachten.</p>
        ) : (
          <ul className="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            {openPlans.map((p) => {
              const status = isStuck(p.status, p.updatedAt) ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[p.status];
              return (
                <li key={p.id}>
                  <Link href={`/admin/schemas/${p.id}`} className="card flex items-center justify-between gap-3 p-5 transition-colors hover:border-ink/40">
                    <span>
                      <span className="block font-semibold">
                        {p.firstName} {p.lastName}
                      </span>
                      <span className="block text-sm text-muted">
                        {PLAN_TYPE_LABEL[p.type]} · {dateFmt.format(p.createdAt)}
                      </span>
                    </span>
                    <span className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${status.tone}`}>{status.label}</span>
                  </Link>
                </li>
              );
            })}
          </ul>
        )}
      </section>

      <section className="mt-12" aria-labelledby="aanvragen">
        <h2 id="aanvragen" className="display display-sm">
          Contactaanvragen
        </h2>
        {contacts.length === 0 ? (
          <p className="mt-4 text-muted">Nog geen aanvragen.</p>
        ) : (
          <ul className="mt-5 grid gap-3 lg:grid-cols-2">
            {contacts.map((c) => (
              <li key={c.id} className={`card p-5 ${c.handled ? "opacity-60" : ""}`}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold">{c.name}</p>
                    <p className="text-sm text-muted">
                      <a href={`mailto:${c.email}`} className="underline">
                        {c.email}
                      </a>
                      {c.phone && (
                        <>
                          {" · "}
                          <a href={`tel:${c.phone}`} className="underline">
                            {c.phone}
                          </a>
                        </>
                      )}
                    </p>
                  </div>
                  <span className="rounded-full bg-surface px-3 py-1 text-xs font-semibold">{INTERESTS.find((i) => i.id === c.interest)?.label ?? c.interest}</span>
                </div>
                {c.message && <p className="mt-3 whitespace-pre-line text-sm">{c.message}</p>}
                <div className="mt-4 flex items-center justify-between text-xs text-muted">
                  <span>{dateFmt.format(c.createdAt)}</span>
                  <form action={toggleContactHandledAction}>
                    <input type="hidden" name="id" value={c.id} />
                    <button type="submit" className="btn btn-sm btn-outline">
                      {c.handled ? "Markeer als open" : "Markeer als afgehandeld"}
                    </button>
                  </form>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="mt-12" aria-labelledby="leden">
        <h2 id="leden" className="display display-sm">
          Leden
        </h2>
        <p className="mt-2 text-sm text-muted">
          Zet de status op <strong>actief</strong> zodra iemand betaald start. Kwam het lid via een vriend, dan verschijnt de vriendenkorting bovenaan om te verrekenen.
        </p>
        {members.length === 0 ? (
          <p className="mt-4 text-muted">Nog geen leden.</p>
        ) : (
          <ul className="mt-5 grid gap-3">
            {members.map((m) => (
              <li key={m.id} className="card p-5">
                <details>
                  <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                    <span>
                      <span className="block font-semibold">
                        {m.firstName} {m.lastName}
                      </span>
                      <span className="block text-sm text-muted">
                        {m.email}
                        {m.phone ? ` · ${m.phone}` : ""}
                      </span>
                    </span>
                    <span className="flex flex-wrap items-center gap-2 text-xs">
                      {m.plan && <span className="rounded-full bg-surface px-3 py-1 font-semibold">{getOnlinePlan(m.plan)?.name}</span>}
                      <span className={`rounded-full px-3 py-1 font-semibold ${m.coachingStatus === "aangevraagd" ? "bg-accent-tint" : m.coachingStatus === "actief" ? "bg-ink text-white" : "bg-surface"}`}>
                        {m.coachingStatus}
                      </span>
                                          </span>
                  </summary>
                  <div className="mt-5 grid gap-6 border-t border-line pt-5 lg:grid-cols-[1.4fr_1fr]">
                    <form action={updateMemberAction} className="grid gap-3">
                      <input type="hidden" name="userId" value={m.id} />
                      <p className="text-sm text-muted">
                        <Link href={`/admin/leden/${m.id}`} className="font-semibold text-ink underline decoration-accent underline-offset-4">
                          Intake &amp; schema&apos;s
                        </Link>{" "}
                        · Doel: {GOALS.find((g) => g.id === m.goal)?.label ?? "–"} · Lid sinds {dateFmt.format(m.createdAt)}
                        {m.referrerName ? ` · Uitgenodigd door ${m.referrerName}` : ""}
                      </p>
                      <label className="label" htmlFor={`status-${m.id}`}>
                        Coachingstatus
                      </label>
                      <select id={`status-${m.id}`} name="coachingStatus" defaultValue={m.coachingStatus} className="input">
                        {COACHING_STATUSES.map((s) => (
                          <option key={s} value={s}>
                            {s}
                          </option>
                        ))}
                      </select>
                      <label className="label" htmlFor={`note-${m.id}`}>
                        Bericht in dashboard
                      </label>
                      <textarea id={`note-${m.id}`} name="coachNote" defaultValue={m.coachNote ?? ""} className="input min-h-24" placeholder="Bijv. feedback op de laatste check-in" />
                      <button type="submit" className="btn btn-sm btn-ink justify-self-start">
                        Opslaan
                      </button>
                    </form>
                    <div className="grid content-start gap-3 text-sm">
                      <p className="label">Snel naar</p>
                      <Link href={`/admin/leden/${m.id}`} className="btn btn-sm btn-outline justify-self-start">
                        Intake, schema&apos;s en metingen
                      </Link>
                    </div>
                  </div>
                </details>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
