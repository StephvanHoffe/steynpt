import { SiteFooter } from "@/components/SiteFooter";
import { SiteHeader } from "@/components/SiteHeader";
import { getAnnouncement } from "@/lib/content/texts";

/** Website en Mijn omgeving: met de gewone kop en footer. Het beheer heeft een eigen opmaak. */
export default async function SiteLayout({ children }: LayoutProps<"/">) {
  return (
    <>
      <SiteHeader announcement={await getAnnouncement()} />
      <main id="inhoud" className="flex-1 overflow-x-clip">
        {children}
      </main>
      <SiteFooter />
    </>
  );
}
