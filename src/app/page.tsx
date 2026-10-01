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
  Users,
  Wind,
} from "lucide-react";
import Image from "next/image";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { DashboardPreview } from "@/components/DashboardPreview";
import { Locations } from "@/components/Locations";
import { LogoMark } from "@/components/Logo";
import { Reviews } from "@/components/Reviews";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { POINTS, TIERS, welcomePoints } from "@/lib/loyalty";
import { EXPERTISE, METHOD_STEPS, ONLINE_PLANS } from "@/lib/site";

const services = [
  {
    href: "/online-coaching",
    icon: Smartphone,
    title: "Online coaching",
    text: "Schema, voedingsplan en wekelijkse check-ins in je eigen dashboard. Train waar en wanneer jij wilt.",
    highlight: true,
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
    text: "In groepsverband werken aan rust, focus, herstel en energie. Voor teams, bedrijven en vriendengroepen.",
  },
  {
    href: "/voedingscoaching",
    icon: Apple,
    title: "Voedingscoaching",
    text: "Bij afvallen en aankomen. Orthomoleculaire, leefstijl- en vitaliteitscoaching.",
  },
  {
    href: "/small-group-training",
    icon: Users,
    title: "Small group training",
    text: "Bedrijfsfitness op locatie of buitentrainingen met je vrienden of collega's.",
  },
];

const onlineFeatures = [
  { icon: ClipboardList, title: "Schema op maat", text: "Afgestemd op je doel, niveau en agenda." },
  { icon: Apple, title: "Voedingsplan", text: "De juiste balans in macro- en micronutriënten." },
  { icon: CalendarCheck, title: "Wekelijkse check-in", text: "Houd je voortgang bij en verdien punten." },
  { icon: MessageCircle, title: "Direct contact", text: "Steyn stuurt bij waar nodig." },
];

const marquee = ["Personal training", "Online coaching", "Ademcoaching", "Voedingscoaching", "Topsport", "Small group", "Leefstijl"];

