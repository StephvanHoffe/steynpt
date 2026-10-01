import { SiteFooter } from "@/components/SiteFooter";
import { SiteHeader } from "@/components/SiteHeader";
import { SITE } from "@/lib/site";

/** Website en Mijn omgeving: met de gewone kop en footer. Het beheer heeft een eigen opmaak. */
export default function SiteLayout({ children }: LayoutProps<"/">) {
  return (
    <>
      <SiteHeader announcement={SITE.announcement} />
      <main id="inhoud" className="flex-1 overflow-x-clip">
        {children}
      </main>
      <SiteFooter />
    </>
  );
}
