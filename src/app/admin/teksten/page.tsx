import { eq } from "drizzle-orm";
import { ChevronRight, ExternalLink } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { requireAdmin } from "@/lib/auth";
import type { PageDef } from "@/lib/content/fields";
import { SHARED_PAGES, SITE_PAGES } from "@/lib/content/registry";
import { db, siteTexts, users } from "@/lib/db";

export const metadata: Metadata = { title: "Website-teksten" };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", timeZone: "Europe/Amsterdam" });

type Stat = { count: number; last: Date; by: string | null };

export default async function SiteTextsPage() {
  await requireAdmin("/admin/teksten");
  const rows = await db
    .select({ key: siteTexts.key, updatedAt: siteTexts.updatedAt, by: users.firstName })
    .from(siteTexts)
    .leftJoin(users, eq(users.id, siteTexts.updatedById));

  const stats = new Map<string, Stat>();
  for (const row of rows) {
    const slug = row.key.split(".")[0];
    const stat = stats.get(slug);
    if (!stat) stats.set(slug, { count: 1, last: row.updatedAt, by: row.by });
    else {
      stat.count++;
      if (row.updatedAt > stat.last) Object.assign(stat, { last: row.updatedAt, by: row.by });
    }
  }

  return (
    <div className={ADMIN_PAGE}>
      <div className="max-w-5xl">
        <AdminPageHeader
          title="Website-teksten"
          description="Pas de teksten aan die bezoekers en klanten op de website zien. Opslaan is meteen zichtbaar; wat je niet aanpast blijft de standaardtekst."
        />

        <PageGroup title="Pagina's" pages={SITE_PAGES} stats={stats} />
        <PageGroup
          title="Op meerdere pagina's"
          intro="Pas je hier iets aan, dan verandert het overal waar het op de site staat."
          pages={SHARED_PAGES}
          stats={stats}
        />

        <section className="card mt-8 p-5 text-sm sm:p-6">
          <h2 className="font-semibold">Handig om te weten</h2>
          <ul className="mt-3 grid gap-2 text-muted">
            <li>
              In titels zet je <span className="font-medium text-ink">*sterretjes*</span> om woorden die de petrolkleur moeten krijgen, zoals{" "}
              <span className="font-medium text-ink">Voeding die *werkt* voor jou</span>. Met Enter begin je een nieuwe regel.
            </li>
            <li>
              In lange teksten begint een lege regel een nieuwe alinea. In opsommingen staat elk punt op een eigen regel.
            </li>
            <li>
              Codes tussen accolades, zoals <span className="font-medium text-ink">{"{ademprijs}"}</span>, worden automatisch ingevuld. Verander je de prijs onder
              &lsquo;Prijzen en pakketten&rsquo;, dan klopt hij meteen overal.
            </li>
            <li>Spijt van een wijziging? Met &lsquo;Standaardtekst&rsquo; bij een veld zet je de oorspronkelijke tekst terug.</li>
          </ul>
        </section>
      </div>
    </div>
  );
}

function PageGroup({ title, intro, pages, stats }: { title: string; intro?: string; pages: PageDef[]; stats: Map<string, Stat> }) {
  return (
    <section className="mb-8">
      <h2 className="text-xs font-semibold uppercase tracking-[0.12em] text-muted">{title}</h2>
      {intro && <p className="mt-1 text-sm text-muted">{intro}</p>}
      <ul className="mt-3 grid gap-2 md:grid-cols-2">
        {pages.map((page) => {
          const stat = stats.get(page.slug);
          return (
            <li key={page.slug} className="card relative flex items-center gap-4 p-4 transition-colors hover:border-ink/40">
              <div className="min-w-0 flex-1">
                <Link href={`/admin/teksten/${page.slug}`} className="font-semibold after:absolute after:inset-0 after:rounded-xl">
                  {page.title}
                </Link>
                <p className="mt-0.5 text-sm text-muted">{page.description}</p>
                <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                  {stat ? (
                    <>
                      <span className="rounded-full bg-accent-tint px-2 py-0.5 font-semibold text-accent">
                        {stat.count === 1 ? "1 tekst aangepast" : `${stat.count} teksten aangepast`}
                      </span>
                      <span className="text-muted">
                        Laatst op {dateFmt.format(stat.last)}
                        {stat.by ? ` door ${stat.by}` : ""}
                      </span>
                    </>
                  ) : (
                    <span className="rounded-full bg-surface px-2 py-0.5 font-medium text-muted">Standaardteksten</span>
                  )}
                  {page.path && (
                    <a
                      href={page.path}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="relative z-10 inline-flex items-center gap-1 font-medium text-muted underline-offset-2 hover:text-ink hover:underline"
                    >
                      {page.path} <ExternalLink className="size-3" aria-hidden="true" />
                      <span className="sr-only">(bekijk op de website, opent in een nieuw tabblad)</span>
                    </a>
                  )}
                </p>
              </div>
              <ChevronRight className="size-5 shrink-0 text-muted" aria-hidden="true" />
            </li>
          );
        })}
      </ul>
    </section>
  );
}
