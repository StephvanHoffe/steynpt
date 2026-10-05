import { Activity, ArrowRight, CalendarRange, HeartPulse, ShieldCheck, Target, Video } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { PriceCard } from "@/components/PriceCard";
import { Reviews } from "@/components/Reviews";
import { Paragraphs, Rich } from "@/components/content/Rich";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { personalTraining } from "@/lib/content/registry";
import { getPtPrices, getTexts } from "@/lib/content/texts";

const TOPSPORT_ICONS = [Target, Activity, ShieldCheck, HeartPulse, Video, CalendarRange];

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(personalTraining);
  return { title: seo.title, description: seo.description };
}

export default async function PersonalTrainingPage() {
  const [t, prices] = await Promise.all([getTexts(personalTraining), getPtPrices()]);
  const topsport = t.topsport.cards.map((c, i) => ({ ...c, icon: TOPSPORT_ICONS[i] }));
  return (
    <>
      <PageHero
        eyebrow={t.hero.eyebrow}
        title={<Rich text={t.hero.title} />}
        intro={t.hero.intro}
        image="/images/steyn-deadlift-portret.jpg"
        imageAlt="Steyn coacht een sporter tijdens de deadlift"
      >
        <ButtonLink href="/contact">
          {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="#tarieven" variant="outline">
          {t.hero.secondary}
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <div>
          <SectionHeading eyebrow={t.leefstijl.eyebrow} title={<Rich text={t.leefstijl.title} />} />
          <div className="prose-site lead mt-6 text-muted">
            <Paragraphs text={t.leefstijl.body} />
          </div>
        </div>
        <div className="card self-start p-8">
          <h3 className="display text-2xl">{t.leefstijl.cardTitle}</h3>
          <div className="mt-6">
            <CheckList items={t.leefstijl.cardList} />
          </div>
        </div>
      </section>

      <section id="topsport" className="scroll-mt-28 bg-surface py-20 lg:py-28">
        <div className="container-site">
          <div className="grid items-end gap-10 lg:grid-cols-[1.3fr_1fr]">
            <SectionHeading eyebrow={t.topsport.eyebrow} title={<Rich text={t.topsport.title} />} intro={t.topsport.intro} />
            <Image
              src="/images/steyn-roeien.jpg"
              alt="Steyn coacht een sporter op de roeimachine"
              width={1400}
              height={1014}
              sizes="(min-width: 1024px) 30vw, 100vw"
              className="hidden aspect-[4/3] w-full max-w-md justify-self-end rounded-xl object-cover lg:block"
            />
          </div>
          <div className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {topsport.map(({ icon: Icon, title, text }, i) => (
              <div key={i} className="card-soft p-7">
                <Icon className="size-7 text-accent" aria-hidden="true" />
                <h3 className="mt-5 text-lg font-semibold">{title}</h3>
                <p className="mt-2 text-[15px] leading-relaxed text-muted">{text}</p>
              </div>
            ))}
          </div>
          <div className="mt-10 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="/contact">{t.topsport.primary}</ButtonLink>
            <ButtonLink href="/online-coaching" variant="outline">
              {t.topsport.secondary}
            </ButtonLink>
          </div>
        </div>
      </section>

      <section id="tarieven" className="container-site scroll-mt-28 py-20 lg:py-28">
        <SectionHeading eyebrow={t.tarieven.eyebrow} title={<Rich text={t.tarieven.title} />} intro={t.tarieven.intro} />
        <div className="mt-14 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {prices.map((card, i) => (
            <PriceCard key={i} card={card} cta={t.tarieven.button} />
          ))}
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow={t.reviews.eyebrow} title={<Rich text={t.reviews.title} />} />
          <div className="mt-12">
            <Reviews />
          </div>
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
