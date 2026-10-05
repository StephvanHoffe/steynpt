import { eq } from "drizzle-orm";
import { ExternalLink } from "lucide-react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { TextEditor } from "@/components/admin/texts/TextEditor";
import { ADMIN_PAGE, AdminPageHeader } from "@/components/admin/ui";
import { requireAdmin } from "@/lib/auth";
import { algemeen, findPage, pakketten, VARS } from "@/lib/content/registry";
import { getEditableTexts } from "@/lib/content/texts";
import { SITE_LINKS } from "@/lib/content/values";
import { db, siteTexts, users } from "@/lib/db";

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", hour: "2-digit", minute: "2-digit", timeZone: "Europe/Amsterdam" });

export async function generateMetadata({ params }: PageProps<"/admin/teksten/[pagina]">): Promise<Metadata> {
  const page = findPage((await params).pagina);
  return { title: page ? `${page.title} · Website-teksten` : "Website-teksten" };
}

export default async function EditSiteTextsPage({ params }: PageProps<"/admin/teksten/[pagina]">) {
  const { pagina } = await params;
  await requireAdmin(`/admin/teksten/${pagina}`);
  const page = findPage(pagina);
  if (!page) notFound();

  const [values, shared, prices, rows] = await Promise.all([
    getEditableTexts(page),
    getEditableTexts(algemeen),
    getEditableTexts(pakketten),
    db
      .select({ key: siteTexts.key, updatedAt: siteTexts.updatedAt, by: users.firstName })
      .from(siteTexts)
      .leftJoin(users, eq(users.id, siteTexts.updatedById)),
  ]);

  // Wanneer is elk aangepast veld voor het laatst opgeslagen (voor de tooltip in het bewerkscherm)?
  const prefix = `${page.slug}.`;
  const mine = rows.filter((r) => r.key.startsWith(prefix));
  const changedAt = Object.fromEntries(mine.map((r) => [r.key.slice(prefix.length), `${dateFmt.format(r.updatedAt)}${r.by ? ` door ${r.by}` : ""}`]));
  const last = mine.reduce<(typeof mine)[number] | null>((a, b) => (!a || b.updatedAt > a.updatedAt ? b : a), null);

  return (
    <div className={ADMIN_PAGE}>
      <AdminPageHeader
        back={{ href: "/admin/teksten", label: "Website-teksten" }}
        title={page.title}
        description={
          <>
            {page.description}{" "}
            {last ? `Laatst opgeslagen op ${dateFmt.format(last.updatedAt)}${last.by ? ` door ${last.by}` : ""}.` : "Nog niets aangepast: alles is de standaardtekst."}
          </>
        }
        actions={
          page.path && (
            <a href={page.path} target="_blank" rel="noopener noreferrer" className="btn btn-sm btn-outline bg-white">
              Bekijk op de site <ExternalLink className="size-3.5" aria-hidden="true" />
            </a>
          )
        }
      />
      <TextEditor
        page={page}
        initial={values}
        shared={{ algemeen: shared, pakketten: prices }}
        vars={VARS.map((v) => ({ ...v }))}
        links={SITE_LINKS.map((l) => ({ ...l }))}
        changedAt={changedAt}
      />
    </div>
  );
}