export default function HomePage() {
  const lowestPrice = Math.min(...ONLINE_PLANS.map((p) => Number(p.price)));
  return (
    <>
      {/* Hero */}
      <section className="hero-soft relative overflow-hidden">
        <LogoMark className="pointer-events-none absolute -right-20 top-10 hidden h-[640px] w-auto opacity-[0.035] lg:block" />
        <div className="container-site grid items-center gap-12 pb-16 pt-12 lg:grid-cols-[1.3fr_1fr] lg:pb-24 lg:pt-20">
          <div className="animate-rise">
            <p className="eyebrow text-rose">Personal training · Amsterdam &amp; online</p>
            <h1 className="display display-xl mt-6">
              Sterker lichaam.
              <br />
              <span className="text-rose">Gezonder</span> leven.
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
                ["2", "locaties in Amsterdam"],
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
            <div className="absolute -inset-3 rotate-2 rounded-[1.75rem] border border-rose/40" aria-hidden="true" />
            <Image
              src="/images/steyn-glimlach.jpg"
              alt="Steyn van Leeuwen lacht tijdens een intakegesprek"
              width={900}
              height={1350}
              priority
              sizes="(min-width: 1024px) 38vw, 90vw"
              className="relative aspect-[4/5] w-full rounded-[1.5rem] object-cover"
            />
            <Link
              href="/online-coaching"
              className="absolute -bottom-6 left-4 right-4 flex items-center justify-between gap-4 rounded-2xl bg-petal p-4 text-ink shadow-xl transition-transform hover:-translate-y-0.5 sm:left-auto sm:right-[-1rem] sm:w-72"
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

      {/* Marquee */}
      <div className="overflow-hidden border-y border-line bg-petal py-3 text-ink" aria-hidden="true">
        <div className="animate-marquee flex w-max gap-8 whitespace-nowrap">
          {[...marquee, ...marquee, ...marquee, ...marquee].map((item, i) => (
            <span key={i} className="display flex items-center gap-8 text-2xl">
              {item}
              <span className="inline-block size-2 rotate-45 bg-rose" />
            </span>
          ))}
        </div>
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
                  <span className="grid size-11 shrink-0 place-items-center rounded-xl bg-blush text-rose">
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
            <p className="mt-4 text-sm text-muted">
              Nu tijdelijk <strong className="text-ink">{welcomePoints()} welkomstpunten</strong> bij het aanmaken van je account.
            </p>
          </div>
          <DashboardPreview />
        </div>
      </section>

      {/* Aanbod */}
      <section className="bg-sand py-20 lg:py-28">
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
            {services.map(({ href, icon: Icon, title, text, highlight }) => (
              <Link
                key={title}
                href={href}
                className={`group relative flex min-h-64 flex-col rounded-[1.25rem] border p-7 transition-all duration-300 hover:-translate-y-1 ${
                  highlight ? "border-rose bg-blush" : "border-line bg-paper hover:border-rose-soft"
                }`}
              >
                <div className="flex items-start justify-between">
                  <span className={`grid size-12 place-items-center rounded-xl ${highlight ? "bg-rose text-white" : "bg-blush text-rose"}`}>
                    <Icon className="size-6" aria-hidden="true" />
                  </span>
                  {highlight && (
                    <span className="rounded-full bg-petal px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">Nieuw</span>
                  )}
                </div>
                <h3 className="display mt-8 text-3xl">{title}</h3>
                <p className="mt-3 flex-1 text-[15px] leading-relaxed text-muted">{text}</p>
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
      <section className="bg-blush py-20 lg:py-28">
        <div className="container-site grid items-center gap-14 lg:grid-cols-2">
          <div className="relative">
            <Image
              src="/images/steyn-coaching-dumbbell.jpg"
              alt="Steyn begeleidt een sporter bij een dumbbell press"
              width={900}
              height={1350}
              sizes="(min-width: 1024px) 45vw, 100vw"
              className="aspect-[4/5] w-full rounded-[1.5rem] object-cover lg:max-w-lg"
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
        <ol className="mt-14 grid gap-px overflow-hidden rounded-[1.25rem] border border-line bg-line md:grid-cols-2 lg:grid-cols-4">
          {METHOD_STEPS.map((step, i) => (
            <li key={step.title} className="bg-paper p-7">
              <span className="display text-6xl text-rose">0{i + 1}</span>
              <h3 className="display mt-6 text-2xl">{step.title}</h3>
              <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
            </li>
          ))}
        </ol>
      </section>

      {/* Rewards & vrienden */}
      <section className="relative overflow-hidden bg-blush py-20 lg:py-28">
        <div className="container-site grid gap-14 lg:grid-cols-[1fr_1.1fr] lg:items-center">
          <div>
            <SectionHeading
             
              eyebrow="SteynPT Rewards"
              title={
                <>
                  Samen sterker. <span className="text-rose">Nodig vrienden uit</span>
                </>
              }
              intro="Met je account spaar je automatisch punten: voor elke check-in, voor je streak en voor iedere vriend die je meeneemt. Wissel ze in voor korting, ademcoaching of een gratis PT-sessie."
            />
            <div className="mt-10 flex flex-col gap-3 sm:flex-row">
              <ButtonLink href="/registreren">
                Maak gratis account <ArrowRight className="size-4" aria-hidden="true" />
              </ButtonLink>
              <ButtonLink href="/rewards" variant="outline">
                Hoe het werkt
              </ButtonLink>
            </div>
          </div>
          <div className="grid gap-4">
            {[
              { step: "1", title: "Deel je persoonlijke link", text: "Via WhatsApp, e-mail of social media, direct vanuit je dashboard." },
              {
                step: "2",
                title: "Je vriend maakt een account",
                text: `Jij krijgt ${POINTS.friendSignup} punten, je vriend ${POINTS.invitedBonus} extra welkomstpunten.`,
              },
              {
                step: "3",
                title: "Je vriend start een traject",
                text: `Nog eens ${POINTS.friendStarts} punten voor jou. Bij ${POINTS.friendsMilestoneCount} gestarte vrienden volgt een bonus van ${POINTS.friendsMilestone}.`,
              },
            ].map((item) => (
              <div key={item.step} className="card-soft flex gap-5 p-6">
                <span className="display grid size-12 shrink-0 place-items-center rounded-full bg-petal text-2xl text-ink">{item.step}</span>
                <span>
                  <span className="block text-lg font-semibold">{item.title}</span>
                  <span className="mt-1 block text-muted">{item.text}</span>
                </span>
              </div>
            ))}
            <div className="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
              Niveaus:
              {TIERS.map((t) => (
                <span key={t.id} className="rounded-full border border-ink/15 px-3 py-1 text-ink/85">
                  {t.name}
                </span>
              ))}
            </div>
          </div>
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
      <section className="bg-sand py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading
            eyebrow="Bezoek ons"
            title="Trainen waar het jou uitkomt"
            intro="In één van onze studio's in Amsterdam, op jouw favoriete plek of volledig online."
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
