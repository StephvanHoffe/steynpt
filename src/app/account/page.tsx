import { desc, eq } from "drizzle-orm";
import { CalendarCheck, CircleCheck, Circle, Flame, Gift, MessageSquareQuote, PartyPopper, Users } from "lucide-react";
import Link from "next/link";
import { CheckInForm } from "@/components/account/CheckInForm";
import { RedeemButton } from "@/components/account/RedeemButton";
import { ReferralShare } from "@/components/account/ReferralShare";
import { RequestCoachingForm } from "@/components/account/RequestCoachingForm";
import { requireUser } from "@/lib/auth";
import { checkIns, db, redemptions, users, type CoachingStatus } from "@/lib/db";
import { checkInStreak, getReward, getTierProgress, isoWeekKey, POINTS, REWARDS } from "@/lib/loyalty";
import { getPointsHistory, getPointsSummary } from "@/lib/points";
import { getOnlinePlan, GOALS, SITE } from "@/lib/site";

const STATUS: Record<CoachingStatus, { label: string; tone: string }> = {
  geen: { label: "Nog niet gestart", tone: "bg-sand text-ink" },
  aangevraagd: { label: "Aanvraag ontvangen", tone: "bg-volt text-ink" },
  actief: { label: "Actief", tone: "bg-ink text-volt" },
  gepauzeerd: { label: "Gepauzeerd", tone: "bg-sand text-ink" },
  gestopt: { label: "Gestopt", tone: "bg-sand text-muted" },
};

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short" });

