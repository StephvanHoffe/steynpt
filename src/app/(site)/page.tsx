import {
  Apple,
  ArrowRight,
  ArrowUpRight,
  CalendarCheck,
  ClipboardList,
  Dumbbell,
  MessageCircle,
  Smartphone,
  Trophy,
  Wind,
} from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Locations } from "@/components/Locations";
import { LogoMark } from "@/components/Logo";
import { ReferralSteps } from "@/components/ReferralSteps";
import { Reviews } from "@/components/Reviews";
import { Rich } from "@/components/content/Rich";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { algemeen, home } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

// Links en iconen horen vast bij de kaarten; de teksten komen uit het tekstbeheer (zelfde volgorde).
const SERVICES = [
  { href: "/online-coaching", icon: Smartphone, highlight: true },
  { href: "/personal-training", icon: Dumbbell },
  { href: "/personal-training#topsport", icon: Trophy },
  { href: "/ademcoaching", icon: Wind },
  { href: "/voedingscoaching", icon: Apple },
];
const ONLINE_ICONS = [ClipboardList, Apple, CalendarCheck, MessageCircle];

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(home);
  return { title: { absolute: seo.title }, description: seo.description };
}

export default async function HomePage() {
  const [t, shared] = await Promise.all([getTexts(home), getTexts(algemeen)]);
  const services = SERVICES.map((s, i) => ({ ...s, ...t.aanbod.services[i], points: s.highlight ? t.aanbod.onlinePoints : undefined }));
  const onlineFeatures = t.online.features.map((f, i) => ({ ...f, icon: ONLINE_ICONS[i] }));
  return (
    <>
      {/* Hero */}
      <section className="hero-soft relative overflow-hidden">
        <LogoMark className="pointer-events-none absolute -right-20 top-10 hidden h-[640px] w-auto opacity-[0.035] lg:block" />
        <div className="container-site grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[1.3fr_1fr] lg:pb-24 lg:pt-20">
          <div className="animate-rise">
            <p className="eyebrow text-accent">{t.hero.eyebrow}</p>
            <h1 className="display display-xl mt-6">
              <Rich text={t.hero.title} />
            </h1>
            <p className="lead mt-7 max-w-xl text-ink/75">{t.hero.intro}</p>
            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/online-coaching">
                {t.hero.primary} <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/contact" variant="outline">
                {t.hero.secondary}
              </ButtonLink>
            </div>
            <dl className="mt-12 grid max-w-xl grid-cols-2 gap-x-6 gap-y-5 border-t border-ink/10 pt-8 sm:grid-cols-4">
              {t.hero.stats.map(({ value, label }, i) => (
                <div key={i}>
                  <dt className="sr-only">{label}</dt>
                  <dd>
                    <span className="display block text-3xl text-ink">{value}</span>
                    <span className="mt-1 block text-xs text-muted">{label}</span>
                  </dd>
                </div>
              ))}
            </dl>
          </div>

          <div className="relative mx-auto w-full max-w-md lg:max-w-none">
            <div className="absolute -inset-3 rounded-2xl border border-line" aria-hidden="true" />
            <Image
              src="/images/steyn-glimlach.jpg"
              alt="Steyn van Leeuwen lacht tijdens een intakegesprek"
              width={900}
              height={1350}
              priority
              sizes="(min-width: 1024px) 38vw, 90vw"
              className="relative aspect-[4/5] w-full rounded-xl object-cover"
            />
            <Link
              href="/online-coaching"
              className="absolute -bottom-6 left-4 right-4 flex items-center justify-between gap-4 rounded-2xl bg-accent-tint p-4 text-ink shadow-xl transition-transform hover:-translate-y-0.5 sm:left-auto sm:right-[-1rem] sm:w-72"
            >
              <span>
                <span className="block text-[11px] font-bold uppercase tracking-wider">{t.hero.badgeLabel}</span>
                <span className="block font-semibold">{t.hero.badgeText}</span>
              </span>
              <ArrowUpRight className="size-5 shrink-0" aria-hidden="true" />
            </Link>
          </div>
        </div>
      </section>

      {/* Diensten */}
      <div className="border-b border-line bg-white">
        <ul className="container-site flex flex-wrap items-center justify-center gap-x-8 gap-y-2 py-5 text-sm font-medium text-muted">
          {t.diensten.list.map((item, i) => (
            <li key={i} className="flex items-center gap-2">
              <span className="size-1.5 rounded-full bg-accent" aria-hidden="true" />
              {item}
            </li>
          ))}
        </ul>
      </div>

      {/* Online coaching spotlight */}
      <section className="container-site py-20 lg:py-28">
        <div className="grid items-center gap-14 lg:grid-cols-[1.1fr_1fr]">
          <div>
            <SectionHeading eyebrow={t.online.eyebrow} title={<Rich text={t.online.title} accent="marker" />} intro={t.online.intro} />
            <div className="mt-10 grid gap-4 sm:grid-cols-2">
              {onlineFeatures.map(({ icon: Icon, title, text }, i) => (
                <div key={i} className="card flex gap-4 p-5">
                  <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-tint text-accent">
                    <Icon className="size-5" aria-hidden="true" />
                  </span>
                  <span>
                    <span className="block font-semibold">{title}</span>
                    <span className="mt-1 block text-sm text-muted">{text}</span>
                  </span>
                </div>
              ))}
            </div>
            <div className="mt-10 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/online-coaching" variant="ink">
                {t.online.primary} <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/registreren" variant="outline">
                {t.online.secondary}
              </ButtonLink>
            </div>
          </div>
          <DashboardPreview />
        </div>
      </section>

      {/* Aanbod */}
      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <div className="flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
            <SectionHeading eyebrow={t.aanbod.eyebrow} title={<Rich text={t.aanbod.title} />} intro={t.aanbod.intro} />
            <ButtonLink href="/tarieven" variant="outline" className="self-start lg:self-auto">
              {t.aanbod.button}
            </ButtonLink>
          </div>
          <div className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {services.map(({ href, icon: Icon, title, text, highlight, points }) => (
              <Link
                key={href}
                href={href}
                className={`group relative flex min-h-64 flex-col rounded-xl border p-7 transition-all duration-300 hover:-translate-y-1 ${
                  highlight ? "border-ink bg-white ring-1 ring-ink md:row-span-2" : "border-line bg-paper hover:border-ink/40"
                }`}
              >
                <div className="flex items-start justify-between">
                  <span className={`grid size-12 place-items-center rounded-xl ${highlight ? "bg-ink text-white" : "bg-accent-tint text-accent"}`}>
                    <Icon className="size-6" aria-hidden="true" />
                  </span>
                  {highlight && (
                    <span className="rounded-full bg-accent-tint px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">{t.aanbod.badge}</span>
                  )}
                </div>
                <h3 className="display mt-8 text-3xl">{title}</h3>
                <p className={`mt-3 text-[15px] leading-relaxed text-muted ${points ? "" : "flex-1"}`}>{text}</p>
                {points && (
                  <div className="mt-6 flex-1 text-sm">
                    <CheckList items={points} />
                  </div>
                )}
                <span className="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold">
                  {t.aanbod.more}
                  <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                </span>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* Over Steyn */}
      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site grid items-center gap-14 lg:grid-cols-2">
          <div className="relative">
            <Image
              src="/images/steyn-coaching-dumbbell.jpg"
              alt="Steyn begeleidt een sporter bij een dumbbell press"
              width={900}
              height={1350}
              sizes="(min-width: 1024px) 45vw, 100vw"
              className="aspect-[4/5] w-full rounded-xl object-cover lg:max-w-lg"
            />
            <div className="absolute -bottom-6 right-0 max-w-[16rem] rounded-2xl bg-paper p-5 text-ink shadow-xl sm:right-6 lg:right-0">
              <p className="display text-4xl">{t.over.cardTitle}</p>
              <p className="mt-1 text-sm text-muted">{t.over.cardText}</p>
            </div>
          </div>
          <div>
            <SectionHeading eyebrow={t.over.eyebrow} title={<Rich text={t.over.title} />} intro={t.over.intro} />
            <ul className="mt-8 flex flex-wrap gap-2">
              {shared.expertises.list.map((e, i) => (
                <li key={i} className="rounded-full border border-ink/15 px-3.5 py-1.5 text-sm text-ink/85">
                  {e}
                </li>
              ))}
            </ul>
            <ButtonLink href="/over-steyn" className="mt-10">
              {t.over.button} <ArrowRight className="size-4" aria-hidden="true" />
            </ButtonLink>
          </div>
        </div>
      </section>

      {/* Werkwijze */}
      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow={t.werkwijze.eyebrow} title={<Rich text={t.werkwijze.title} />} intro={t.werkwijze.intro} />
        <ol className="mt-14 grid gap-px overflow-hidden rounded-xl border border-line bg-line md:grid-cols-2 lg:grid-cols-4">
          {shared.werkwijze.steps.map((step, i) => (
            <li key={i} className="bg-paper p-7">
              <span className="display text-6xl text-accent">0{i + 1}</span>
              <h3 className="display mt-6 text-2xl">{step.title}</h3>
              <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* Vriendenactie */}
      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:items-center">
          <div>
            <SectionHeading eyebrow={t.vriendenactie.eyebrow} title={<Rich text={t.vriendenactie.title} />} intro={t.vriendenactie.intro} />
            <div className="mt-10 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/registreren">
                {t.vriendenactie.primary} <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/vriend-uitnodigen" variant="outline">
                {t.vriendenactie.secondary}
              </ButtonLink>
            </div>
          </div>
          <ReferralSteps />
        </div>
      </section>

      {/* Reviews */}
      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow={t.reviews.eyebrow} title={<Rich text={t.reviews.title} />} />
        <div className="mt-12">
          <Reviews />
        </div>
      </section>

      {/* Locaties */}
      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow={t.locaties.eyebrow} title={<Rich text={t.locaties.title} />} intro={t.locaties.intro} />
          <div className="mt-12">
            <Locations />
          </div>
        </div>
      </section>

      <CtaBand />
    </>
  );
}
