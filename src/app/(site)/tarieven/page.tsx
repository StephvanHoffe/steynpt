import type { Metadata } from "next";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { OnlinePlans } from "@/components/OnlinePlans";
import { PageHero } from "@/components/PageHero";
import { PriceCard } from "@/components/PriceCard";
import { Reviews } from "@/components/Reviews";
import { Rich } from "@/components/content/Rich";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { tarieven } from "@/lib/content/registry";
import { getBreathworkPrice, getPtPrices, getTexts } from "@/lib/content/texts";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(tarieven);
  return { title: seo.title, description: seo.description };
}

export default async function TarievenPage() {
  const [t, ptPrices, breathwork] = await Promise.all([getTexts(tarieven), getPtPrices(), getBreathworkPrice()]);
  const jump = [
    { href: "#online", label: t.menu.online },
    { href: "#personal-training", label: t.menu.pt },
    { href: "#ademcoaching", label: t.menu.adem },
  ];
  return (
    <>
      <PageHero eyebrow={t.hero.eyebrow} title={<Rich text={t.hero.title} />} intro={t.hero.intro}>
        <ButtonLink href="/contact">{t.hero.primary}</ButtonLink>
      </PageHero>

      <nav aria-label="Tarieven per dienst" className="border-b border-line bg-paper">
        <ul className="container-site flex gap-2 overflow-x-auto py-3">
          {jump.map((j) => (
            <li key={j.href}>
              <a href={j.href} className="block whitespace-nowrap rounded-full border border-line px-4 py-2 text-sm font-medium hover:border-ink/40">
                {j.label}
              </a>
            </li>
          ))}
        </ul>
      </nav>

      <section id="online" className="scroll-mt-28 bg-surface py-20 lg:py-24">
        <div className="container-site">
          <SectionHeading eyebrow={t.online.eyebrow} title={<Rich text={t.online.title} />} intro={t.online.intro} />
          <div className="mt-12">
            <OnlinePlans />
          </div>
        </div>
      </section>

      <section id="personal-training" className="container-site scroll-mt-28 py-20 lg:py-24">
        <SectionHeading eyebrow={t.pt.eyebrow} title={<Rich text={t.pt.title} />} intro={t.pt.intro} />
        <div className="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {ptPrices.map((card, i) => (
            <PriceCard key={i} card={card} cta={t.pt.button} />
          ))}
        </div>
        {t.pt.note && <p className="mt-6 text-sm text-muted">{t.pt.note}</p>}
      </section>

      <section id="ademcoaching" className="container-site scroll-mt-28 py-20 lg:py-24">
        <SectionHeading eyebrow={t.adem.eyebrow} title={<Rich text={t.adem.title} />} intro={t.adem.intro} />
        <div className="mt-12 grid max-w-4xl gap-5 md:grid-cols-2">
          <PriceCard card={breathwork} cta={t.adem.soloButton} href="/contact?onderwerp=ademcoaching" />
          <article className="flex flex-col rounded-xl border border-line bg-white p-7">
            <p className="text-xs font-semibold uppercase tracking-[0.14em] text-muted">{t.adem.groupLabel}</p>
            <h3 className="display mt-2 text-2xl">{t.adem.groupTitle}</h3>
            <p className="display mt-6 text-4xl">{t.adem.groupPrice}</p>
            <p className="mt-6 flex-1 text-sm text-ink/85">{t.adem.groupText}</p>
            <ButtonLink href="/contact?onderwerp=ademcoaching-groep" variant="outline" className="mt-7 w-full bg-white">
              {t.adem.groupButton}
            </ButtonLink>
            <Link href="/ademcoaching" className="mt-4 text-center text-sm font-semibold underline decoration-accent underline-offset-4">
              {t.adem.moreLink}
            </Link>
          </article>
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-24">
        <div className="container-site">
          <SectionHeading eyebrow={t.reviews.eyebrow} title={<Rich text={t.reviews.title} />} />
          <div className="mt-12">
            <Reviews />
          </div>
        </div>
      </section>

      <CtaBand />
    </>
  );
}
