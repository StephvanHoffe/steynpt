import { SiteFooter } from "@/components/SiteFooter";
import { SiteHeader } from "@/components/SiteHeader";
import { ButtonLink } from "@/components/ui";
import { SITE } from "@/lib/site";

export default function NotFound() {
  return (
    <>
      <SiteHeader announcement={SITE.announcement} />
      <main id="inhoud" className="flex-1 overflow-x-clip">
    <section className="hero-soft">
      <div className="container-site py-28 text-center lg:py-40">
        <p className="eyebrow justify-center text-accent">404</p>
        <h1 className="display display-xl mt-5">Deze set bestaat niet</h1>
        <p className="lead mx-auto mt-6 max-w-lg text-ink/75">De pagina die je zoekt is verplaatst of bestaat niet meer.</p>
        <div className="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
          <ButtonLink href="/">Naar home</ButtonLink>
          <ButtonLink href="/online-coaching" variant="outline">
            Bekijk online coaching
          </ButtonLink>
        </div>
      </div>
    </section>
      </main>
      <SiteFooter />
    </>
  );
}
