import { and, asc, desc, eq, gt, ne } from "drizzle-orm";
import { ArrowRight, CalendarCheck, CalendarDays, Circle, CircleCheck, Flame, LineChart, MessageSquareQuote, PartyPopper, Users } from "lucide-react";
import Link from "next/link";
import { AppointmentList } from "@/components/agenda/AppointmentList";
import { CheckInForm } from "@/components/account/CheckInForm";
import { MyPlansCard } from "@/components/account/MyPlansCard";
import { ReferralShare } from "@/components/account/ReferralShare";
import { RequestCoachingForm } from "@/components/account/RequestCoachingForm";
import { ProgressOverview } from "@/components/progress/ProgressOverview";
import { formatDayLong, formatTime, getAgendaLocation, getAppointmentType } from "@/lib/agenda";
import { requireUser } from "@/lib/auth";
import { appointments, checkIns, db, intakes, measurements, plans, users, type CoachingStatus } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { siteOrigin } from "@/lib/origin";
import { algemeen } from "@/lib/content/registry";
import { getOnlinePlans, getTexts } from "@/lib/content/texts";
import { GOALS } from "@/lib/site";
import { checkInStreak, isoWeekKey } from "@/lib/weeks";
import { activateDuePlans } from "@/lib/plans/schedule";

const STATUS: Record<CoachingStatus, { label: string; tone: string }> = {
  geen: { label: "Nog niet gestart", tone: "bg-surface text-ink" },
  aangevraagd: { label: "Aanvraag ontvangen", tone: "bg-accent-tint text-accent" },
  actief: { label: "Actief", tone: "bg-ink text-white" },
  gepauzeerd: { label: "Gepauzeerd", tone: "bg-surface text-ink" },
  gestopt: { label: "Gestopt", tone: "bg-surface text-muted" },
};

