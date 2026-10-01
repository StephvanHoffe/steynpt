import { desc, eq, inArray } from "drizzle-orm";
import type { Metadata } from "next";
import Link from "next/link";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { ADMIN_PAGE, AdminPageHeader, EmptyState } from "@/components/admin/ui";
import { requireAdmin } from "@/lib/auth";
import { db, plans, users } from "@/lib/db";

export const metadata: Metadata = { title: "Schema's" };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit", timeZone: "Europe/Amsterdam" });

export default async function PlansPage() {
  await requireAdmin("/admin/schemas");
  const select = { id: plans.id, type: plans.type, status: plans.status, source: plans.source, createdAt: plans.createdAt, updatedAt: plans.updatedAt, publishedAt: plans.publishedAt, userId: users.id, firstName: users.firstName, lastName: users.lastName };
  const [open, published] = await Promise.all([
    db.select(select).from(plans).innerJoin(users, eq(plans.userId, users.id)).where(inArray(plans.status, ["genereren", "concept", "fout"])).orderBy(plans.createdAt),
    db.select(select).from(plans).innerJoin(users, eq(plans.userId, users.id)).where(eq(plans.status, "gepubliceerd")).orderBy(desc(plans.publishedAt)).limit(20),
  ]);

  const List = ({ rows, empty, date }: { rows: typeof open; empty: string; date: "createdAt" | "publishedAt" }) =>
    rows.length === 0 ? (
      <EmptyState>{empty}</EmptyState>
    ) : (
      <ul className="divide-y divide-line overflow-hidden rounded-xl border border-line bg-white">
        {rows.map((p) => {
          const status = isStuck(p.status, p.updatedAt) ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[p.status];
          const when = p[date];
          return (
            <li key={p.id}>
              <Link href={`/admin/schemas/${p.id}`} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 hover:bg-surface">
                <span>
                  <span className="block font-semibold">
                    {p.firstName} {p.lastName}
                  </span>
                  <span className="block text-sm text-muted">
                    {PLAN_TYPE_LABEL[p.type]} · {p.source === "ai" ? "AI-concept" : "handmatig"}
                    {when ? ` · ${dateFmt.format(when)}` : ""}
                  </span>
                </span>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${status.tone}`}>{status.label}</span>
              </Link>
            </li>
          );
        })}
      </ul>
    );

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        title="Schema's"
        description="AI-concepten controleer en publiceer je hier. Een nieuw schema maak je vanaf de pagina van het lid."
      />
      <div className="grid gap-8 xl:grid-cols-2">
        <section aria-labelledby="open">
          <h2 id="open" className="mb-3 text-lg font-semibold">
            Te controleren <span className="text-muted">({open.length})</span>
          </h2>
          <List rows={open} empty="Geen schema's die op je wachten." date="createdAt" />
        </section>
        <section aria-labelledby="gepubliceerd">
          <h2 id="gepubliceerd" className="mb-3 text-lg font-semibold">
            Recent gepubliceerd
          </h2>
          <List rows={published} empty="Nog niets gepubliceerd." date="publishedAt" />
        </section>
      </div>
    </div>
  );
}
