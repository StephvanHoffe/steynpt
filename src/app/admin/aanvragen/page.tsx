import { desc, eq } from "drizzle-orm";
import { Mail, Phone } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader, EmptyState } from "@/components/admin/ui";
import { toggleContactHandledAction } from "@/lib/actions/admin";
import { requireAdmin } from "@/lib/auth";
import { contactRequests, db } from "@/lib/db";
import { INTERESTS } from "@/lib/site";

export const metadata: Metadata = { title: "Aanvragen" };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { weekday: "short", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit", timeZone: "Europe/Amsterdam" });

export default async function RequestsPage({ searchParams }: PageProps<"/admin/aanvragen">) {
  await requireAdmin("/admin/aanvragen");
  const { toon } = await searchParams;
  const done = toon === "afgehandeld";
  const [rows, [{ n: openCount }], [{ n: doneCount }]] = await Promise.all([
    db.select().from(contactRequests).where(eq(contactRequests.handled, done)).orderBy(desc(contactRequests.createdAt)).limit(200),
    db.$count(contactRequests, eq(contactRequests.handled, false)).then((n) => [{ n }]),
    db.$count(contactRequests, eq(contactRequests.handled, true)).then((n) => [{ n }]),
  ]);

  const tab = (active: boolean) => `inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm font-medium ${active ? "border-ink bg-ink text-white" : "border-line bg-white hover:border-ink"}`;

  return (
    <div className={ADMIN_PAGE}>
      <div className="max-w-5xl">
        <AdminPageHeader title="Aanvragen" description="Aanvragen voor een gratis kennismaking via de contactpagina." />
        <nav aria-label="Filter" className="mb-4 flex gap-1.5">
          <Link href="/admin/aanvragen" className={tab(!done)} aria-current={!done ? "page" : undefined}>
            Open <span className="tabular-nums opacity-70">{openCount}</span>
          </Link>
          <Link href="/admin/aanvragen?toon=afgehandeld" className={tab(done)} aria-current={done ? "page" : undefined}>
            Afgehandeld <span className="tabular-nums opacity-70">{doneCount}</span>
          </Link>
        </nav>

        {rows.length === 0 ? (
          <EmptyState>{done ? "Nog niets afgehandeld." : "Geen open aanvragen. Mooi zo!"}</EmptyState>
        ) : (
          <ul className="grid gap-3">
            {rows.map((c) => (
              <li key={c.id} className="card p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p className="font-semibold">{c.name}</p>
                    <p className="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                      <a href={`mailto:${c.email}`} className="inline-flex items-center gap-1.5 hover:underline">
                        <Mail className="size-3.5 text-muted" aria-hidden="true" /> {c.email}
                      </a>
                      {c.phone && (
                        <a href={`tel:${c.phone.replace(/\s/g, "")}`} className="inline-flex items-center gap-1.5 hover:underline">
                          <Phone className="size-3.5 text-muted" aria-hidden="true" /> {c.phone}
                        </a>
                      )}
                    </p>
                  </div>
                  <span className="rounded-full bg-accent-tint px-3 py-1 text-xs font-semibold text-accent">{INTERESTS.find((i) => i.id === c.interest)?.label ?? c.interest}</span>
                </div>
                {c.message && <p className="mt-3 whitespace-pre-line rounded-lg bg-surface px-4 py-3 text-sm">{c.message}</p>}
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-muted">
                  <span className="first-letter:uppercase">{dateFmt.format(c.createdAt)}</span>
                  <form action={toggleContactHandledAction}>
                    <input type="hidden" name="id" value={c.id} />
                    <button type="submit" className={`btn btn-sm ${c.handled ? "btn-outline" : "btn-primary"}`}>
                      {c.handled ? "Terugzetten naar open" : "Markeer als afgehandeld"}
                    </button>
                  </form>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
