import type { Metadata } from "next";
import Link from "next/link";
import { CtaBand } from "@/components/CtaBand";
import { OnlinePlans } from "@/components/OnlinePlans";
import { PageHero } from "@/components/PageHero";
import { PriceCard } from "@/components/PriceCard";
import { Reviews } from "@/components/Reviews";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { GROUP_PRICES, PT_PRICES } from "@/lib/site";

export const metadata: Metadata = {
  title: "Tarieven",
  description:
    "Alle tarieven van SteynPT: 1-op-1 personal training, small group training, online coaching en ademcoaching in Amsterdam.",
};

const jump = [
  { href: "#online", label: "Online coaching" },
  { href: "#personal-training", label: "1-op-1 training" },
  { href: "#small-group", label: "Small group" },
  { href: "#ademcoaching", label: "Ademcoaching" },
];

export default function TarievenPage() {
  return (
    <>
      <PageHero
        eyebrow="Alle info over onze"
        title="Tarieven"
        intro="Transparante prijzen, geen verrassingen. Kies het pakket dat bij jouw doel past, of plan eerst een gratis kennismaking."
      >
        <ButtonLink href="/contact">Gratis kennismaking</ButtonLink>
      </PageHero>

      <nav aria-label="Tarieven per dienst" className="border-b border-line bg-paper">
        <ul className="container-site flex gap-2 overflow-x-auto py-3">
          {jump.map((j) => (
            <li key={j.href}>
              <a href={j.href} className="block whitespace-nowrap rounded-full border border-line px-4 py-2 text-sm font-medium hover:border-rose-soft">
                {j.label}
              </a>
            </li>
          ))}
        </ul>
      </nav>

      <section id="online" className="scroll-mt-28 bg-blush py-20 lg:py-24">
        <div className="container-site">
          <SectionHeading
           
            eyebrow="Nieuw · Online coaching"
            title="Online coaching"
            intro="Maandelijkse begeleiding met je eigen dashboard, wekelijkse check-ins en SteynPT Rewards."
          />
          <div className="mt-12">
            <OnlinePlans />
          </div>
        </div>
      </section>

      <section id="personal-training" className="container-site scroll-mt-28 py-20 lg:py-24">
        <SectionHeading
          eyebrow="1-op-1 trainingen"
          title="Personal training"
          intro="Sport je graag als individu en wil je samen met Steyn alles uit je sessie halen? Kies dan één van onze 1-op-1 pakketten."
        />
        <div className="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {PT_PRICES.map((card) => (
            <PriceCard key={card.name} card={card} />
          ))}
        </div>
        <p className="mt-6 text-sm text-muted">* Prijs per uur. Duo-trainingen: € 15,- toeslag per sessie.</p>
      </section>

      <section id="small-group" className="scroll-mt-28 bg-sand py-20 lg:py-24">
        <div className="container-site">
          <SectionHeading
            eyebrow="Pricing"
            title="Small group trainingen"
            intro="Vanaf 3 personen trainen wij in small group classes. Ideaal voor bedrijven of sportteams."
          />
          <div className="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            {GROUP_PRICES.map((card) => (
              <PriceCard key={card.name} card={card} />
            ))}
          </div>
          <p className="mt-6 text-sm text-muted">* Prijs per uur.</p>
        </div>
      </section>

      <section id="ademcoaching" className="container-site scroll-mt-28 py-20 lg:py-24">
        <div className="grid items-center gap-8 rounded-[1.5rem] border border-line bg-white p-8 lg:grid-cols-[1.5fr_1fr] lg:p-12">
          <div>
            <p className="eyebrow text-muted">In groepsverband</p>
            <h2 className="display display-md mt-3">Ademcoaching</h2>
            <p className="lead mt-4 text-muted">
              Tarief op aanvraag, afhankelijk van groepsgrootte en locatie. Voor bedrijven, sportteams en vriendengroepen.
            </p>
          </div>
          <div className="flex flex-col gap-3 lg:items-end">
            <ButtonLink href="/contact" variant="ink">
              Vraag een offerte aan
            </ButtonLink>
            <Link href="/ademcoaching" className="text-sm font-semibold underline decoration-rose underline-offset-4">
              Meer over ademcoaching
            </Link>
          </div>
        </div>
      </section>

      <section className="bg-sand py-20 lg:py-24">
        <div className="container-site">
          <SectionHeading eyebrow="Reviews" title="Wat sporters zeggen" />
          <div className="mt-12">
            <Reviews />
          </div>
        </div>
      </section>

      <CtaBand />
    </>
  );
}