export default async function DashboardPage({ searchParams }: PageProps<"/account">) {
  const user = await requireUser();
  const { welkom } = await searchParams;

  const [points, history, myCheckIns, friends, myRedemptions] = await Promise.all([
    getPointsSummary(user.id),
    getPointsHistory(user.id, 12),
    db.select().from(checkIns).where(eq(checkIns.userId, user.id)).orderBy(desc(checkIns.week)).limit(52),
    db
      .select({ firstName: users.firstName, coachingStatus: users.coachingStatus, createdAt: users.createdAt })
      .from(users)
      .where(eq(users.referredById, user.id))
      .orderBy(desc(users.createdAt)),
    db.select().from(redemptions).where(eq(redemptions.userId, user.id)).orderBy(desc(redemptions.createdAt)).limit(10),
  ]);

  const tier = getTierProgress(points.lifetime);
  const week = isoWeekKey(new Date());
  const checkedInThisWeek = myCheckIns.some((c) => c.week === week);
  const streak = checkInStreak(myCheckIns.map((c) => c.week));
  const toNextStreakBonus = POINTS.streakLength - (streak % POINTS.streakLength);
  const plan = getOnlinePlan(user.plan);
  const goal = GOALS.find((g) => g.id === user.goal)?.label;
  const referralUrl = `${SITE.url}/r/${user.referralCode}`;
  const friendsStarted = friends.filter((f) => f.coachingStatus === "actief").length;
  const status = STATUS[user.coachingStatus];

  return (
    <>
      <section className="grain bg-ink text-paper">
        <div className="container-site py-10 lg:py-14">
          {welkom && (
            <div className="mb-8 flex items-start gap-3 rounded-2xl bg-volt p-4 text-ink sm:items-center">
              <PartyPopper className="size-6 shrink-0" aria-hidden="true" />
              <p className="font-medium">
                Welkom bij SteynPT, {user.firstName}! Je welkomstpunten staan klaar.
                {user.coachingStatus === "aangevraagd" && " Steyn neemt binnen 24 uur contact met je op voor je intake."}
              </p>
            </div>
          )}
          <div className="grid gap-8 lg:grid-cols-[1.3fr_1fr] lg:items-end">
            <div>
              <p className="eyebrow text-volt">Mijn SteynPT</p>
              <h1 className="display display-lg mt-3">Hoi {user.firstName}!</h1>
              <p className="mt-3 text-mist">
                {goal ? `Doel: ${goal}` : "Stel je doel in via je profiel"}
                {plan ? ` · Online coaching ${plan.name}` : ""}
              </p>
            </div>
            <div className="card-dark p-6">
              <div className="flex items-start justify-between gap-4">
                <div>
                  <p className="text-sm text-mist">Je punten</p>
                  <p className="display mt-1 text-6xl text-volt">{points.balance}</p>
                </div>
                <span className="rounded-full bg-volt px-3 py-1 text-xs font-bold uppercase tracking-wider text-ink">{tier.current.name}</span>
              </div>
              <div
                className="mt-5 h-2 overflow-hidden rounded-full bg-ink-3"
                role="progressbar"
                aria-label={tier.next ? `Voortgang naar ${tier.next.name}` : "Hoogste niveau bereikt"}
                aria-valuenow={Math.round(tier.progress * 100)}
                aria-valuemin={0}
                aria-valuemax={100}
              >
                <div className="h-full rounded-full bg-volt" style={{ width: `${Math.max(4, tier.progress * 100)}%` }} />
              </div>
              <p className="mt-2 text-xs text-mist">
                {tier.next ? `Nog ${tier.pointsToNext} punten tot ${tier.next.name}` : "Je hebt het hoogste niveau bereikt!"} · {points.lifetime} punten verdiend in totaal
              </p>
            </div>
          </div>
        </div>
      </section>

      <div className="container-site grid gap-6 py-10 lg:grid-cols-[1.6fr_1fr] lg:py-14">
        <div className="grid content-start gap-6">
          {/* Coaching */}
          <section className="card p-6 sm:p-8" aria-labelledby="coaching-title">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <h2 id="coaching-title" className="display text-3xl">
                Online coaching
              </h2>
              <span className={`rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider ${status.tone}`}>{status.label}</span>
            </div>

            {(user.coachingStatus === "geen" || user.coachingStatus === "gestopt") && (
              <div className="mt-5">
                <p className="text-muted">
                  {user.coachingStatus === "geen"
                    ? "Klaar voor de volgende stap? Kies je pakket en Steyn neemt binnen 24 uur contact met je op voor een intake. Je betaalt pas als je na de intake start."
                    : "Zin om weer te beginnen? Kies je pakket, dan plannen we een nieuwe intake."}
                </p>
                <div className="mt-5">
                  <RequestCoachingForm currentPlan={user.plan} />
                </div>
                <Link href="/online-coaching#pakketten" className="mt-3 inline-block text-sm font-semibold underline decoration-volt-deep underline-offset-4">
                  Vergelijk de pakketten
                </Link>
              </div>
            )}

            {user.coachingStatus === "aangevraagd" && (
              <div className="mt-5">
                <p className="text-muted">
                  Je aanvraag voor <strong className="text-ink">{plan?.name ?? "online coaching"}</strong> is binnen. Dit zijn de volgende stappen:
                </p>
                <ol className="mt-5 space-y-3">
                  {[
                    { done: true, text: "Account aangemaakt" },
                    { done: true, text: "Pakket gekozen" },
                    { done: false, text: "Intake met Steyn (je hoort binnen 24 uur van ons)" },
                    { done: false, text: "Je persoonlijke plan staat klaar" },
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
                Je volgt <strong className="text-ink">Online coaching {plan?.name}</strong>. Check elke week in, zodat Steyn je plan kan bijsturen.
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
              <div className="mt-6 flex gap-3 rounded-xl border border-volt-deep/40 bg-volt/15 p-4">
                <MessageSquareQuote className="size-5 shrink-0" aria-hidden="true" />
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
              <h2 id="checkin-title" className="display flex items-center gap-2 text-3xl">
                <CalendarCheck className="size-7" aria-hidden="true" /> Wekelijkse check-in
              </h2>
              <span className="inline-flex items-center gap-1.5 rounded-full bg-sand px-3 py-1 text-sm font-semibold">
                <Flame className="size-4" aria-hidden="true" /> {streak} {streak === 1 ? "week" : "weken"} streak
              </span>
            </div>
            <p className="mt-2 text-sm text-muted">
              Week {week.split("-W")[1]} ·{" "}
              {checkedInThisWeek
                ? "Je hebt deze week al ingecheckt. Top!"
                : `Check in en verdien ${POINTS.weeklyCheckIn} punten. Nog ${toNextStreakBonus} ${toNextStreakBonus === 1 ? "week" : "weken"} tot je streakbonus van ${POINTS.streakBonus}.`}
            </p>
            <div className="mt-6 empty:hidden">
              <CheckInForm done={checkedInThisWeek} />
            </div>

            {myCheckIns.length > 0 && (
              <div className="mt-8 overflow-x-auto">
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

          {/* Beloningen */}
          <section className="card p-6 sm:p-8" aria-labelledby="rewards-title">
            <h2 id="rewards-title" className="display flex items-center gap-2 text-3xl">
              <Gift className="size-7" aria-hidden="true" /> Beloningen
            </h2>
            <p className="mt-2 text-sm text-muted">
              Je hebt <strong className="text-ink">{points.balance} punten</strong> te besteden.
            </p>
            <ul className="mt-6 grid gap-3 sm:grid-cols-2">
              {REWARDS.map((r) => {
                const affordable = points.balance >= r.cost;
                return (
                  <li key={r.id} className={`flex flex-col rounded-xl border p-4 ${affordable ? "border-ink" : "border-line"}`}>
                    <div className="flex items-start justify-between gap-3">
                      <p className="font-semibold">{r.title}</p>
                      <p className="display shrink-0 text-xl">{r.cost}</p>
                    </div>
                    <p className="mt-1 flex-1 text-sm text-muted">{r.description}</p>
                    {!affordable && (
                      <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-sand" aria-hidden="true">
                        <div className="h-full rounded-full bg-volt-deep" style={{ width: `${Math.min(100, (points.balance / r.cost) * 100)}%` }} />
                      </div>
                    )}
                    <div className="mt-3">
                      <RedeemButton rewardId={r.id} title={r.title} cost={r.cost} affordable={affordable} />
                    </div>
                  </li>
                );
              })}
            </ul>
            {myRedemptions.length > 0 && (
              <div className="mt-6 border-t border-line pt-5">
                <h3 className="text-sm font-semibold">Mijn inwisselingen</h3>
                <ul className="mt-3 space-y-2 text-sm">
                  {myRedemptions.map((r) => (
                    <li key={r.id} className="flex justify-between gap-3">
                      <span>
                        {getReward(r.rewardId)?.title ?? r.rewardId} · {dateFmt.format(r.createdAt)}
                      </span>
                      <span className="text-muted">{r.status}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </section>
        </div>

        <div className="grid content-start gap-6">
          {/* Vrienden */}
          <section className="rounded-[1.25rem] bg-volt p-6 text-ink sm:p-8" aria-labelledby="friends-title">
            <h2 id="friends-title" className="display flex items-center gap-2 text-3xl">
              <Users className="size-7" aria-hidden="true" /> Nodig vrienden uit
            </h2>
            <p className="mt-2 text-sm text-ink/80">
              +{POINTS.friendSignup} punten als je vriend een account maakt, +{POINTS.friendStarts} als je vriend start. Je vriend krijgt {POINTS.invitedBonus} extra welkomstpunten.
            </p>
            <div className="mt-5">
              <ReferralShare url={referralUrl} code={user.referralCode} firstName={user.firstName} />
            </div>
            <dl className="mt-6 grid grid-cols-2 gap-3">
              <div className="rounded-xl bg-ink/5 p-3">
                <dt className="text-xs text-ink/70">Aangemeld</dt>
                <dd className="display text-3xl">{friends.length}</dd>
              </div>
              <div className="rounded-xl bg-ink/5 p-3">
                <dt className="text-xs text-ink/70">Gestart</dt>
                <dd className="display text-3xl">{friendsStarted}</dd>
              </div>
            </dl>
            {friends.length > 0 && (
              <ul className="mt-4 space-y-1.5 text-sm">
                {friends.map((f, i) => (
                  <li key={i} className="flex justify-between">
                    <span>{f.firstName}</span>
                    <span className="text-ink/70">{f.coachingStatus === "actief" ? "Gestart" : "Aangemeld"}</span>
                  </li>
                ))}
              </ul>
            )}
          </section>

          {/* Punten historie */}
          <section className="card p-6 sm:p-8" aria-labelledby="history-title">
            <h2 id="history-title" className="display text-3xl">
              Puntenhistorie
            </h2>
            {history.length === 0 ? (
              <p className="mt-3 text-sm text-muted">Nog geen punten.</p>
            ) : (
              <ul className="mt-5 divide-y divide-line text-sm">
                {history.map((t) => (
                  <li key={t.id} className="flex items-center justify-between gap-3 py-2.5">
                    <span>
                      <span className="block">{t.description}</span>
                      <span className="block text-xs text-muted">{dateFmt.format(t.createdAt)}</span>
                    </span>
                    <span className={`shrink-0 font-semibold ${t.amount > 0 ? "text-success" : "text-muted"}`}>
                      {t.amount > 0 ? "+" : ""}
                      {t.amount}
                    </span>
                  </li>
                ))}
              </ul>
            )}
            <Link href="/rewards" className="mt-5 inline-block text-sm font-semibold underline decoration-volt-deep underline-offset-4">
              Hoe verdien ik punten?
            </Link>
          </section>

          <section className="card p-6 sm:p-8">
            <h2 className="display text-2xl">Niveau {tier.current.name}</h2>
            <ul className="mt-4 space-y-2 text-sm text-muted">
              {tier.current.perks.map((p) => (
                <li key={p} className="flex gap-2">
                  <span className="mt-2 size-1.5 shrink-0 rotate-45 bg-ink" />
                  {p}
                </li>
              ))}
            </ul>
          </section>
        </div>
      </div>
    </>
  );
}
