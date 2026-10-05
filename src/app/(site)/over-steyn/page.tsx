import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { Paragraphs, Rich } from "@/components/content/Rich";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { algemeen, overSteyn } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(overSteyn);
  return { title: seo.title, description: seo.description };
}

export default async function OverSteynPage() {
  const [t, shared] = await Promise.all([getTexts(overSteyn), getTexts(algemeen)]);
  return (
    <>
      <PageHero
        eyebrow={t.hero.eyebrow}
        title={<Rich text={t.hero.title} />}
        intro={t.hero.intro}
        image="/images/steyn-headshot.jpg"
        imageAlt="Portret van Steyn van Leeuwen"
      >
        <ButtonLink href="/contact">
          {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1.4fr_1fr] lg:py-28">
        <div>
          <SectionHeading eyebrow={t.verhaal.eyebrow} title={<Rich text={t.verhaal.title} />} />
          <div className="prose-site lead mt-6 text-muted">
            <Paragraphs text={t.verhaal.body} />
          </div>
        </div>
        <div className="space-y-5">
          <Image
            src="/images/steyn-deadlift.jpg"
            alt="Steyn coacht een sporter bij de deadlift"
            width={500}
            height={500}
            className="aspect-square w-full rounded-xl object-cover"
          />
          <div className="card p-7">
            <h3 className="display text-2xl">{t.verhaal.expertisesTitle}</h3>
            <ul className="mt-5 flex flex-wrap gap-2">
              {shared.expertises.list.map((e, i) => (
                <li key={i} className="rounded-full bg-surface px-3.5 py-1.5 text-sm">
                  {e}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow={t.werkwijze.eyebrow} title={<Rich text={t.werkwijze.title} />} intro={t.werkwijze.intro} />
          <ol className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {shared.werkwijze.steps.map((step, i) => (
              <li key={i} className="card-soft p-7">
                <span className="display text-5xl text-accent">0{i + 1}</span>
                <h3 className="display mt-5 text-2xl">{step.title}</h3>
                <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <CtaBand
        title={t.afsluiter.title}
        text={t.afsluiter.text}
        primary={{ href: "/contact", label: t.afsluiter.primary }}
        secondary={{ href: "/online-coaching", label: t.afsluiter.secondary }}
      />
    </>
  );
}