export default async function DashboardPage({ searchParams }: PageProps<"/account">) {
  const user = await requireUser();
  const { welkom, intake: intakeParam } = await searchParams;
  const now = new Date();
  // Ingeplande schema's waarvan de startdag is aangebroken, worden nu zichtbaar.
  await activateDuePlans();

  const [myCheckIns, friends, intakeRows, myPlans, upcoming, myMeasurements] = await Promise.all([
    db.select().from(checkIns).where(eq(checkIns.userId, user.id)).orderBy(desc(checkIns.week)).limit(52),
    db
      .select({ firstName: users.firstName, coachingStatus: users.coachingStatus, referralRewardAt: users.referralRewardAt })
      .from(users)
      .where(eq(users.referredById, user.id))
      .orderBy(desc(users.createdAt)),
    db.select().from(intakes).where(eq(intakes.userId, user.id)),
    db
      .select({ id: plans.id, type: plans.type, status: plans.status, publishedAt: plans.publishedAt, startsOn: plans.startsOn })
      .from(plans)
      .where(and(eq(plans.userId, user.id), ne(plans.status, "vervangen")))
      .orderBy(desc(plans.createdAt)),
    db
      .select()
      .from(appointments)
      .where(and(eq(appointments.userId, user.id), eq(appointments.status, "gepland"), gt(appointments.endsAt, now)))
      .orderBy(asc(appointments.startsAt)),
    db.select().from(measurements).where(eq(measurements.userId, user.id)).orderBy(asc(measurements.measuredAt)),
  ]);

  const intake = intakeSchema.safeParse(intakeRows[0]?.data);
  const week = isoWeekKey(now);
  const checkedInThisWeek = myCheckIns.some((c) => c.week === week);
  const streak = checkInStreak(myCheckIns.map((c) => c.week));
  const [onlinePlans, { vriendenactie }] = await Promise.all([getOnlinePlans(), getTexts(algemeen)]);
  const plan = onlinePlans.find((p) => p.id === user.plan);
  const goal = GOALS.find((g) => g.id === user.goal)?.label;
  const referralUrl = `${await siteOrigin()}/r/${user.referralCode}`;
  const status = STATUS[user.coachingStatus];
  const next = upcoming[0];

  return (
    <>
      <section className="hero-soft">
        <div className="container-site py-10 lg:py-12">
          {(welkom || intakeParam) && (
            <div role="status" className="mb-8 flex items-start gap-3 rounded-lg border border-accent/30 bg-accent-tint p-4 sm:items-center">
              <PartyPopper className="size-5 shrink-0 text-accent" aria-hidden="true" />
              <p className="text-sm font-medium">
                {welkom && `Welkom bij SteynPT, ${user.firstName}!`}
                {welkom && user.coachingStatus === "aangevraagd" && " Steyn neemt binnen 24 uur contact met je op voor je intake."}
                {intakeParam &&
                  (intakeParam === "gestart"
                    ? "Je intake is opgeslagen. We maken nu een eerste opzet van je schema; Steyn controleert het en laat het je weten zodra het klaarstaat."
                    : "Je intake is opgeslagen. Steyn gebruikt je gegevens voor je schema.")}
              </p>
            </div>
          )}
          <div className="grid gap-8 lg:grid-cols-[1.3fr_1fr] lg:items-end">
            <div>
              <p className="eyebrow text-accent">Mijn omgeving</p>
              <h1 className="display display-lg mt-3">Hoi {user.firstName}</h1>
              <p className="mt-3 text-muted">
                {goal ? `Doel: ${goal}` : "Stel je doel in via je intake"}
                {plan ? ` · online coaching ${plan.name}` : ""}
              </p>
            </div>
            <div className="card p-5">
              <p className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-muted">
                <CalendarDays className="size-4" aria-hidden="true" /> Volgende afspraak
              </p>
              {next ? (
                <>
                  <p className="mt-2 text-lg font-semibold first-letter:uppercase">
                    {formatDayLong(next.startsAt)}, {formatTime(next.startsAt)}
                  </p>
                  <p className="text-sm text-muted">
                    {getAppointmentType(next.type)?.label ?? next.type} · {getAgendaLocation(next.location)?.label ?? next.location}
                  </p>
                  <Link href="/account/agenda" className="mt-3 inline-flex items-center gap-1 text-sm font-semibold underline decoration-accent underline-offset-4">
                    Alle afspraken
                  </Link>
                </>
              ) : (
                <>
                  <p className="mt-2 text-sm text-muted">Je hebt nog geen afspraak gepland.</p>
                  <Link href="/account/agenda" className="btn btn-primary btn-sm mt-3">
                    Afspraak maken
                  </Link>
                </>
              )}
            </div>
          </div>
        </div>
      </section>

      <div className="container-site grid gap-6 py-10 lg:grid-cols-[1.6fr_1fr] lg:py-12">
        <div className="grid min-w-0 content-start gap-6">
          <MyPlansCard intake={intake.success ? intake.data : null} plans={myPlans} coachingStatus={user.coachingStatus} />

          {/* Voortgang */}
          <section className="card p-6 sm:p-8" aria-labelledby="progress-title">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 id="progress-title" className="display flex items-center gap-2 text-2xl">
                <LineChart className="size-6" aria-hidden="true" /> Mijn voortgang
              </h2>
              {myMeasurements.length > 0 && (
                <Link href="/account/voortgang" className="text-sm font-semibold underline decoration-accent underline-offset-4">
                  Alle metingen
                </Link>
              )}
            </div>
            {myMeasurements.length === 0 ? (
              <div className="mt-4 rounded-lg bg-surface p-5 text-sm">
                <p>Steyn houdt hier je metingen bij, zoals gewicht, vetpercentage en omvang. Na je eerste meting zie je hier je voortgang.</p>
                <Link href="/account/agenda?type=meting" className="mt-3 inline-flex items-center gap-1 font-semibold underline decoration-accent underline-offset-4">
                  Plan een meting <ArrowRight className="size-4" aria-hidden="true" />
                </Link>
              </div>
            ) : (
              <div className="mt-5">
                <ProgressOverview rows={myMeasurements} charts="main" />
              </div>
            )}
          </section>

          {/* Coaching */}
          <section className="card p-6 sm:p-8" aria-labelledby="coaching-title">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 id="coaching-title" className="display text-2xl">
                Online coaching
              </h2>
              <span className={`rounded px-2.5 py-1 text-xs font-semibold uppercase tracking-wider ${status.tone}`}>{status.label}</span>
            </div>

            {(user.coachingStatus === "geen" || user.coachingStatus === "gestopt") && (
              <div className="mt-5">
                <p className="text-muted">
                  {user.coachingStatus === "geen"
                    ? "Klaar voor de volgende stap? Kies je pakket en Steyn neemt binnen 24 uur contact met je op voor een intake. Je betaalt pas als je na de intake start."
                    : "Zin om weer te beginnen? Kies je pakket, dan plannen we een nieuwe intake."}
                </p>
                <div className="mt-5">
                  <RequestCoachingForm currentPlan={user.plan} plans={onlinePlans.map(({ id, name, price }) => ({ id, name, price }))} />
                </div>
                <Link href="/online-coaching#pakketten" className="mt-3 inline-block text-sm font-semibold underline decoration-accent underline-offset-4">
                  Vergelijk de pakketten
                </Link>
              </div>
            )}

            {user.coachingStatus === "aangevraagd" && (
              <div className="mt-5">
                <p className="text-muted">
                  Je aanvraag voor <strong className="text-ink">online coaching{plan ? ` ${plan.name}` : ""}</strong> is binnen. Dit zijn de volgende stappen:
                </p>
                <ol className="mt-5 space-y-3">
                  {[
                    { done: true, text: "Account aangemaakt" },
                    { done: true, text: "Pakket gekozen" },
                    { done: intake.success, text: "Intake ingevuld" },
                    { done: false, text: "Intakegesprek met Steyn (je hoort binnen 24 uur van ons)" },
                    { done: myPlans.some((p) => p.status === "gepubliceerd"), text: "Je persoonlijke schema staat klaar" },
                  ].map((s) => (
                    <li key={s.text} className="flex items-center gap-3">
                      {s.done ? <CircleCheck className="size-5 text-success" aria-hidden="true" /> : <Circle className="size-5 text-line" aria-hidden="true" />}
                      <span className={s.done ? "" : "text-muted"}>{s.text}</span>
                    </li>
                  ))}
                </ol>
              </div>
            )}

            {user.coachingStatus === "actief" && (
              <p className="mt-5 text-muted">
                Je volgt <strong className="text-ink">online coaching {plan?.name}</strong>. Check elke week in, zodat Steyn je plan kan bijsturen.
              </p>
            )}

            {user.coachingStatus === "gepauzeerd" && (
              <p className="mt-5 text-muted">
                Je coaching staat tijdelijk op pauze. Klaar om verder te gaan?{" "}
                <Link href="/contact?onderwerp=online-coaching" className="font-semibold text-ink underline">
                  Laat het Steyn weten
                </Link>
                .
              </p>
            )}

            {user.coachNote && (
              <div className="mt-6 flex gap-3 rounded-lg border border-accent/30 bg-accent-tint p-4">
                <MessageSquareQuote className="size-5 shrink-0 text-accent" aria-hidden="true" />
                <div>
                  <p className="text-sm font-semibold">Bericht van Steyn</p>
                  <p className="mt-1 whitespace-pre-line text-sm leading-relaxed">{user.coachNote}</p>
                </div>
              </div>
            )}
          </section>

          {/* Check-in */}
          <section className="card p-6 sm:p-8" aria-labelledby="checkin-title">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 id="checkin-title" className="display flex items-center gap-2 text-2xl">
                <CalendarCheck className="size-6" aria-hidden="true" /> Wekelijkse check-in
              </h2>
              {streak > 0 && (
                <span className="inline-flex items-center gap-1.5 rounded bg-surface px-2.5 py-1 text-sm font-medium">
                  <Flame className="size-4 text-accent" aria-hidden="true" /> {streak} {streak === 1 ? "week" : "weken"} op rij
                </span>
              )}
            </div>
            <p className="mt-2 text-sm text-muted">
              Week {week.split("-W")[1]} ·{" "}
              {checkedInThisWeek ? "Je hebt deze week al ingecheckt. Top!" : "Laat Steyn weten hoe je week ging, dan kan hij je plan bijsturen."}
            </p>
            <div className="mt-6 empty:hidden">
              <CheckInForm done={checkedInThisWeek} />
            </div>

            {myCheckIns.length > 0 && (
              <div className="mt-8 relative overflow-x-auto">
                <table className="w-full min-w-[480px] text-left text-sm">
                  <caption className="sr-only">Je laatste check-ins</caption>
                  <thead className="text-xs uppercase tracking-wider text-muted">
                    <tr className="border-b border-line">
                      <th className="py-2 pr-3 font-semibold">Week</th>
                      <th className="py-2 pr-3 font-semibold">Energie</th>
                      <th className="py-2 pr-3 font-semibold">Slaap</th>
                      <th className="py-2 pr-3 font-semibold">Voeding</th>
                      <th className="py-2 pr-3 font-semibold">Trainingen</th>
                      <th className="py-2 font-semibold">Gewicht</th>
                    </tr>
                  </thead>
                  <tbody>
                    {myCheckIns.slice(0, 8).map((c) => (
                      <tr key={c.id} className="border-b border-line/70 last:border-0">
                        <td className="py-2.5 pr-3 font-semibold">{c.week.split("-W")[1]}</td>
                        <td className="py-2.5 pr-3">{c.energy}/5</td>
                        <td className="py-2.5 pr-3">{c.sleep}/5</td>
                        <td className="py-2.5 pr-3">{c.nutrition}/5</td>
                        <td className="py-2.5 pr-3">{c.workouts}×</td>
                        <td className="py-2.5">{c.weight ? `${c.weight.toLocaleString("nl-NL")} kg` : "–"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>
        </div>

        <div className="grid min-w-0 content-start gap-6">
          {/* Agenda */}
          <section className="card p-6" aria-labelledby="agenda-title">
            <div className="flex items-center justify-between gap-3">
              <h2 id="agenda-title" className="display flex items-center gap-2 text-2xl">
                <CalendarDays className="size-6" aria-hidden="true" /> Agenda
              </h2>
              <Link href="/account/agenda" className="btn btn-primary btn-sm">
                Afspraak maken
              </Link>
            </div>
            <div className="mt-4">
              <AppointmentList items={upcoming.slice(0, 3)} now={now} compact />
            </div>
          </section>

          {/* Vrienden */}
          <section className="card p-6" aria-labelledby="friends-title">
            <h2 id="friends-title" className="display flex items-center gap-2 text-2xl">
              <Users className="size-6" aria-hidden="true" /> Vriend uitnodigen
            </h2>
            <p className="mt-2 text-sm text-muted">
              {vriendenactie.headline}: je vriend krijgt {vriendenactie.friendReward}, jij {vriendenactie.referrerReward} zodra je vriend start.
            </p>
            <div className="mt-5">
              <ReferralShare url={referralUrl} code={user.referralCode} firstName={user.firstName} friendReward={vriendenactie.friendReward} />
            </div>
            {friends.length > 0 && (
              <ul className="mt-5 divide-y divide-line border-t border-line text-sm">
                {friends.map((f, i) => (
                  <li key={i} className="flex justify-between gap-3 py-2.5">
                    <span>{f.firstName}</span>
                    <span className="text-muted">
                      {f.referralRewardAt ? "Korting verrekend" : f.coachingStatus === "actief" ? "Gestart · korting volgt" : "Aangemeld"}
                    </span>
                  </li>
                ))}
              </ul>
            )}
            <Link href="/vriend-uitnodigen#voorwaarden" className="mt-4 inline-block text-xs text-muted underline">
              Voorwaarden
            </Link>
          </section>
        </div>
      </div>
    </>
  );
}
