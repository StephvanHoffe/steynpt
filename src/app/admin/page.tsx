import { desc, eq, sql } from "drizzle-orm";
import { alias } from "drizzle-orm/sqlite-core";
import type { Metadata } from "next";
import Link from "next/link";
import { requireAdmin } from "@/lib/auth";
import { adjustPointsAction, handleRedemptionAction, toggleContactHandledAction, updateMemberAction } from "@/lib/actions/admin";
import { COACHING_STATUSES, contactRequests, db, pointTransactions, redemptions, users } from "@/lib/db";
import { getReward } from "@/lib/loyalty";
import { getOnlinePlan, GOALS, INTERESTS } from "@/lib/site";

export const metadata: Metadata = { title: "Beheer", robots: { index: false } };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric" });

export default async function AdminPage() {
  await requireAdmin();
  const referrer = alias(users, "referrer");

  const [members, openRedemptions, contacts] = await Promise.all([
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
        points: sql<number>`coalesce((select sum(${pointTransactions.amount}) from ${pointTransactions} where ${pointTransactions.userId} = ${users.id}), 0)`,
      })
      .from(users)
      .leftJoin(referrer, eq(users.referredById, referrer.id))
      .orderBy(desc(users.createdAt)),
    db
      .select({ id: redemptions.id, rewardId: redemptions.rewardId, cost: redemptions.cost, createdAt: redemptions.createdAt, firstName: users.firstName, lastName: users.lastName, email: users.email })
      .from(redemptions)
      .innerJoin(users, eq(redemptions.userId, users.id))
      .where(eq(redemptions.status, "aangevraagd"))
      .orderBy(desc(redemptions.createdAt)),
    db.select().from(contactRequests).orderBy(contactRequests.handled, desc(contactRequests.createdAt)).limit(100),
  ]);

  const stats = [
    { label: "Leden", value: members.length },
    { label: "Coaching aangevraagd", value: members.filter((m) => m.coachingStatus === "aangevraagd").length },
    { label: "Coaching actief", value: members.filter((m) => m.coachingStatus === "actief").length },
    { label: "Via vrienden", value: members.filter((m) => m.referrerName).length },
    { label: "Open aanvragen", value: contacts.filter((c) => !c.handled).length },
  ];

  return (
    <div className="container-site py-10 lg:py-14">
      <Link href="/account" className="text-sm font-semibold text-muted hover:text-ink">
        ← Naar mijn dashboard
      </Link>
      <h1 className="display display-lg mt-3">Beheer</h1>
      <dl className="mt-8 grid grid-cols-2 gap-3 md:grid-cols-5">
        {stats.map((s) => (
          <div key={s.label} className="card p-5">
            <dt className="text-sm text-muted">{s.label}</dt>
            <dd className="display mt-1 text-4xl">{s.value}</dd>
          </div>
        ))}
      </dl>

      <section className="mt-12" aria-labelledby="aanvragen">
        <h2 id="aanvragen" className="display text-3xl">
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
                  <span className="rounded-full bg-sand px-3 py-1 text-xs font-semibold">{INTERESTS.find((i) => i.id === c.interest)?.label ?? c.interest}</span>
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

      <section className="mt-12" aria-labelledby="inwisselingen">
        <h2 id="inwisselingen" className="display text-3xl">
          Open inwisselingen
        </h2>
        {openRedemptions.length === 0 ? (
          <p className="mt-4 text-muted">Geen openstaande beloningen.</p>
        ) : (
          <ul className="mt-5 grid gap-3">
            {openRedemptions.map((r) => (
              <li key={r.id} className="card flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                  <p className="font-semibold">
                    {getReward(r.rewardId)?.title ?? r.rewardId} · {r.cost} punten
                  </p>
                  <p className="text-sm text-muted">
                    {r.firstName} {r.lastName} ({r.email}) · {dateFmt.format(r.createdAt)}
                  </p>
                </div>
                <div className="flex gap-2">
                  <form action={handleRedemptionAction}>
                    <input type="hidden" name="id" value={r.id} />
                    <input type="hidden" name="status" value="geleverd" />
                    <button type="submit" className="btn btn-sm btn-ink">
                      Geleverd
                    </button>
                  </form>
                  <form action={handleRedemptionAction}>
                    <input type="hidden" name="id" value={r.id} />
                    <input type="hidden" name="status" value="geannuleerd" />
                    <button type="submit" className="btn btn-sm btn-outline">
                      Annuleren &amp; terugboeken
                    </button>
                  </form>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="mt-12" aria-labelledby="leden">
        <h2 id="leden" className="display text-3xl">
          Leden
        </h2>
        <p className="mt-2 text-sm text-muted">
          Zet de status op <strong>actief</strong> zodra iemand betaald start; de uitnodiger krijgt dan automatisch de punten.
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
                      {m.plan && <span className="rounded-full bg-sand px-3 py-1 font-semibold">{getOnlinePlan(m.plan)?.name}</span>}
                      <span className={`rounded-full px-3 py-1 font-semibold ${m.coachingStatus === "aangevraagd" ? "bg-petal" : m.coachingStatus === "actief" ? "bg-rose text-white" : "bg-sand"}`}>
                        {m.coachingStatus}
                      </span>
                      <span className="rounded-full border border-line px-3 py-1 font-semibold">{m.points} pt</span>
                    </span>
                  </summary>
                  <div className="mt-5 grid gap-6 border-t border-line pt-5 lg:grid-cols-[1.4fr_1fr]">
                    <form action={updateMemberAction} className="grid gap-3">
                      <input type="hidden" name="userId" value={m.id} />
                      <p className="text-sm text-muted">
                        Doel: {GOALS.find((g) => g.id === m.goal)?.label ?? "–"} · Lid sinds {dateFmt.format(m.createdAt)}
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
                    <form action={adjustPointsAction} className="grid content-start gap-3">
                      <input type="hidden" name="userId" value={m.id} />
                      <p className="label">Punten corrigeren</p>
                      <input name="amount" type="number" required placeholder="Bijv. 100 of -50" className="input" aria-label="Aantal punten" />
                      <input name="description" required minLength={2} placeholder="Omschrijving" className="input" aria-label="Omschrijving" />
                      <button type="submit" className="btn btn-sm btn-outline justify-self-start">
                        Boeken
                      </button>
                    </form>
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
