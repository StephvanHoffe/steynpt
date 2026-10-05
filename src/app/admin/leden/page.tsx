import { and, asc, eq, gt, max } from "drizzle-orm";
import { Search } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader, COACHING_LABEL, CoachingBadge, EmptyState } from "@/components/admin/ui";
import { formatDayShort, formatTime } from "@/lib/agenda";
import { requireAdmin } from "@/lib/auth";
import { appointments, checkIns, COACHING_STATUSES, type CoachingStatus, db, users } from "@/lib/db";
import { onlinePlanNames } from "@/lib/content/texts";

export const metadata: Metadata = { title: "Leden" };

const sinceFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric", timeZone: "Europe/Amsterdam" });
const FILTERS: (CoachingStatus | "alle")[] = ["alle", "aangevraagd", "actief", "gepauzeerd", "gestopt", "geen"];

export default async function MembersPage({ searchParams }: PageProps<"/admin/leden">) {
  await requireAdmin("/admin/leden");
  const sp = await searchParams;
  const q = typeof sp.q === "string" ? sp.q.trim() : "";
  const status = (COACHING_STATUSES as readonly string[]).includes(String(sp.status)) ? (sp.status as CoachingStatus) : "alle";
  const now = new Date();

  const [planName, all, next, lastCheckIn] = await Promise.all([
    onlinePlanNames(),
    db.select().from(users).where(eq(users.role, "member")).orderBy(asc(users.firstName), asc(users.lastName)),
    db
      .select({ userId: appointments.userId, startsAt: appointments.startsAt })
      .from(appointments)
      .where(and(eq(appointments.status, "gepland"), gt(appointments.startsAt, now)))
      .orderBy(asc(appointments.startsAt)),
    db.select({ userId: checkIns.userId, week: max(checkIns.week) }).from(checkIns).groupBy(checkIns.userId),
  ]);
  const nextBy = new Map<string, Date>();
  for (const a of next) if (!nextBy.has(a.userId)) nextBy.set(a.userId, a.startsAt);
  const checkBy = new Map(lastCheckIn.map((c) => [c.userId, c.week]));

  const needle = q.toLowerCase();
  const matchesSearch = (u: (typeof all)[number]) =>
    !needle || `${u.firstName} ${u.lastName} ${u.email} ${u.phone ?? ""}`.toLowerCase().includes(needle);
  const searched = all.filter(matchesSearch);
  const members = status === "alle" ? searched : searched.filter((u) => u.coachingStatus === status);
  const countFor = (f: (typeof FILTERS)[number]) => (f === "alle" ? searched.length : searched.filter((u) => u.coachingStatus === f).length);
  const href = (f: (typeof FILTERS)[number]) => {
    const p = new URLSearchParams();
    if (q) p.set("q", q);
    if (f !== "alle") p.set("status", f);
    const s = p.toString();
    return `/admin/leden${s ? `?${s}` : ""}`;
  };

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader title="Leden" description={`${all.length} ${all.length === 1 ? "lid" : "leden"}`} />

      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <nav aria-label="Filter op coaching" className="flex flex-wrap gap-1.5">
          {FILTERS.map((f) => (
            <Link
              key={f}
              href={href(f)}
              aria-current={f === status ? "page" : undefined}
              className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium ${
                f === status ? "border-ink bg-ink text-white" : "border-line bg-white hover:border-ink"
              }`}
            >
              {f === "alle" ? "Alle" : COACHING_LABEL[f]}
              <span className={`tabular-nums ${f === status ? "text-white/70" : "text-muted"}`}>{countFor(f)}</span>
            </Link>
          ))}
        </nav>
        <form action="/admin/leden" method="get" className="relative w-full sm:w-72">
          {status !== "alle" && <input type="hidden" name="status" value={status} />}
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" aria-hidden="true" />
          <label htmlFor="zoek" className="sr-only">
            Zoek op naam, e-mail of telefoon
          </label>
          <input id="zoek" name="q" defaultValue={q} placeholder="Zoek op naam, e-mail of telefoon" className="input h-10 pl-9" />
        </form>
      </div>

      {members.length === 0 ? (
        <EmptyState>{q ? `Geen leden gevonden voor “${q}”.` : "Geen leden in deze groep."}</EmptyState>
      ) : (
        <div className="overflow-hidden rounded-xl border border-line bg-white">
          {/* Tabel op grotere schermen */}
          <table className="hidden w-full text-left text-sm md:table">
            <caption className="sr-only">Leden</caption>
            <thead className="border-b border-line bg-[#f6f7f8] text-xs uppercase tracking-wide text-muted">
              <tr>
                <th className="px-4 py-2.5 font-semibold">Naam</th>
                <th className="px-4 py-2.5 font-semibold">Coaching</th>
                <th className="px-4 py-2.5 font-semibold">Volgende afspraak</th>
                <th className="px-4 py-2.5 font-semibold">Laatste check-in</th>
                <th className="px-4 py-2.5 font-semibold">Lid sinds</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-line">
              {members.map((m) => {
                const nextAt = nextBy.get(m.id);
                return (
                  <tr key={m.id} className="relative hover:bg-surface">
                    <td className="px-4 py-3">
                      <Link href={`/admin/leden/${m.id}`} className="font-semibold after:absolute after:inset-0">
                        {m.firstName} {m.lastName}
                      </Link>
                      <span className="block text-muted">
                        {m.email}
                        {m.phone ? ` · ${m.phone}` : ""}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className="flex flex-wrap items-center gap-2">
                        <CoachingBadge status={m.coachingStatus} />
                        {m.plan && <span className="text-muted">{planName(m.plan)}</span>}
                      </span>
                    </td>
                    <td className="px-4 py-3 tabular-nums">{nextAt ? `${formatDayShort(nextAt)}, ${formatTime(nextAt)}` : <span className="text-muted">–</span>}</td>
                    <td className="px-4 py-3 tabular-nums">{checkBy.get(m.id)?.replace(/^(\d{4})-W(\d+)$/, "week $2") ?? <span className="text-muted">–</span>}</td>
                    <td className="px-4 py-3 text-muted">{sinceFmt.format(m.createdAt)}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {/* Kaarten op de telefoon */}
          <ul className="divide-y divide-line md:hidden">
            {members.map((m) => {
              const nextAt = nextBy.get(m.id);
              return (
                <li key={m.id}>
                  <Link href={`/admin/leden/${m.id}`} className="block px-4 py-3 hover:bg-surface">
                    <span className="flex items-center justify-between gap-3">
                      <span className="font-semibold">
                        {m.firstName} {m.lastName}
                      </span>
                      <CoachingBadge status={m.coachingStatus} />
                    </span>
                    <span className="mt-0.5 block truncate text-sm text-muted">{m.email}</span>
                    {nextAt && (
                      <span className="mt-0.5 block text-sm">
                        Volgende afspraak: {formatDayShort(nextAt)}, {formatTime(nextAt)}
                      </span>
                    )}
                  </Link>
                </li>
              );
            })}
          </ul>
        </div>
      )}
    </div>
  );
}
