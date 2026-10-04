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
import Image from "next/image";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Locations } from "@/components/Locations";
import { LogoMark } from "@/components/Logo";
import { ReferralSteps } from "@/components/ReferralSteps";
import { Reviews } from "@/components/Reviews";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { REFERRAL } from "@/lib/referral-program";
import { EXPERTISE, METHOD_STEPS, ONLINE_PLANS } from "@/lib/site";

const services = [
  {
    href: "/online-coaching",
    icon: Smartphone,
    title: "Online coaching",
    text: "Schema, voedingsplan en wekelijkse check-ins in je eigen dashboard. Train waar en wanneer jij wilt.",
    highlight: true,
    points: [
      "Trainings- en voedingsschema op maat",
      "Wekelijkse check-in met feedback van Steyn",
      "Afspraken en voortgang in je dashboard",
      `Vanaf € ${ONLINE_PLANS[0].price} per maand`,
    ],
  },
  {
    href: "/personal-training",
    icon: Dumbbell,
    title: "Personal training",
    text: "1-op-1 training voor een gezondere leefstijl. Ongeacht jouw doel of sport: SteynPT gaat er 100% voor.",
  },
  {
    href: "/personal-training#topsport",
    icon: Trophy,
    title: "Topsport & specifieke doelen",
    text: "Sportspecifieke begeleiding, periodisering en blessurepreventie voor sporters die meer willen.",
  },
  {
    href: "/ademcoaching",
    icon: Wind,
    title: "Ademcoaching",
    text: "1-op-1 of in groepsverband werken aan rust, focus, herstel en energie. Groepssessies voor teams, bedrijven en vriendengroepen op aanvraag.",
  },
  {
    href: "/voedingscoaching",
    icon: Apple,
    title: "Voedingscoaching",
    text: "Bij afvallen en aankomen. Orthomoleculaire, leefstijl- en vitaliteitscoaching.",
  },
];

const onlineFeatures = [
  { icon: ClipboardList, title: "Schema op maat", text: "Afgestemd op je doel, niveau en agenda." },
  { icon: Apple, title: "Voedingsplan", text: "De juiste balans in macro- en micronutriënten." },
  { icon: CalendarCheck, title: "Check-ins & metingen", text: "Je voortgang overzichtelijk in je dashboard." },
  { icon: MessageCircle, title: "Direct contact", text: "Steyn stuurt bij waar nodig." },
];

const marquee = ["Personal training", "Online coaching", "Ademcoaching", "Voedingscoaching", "Topsport", "Leefstijl"];

