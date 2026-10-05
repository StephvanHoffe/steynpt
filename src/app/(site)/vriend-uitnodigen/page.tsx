import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { ReferralSteps } from "@/components/ReferralSteps";
import { Rich } from "@/components/content/Rich";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { vriendUitnodigen } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(vriendUitnodigen);
  return { title: seo.title, description: seo.description };
}

export default async function ReferralPage() {
  const t = await getTexts(vriendUitnodigen);
  return (
    <>
      <PageHero eyebrow={t.hero.eyebrow} title={<Rich text={t.hero.title} />} intro={t.hero.intro}>
        <ButtonLink href="/account">
          {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="/registreren" variant="outline">
          {t.hero.secondary}
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <SectionHeading eyebrow={t.stappen.eyebrow} title={<Rich text={t.stappen.title} />} intro={t.stappen.intro} />
        <ReferralSteps />
      </section>

      <section id="voorwaarden" className="scroll-mt-28 bg-surface py-20">
        <div className="container-site max-w-3xl">
          <h2 className="display display-sm">
            <Rich text={t.voorwaarden.title} />
          </h2>
          <div className="mt-6 text-sm text-muted">
            <CheckList items={t.voorwaarden.points} />
          </div>
        </div>
      </section>

      <CtaBand
        title={t.afsluiter.title}
        text={t.afsluiter.text}
        primary={{ href: "/online-coaching", label: t.afsluiter.primary }}
        secondary={{ href: "/contact", label: t.afsluiter.secondary }}
      />
    </>
  );
}
