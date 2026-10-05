import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { Faq } from "@/components/Faq";
import { PageHero } from "@/components/PageHero";
import { Paragraphs, Rich } from "@/components/content/Rich";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { voedingscoaching } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(voedingscoaching);
  return { title: seo.title, description: seo.description };
}

export default async function VoedingscoachingPage() {
  const t = await getTexts(voedingscoaching);
  return (
    <>
      <PageHero
        eyebrow={t.hero.eyebrow}
        title={<Rich text={t.hero.title} />}
        intro={t.hero.intro}
        image="/images/steyn-intake.jpg"
        imageAlt="Steyn tijdens een voedingsgesprek"
      >
        <ButtonLink href="/contact">
          {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <div>
          <SectionHeading eyebrow={t.begeleiding.eyebrow} title={<Rich text={t.begeleiding.title} />} />
          <div className="prose-site lead mt-6 text-muted">
            <Paragraphs text={t.begeleiding.body} />
          </div>
        </div>
        <div className="card self-start p-8">
          <h3 className="display text-2xl">{t.begeleiding.cardTitle}</h3>
          <div className="mt-6">
            <CheckList items={t.begeleiding.cardList} />
          </div>
        </div>
      </section>

      <section className="bg-surface">
        <div className="grid lg:grid-cols-2">
          <Image
            src="/images/meting-huidplooi.jpg"
            alt="Huidplooimeting met een caliper"
            width={1400}
            height={933}
            sizes="(min-width: 1024px) 50vw, 100vw"
            className="h-full max-h-[560px] w-full object-cover"
          />
          <div className="px-4 py-16 sm:px-10 lg:px-16 lg:py-24">
            <SectionHeading eyebrow={t.meten.eyebrow} title={<Rich text={t.meten.title} />} intro={t.meten.intro} />
            <div className="mt-8">
              <CheckList items={t.meten.points} />
            </div>
          </div>
        </div>
      </section>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow={t.faq.eyebrow} title={<Rich text={t.faq.title} />} />
        <div className="mt-10 max-w-4xl">
          <Faq items={t.faq.questions} />
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