export default function HomePage() {
  const lowestPrice = Math.min(...ONLINE_PLANS.map((p) => Number(p.price)));
  return (
    <>
      {/* Hero */}
      <section className="hero-soft relative overflow-hidden">
        <LogoMark className="pointer-events-none absolute -right-20 top-10 hidden h-[640px] w-auto opacity-[0.035] lg:block" />
        <div className="container-site grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[1.3fr_1fr] lg:pb-24 lg:pt-20">
          <div className="animate-rise">
            <p className="eyebrow text-accent">Personal training · Amsterdam &amp; online</p>
            <h1 className="display display-xl mt-6">
              Sterker lichaam.
              <br />
              <span className="text-accent">Gezonder</span> leven.
            </h1>
            <p className="lead mt-7 max-w-xl text-ink/75">
              Ik ben Steyn van Leeuwen, personal trainer en orthomoleculair voedingscoach. Ik help je aan een gezondere
              leefstijl, begeleid je 1-op-1 naar je specifieke doel en coach sporters naar hun beste prestatie. Vanaf nu
              ook online, waar je ook bent.
            </p>
            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/online-coaching">
                Start online coaching <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/contact" variant="outline">
                Gratis kennismaking
              </ButtonLink>
            </div>
            <dl className="mt-12 grid max-w-xl grid-cols-2 gap-x-6 gap-y-5 border-t border-ink/10 pt-8 sm:grid-cols-4">
              {[
                ["1-op-1", "persoonlijke aandacht"],
                ["Online", "overal coaching"],
                ["24 uur", "en Steyn neemt contact op"],
                ["100%", "inzet voor jouw doel"],
              ].map(([value, label]) => (
                <div key={label}>
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
                <span className="block text-[11px] font-bold uppercase tracking-wider">Nieuw</span>
                <span className="block font-semibold">Online coaching vanaf € {lowestPrice} p/m</span>
              </span>
              <ArrowUpRight className="size-5 shrink-0" aria-hidden="true" />
            </Link>
          </div>
        </div>
      </section>

      {/* Diensten */}
      <div className="border-b border-line bg-white">
        <ul className="container-site flex flex-wrap items-center justify-center gap-x-8 gap-y-2 py-5 text-sm font-medium text-muted">
          {marquee.map((item) => (
            <li key={item} className="flex items-center gap-2">
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
            <SectionHeading
              eyebrow="Nieuw bij SteynPT"
              title={
                <>
                  Online coaching. <span className="marker">Jouw coach</span>, altijd en overal.
                </>
              }
              intro="Dezelfde persoonlijke aanpak als in de gym, nu in je eigen online dashboard. Je krijgt een plan op maat, checkt wekelijks in en Steyn stuurt bij. Ideaal als je zelfstandig traint, veel reist of naast je PT-sessies extra begeleiding wilt."
            />
            <div className="mt-10 grid gap-4 sm:grid-cols-2">
              {onlineFeatures.map(({ icon: Icon, title, text }) => (
                <div key={title} className="card flex gap-4 p-5">
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
                Bekijk de pakketten <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/registreren" variant="outline">
                Gratis account aanmaken
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
            <SectionHeading
              eyebrow="Aanbod"
              title="Eén coach, alles voor jouw doel"
              intro="Met een breed scala aan opleidingen en jarenlange ervaring durft Steyn iedereen een garantie op resultaat te geven. Ben jij er klaar voor?"
            />
            <ButtonLink href="/tarieven" variant="outline" className="self-start lg:self-auto">
              Alle tarieven
            </ButtonLink>
          </div>
          <div className="mt-12 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {services.map(({ href, icon: Icon, title, text, highlight, points }) => (
              <Link
                key={title}
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
                    <span className="rounded-full bg-accent-tint px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">Nieuw</span>
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
                  Lees meer
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
              <p className="display text-4xl">15 jaar</p>
              <p className="mt-1 text-sm text-muted">Zo oud was ik toen ik op advies van de fysio begon met krachttraining.</p>
            </div>
          </div>
          <div>
            <SectionHeading
             
              eyebrow="Ontmoet Steyn"
              title="Hallo, ik ben Steyn van Leeuwen"
              intro="Full-time personal trainer en voedingscoach, geboren in Hoevelaken en werkzaam in Amsterdam. Door een beginnende hernia van het hockeyen ontdekte ik krachttraining. Daar vond ik mijn passie, en de motivatie om de onduidelijkheid in de fitnesswereld te doorbreken."
            />
            <ul className="mt-8 flex flex-wrap gap-2">
              {EXPERTISE.map((e) => (
                <li key={e} className="rounded-full border border-ink/15 px-3.5 py-1.5 text-sm text-ink/85">
                  {e}
                </li>
              ))}
            </ul>
            <ButtonLink href="/over-steyn" className="mt-10">
              Lees mijn verhaal <ArrowRight className="size-4" aria-hidden="true" />
            </ButtonLink>
          </div>
        </div>
      </section>

      {/* Werkwijze */}
      <section className="container-site py-20 lg:py-28">
        <SectionHeading
          eyebrow="Werkwijze"
          title="Van intake tot resultaat"
          intro="Of je nu in de gym traint of online: iedere samenwerking volgt dezelfde bewezen aanpak."
        />
        <ol className="mt-14 grid gap-px overflow-hidden rounded-xl border border-line bg-line md:grid-cols-2 lg:grid-cols-4">
          {METHOD_STEPS.map((step, i) => (
            <li key={step.title} className="bg-paper p-7">
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
            <SectionHeading
              eyebrow="Vriendenactie"
              title={
                <>
                  Breng een vriend mee. <span className="text-accent">{REFERRAL.headline}</span>
                </>
              }
              intro={`Nodig een vriend uit voor online coaching. Je vriend krijgt ${REFERRAL.friendReward}; jij krijgt ${REFERRAL.referrerReward} zodra je vriend start.`}
            />
            <div className="mt-10 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/registreren">
                Maak gratis account <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/vriend-uitnodigen" variant="outline">
                Zo werkt het
              </ButtonLink>
            </div>
          </div>
          <ReferralSteps />
        </div>
      </section>

      {/* Reviews */}
      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow="Reviews" title="Wat sporters zeggen" />
        <div className="mt-12">
          <Reviews />
        </div>
      </section>

      {/* Locaties */}
      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading
            eyebrow="Bezoek ons"
            title="Trainen waar het jou uitkomt"
            intro="Bij Gymbase in Amsterdam, op jouw favoriete plek of volledig online."
          />
          <div className="mt-12">
            <Locations />
          </div>
        </div>
      </section>

      <CtaBand />
    </>
  );
}
