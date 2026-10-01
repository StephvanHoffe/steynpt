import { ArrowRight, Building2, Dumbbell, Trees, Users } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { PriceCard } from "@/components/PriceCard";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { GROUP_PRICES } from "@/lib/site";

export const metadata: Metadata = {
  title: "Small group training",
  description:
    "Small group training met vrienden of collega's: bedrijfsfitness op locatie, buitentrainingen, kracht, bootcamp of boksen. Groepen tot maximaal 6 personen.",
};

const formats = [
  { icon: Dumbbell, title: "Krachttraining", text: "Techniek en progressie, ook in groepsverband." },
  { icon: Users, title: "Bootcamp", text: "Energie, conditie en teamspirit." },
  { icon: Building2, title: "Bedrijfsfitness", text: "Op kantoor, in de kantine of op het bedrijfsterrein." },
  { icon: Trees, title: "Buiten", text: "In jullie favoriete park in Amsterdam." },
];

export default function SmallGroupPage() {
  return (
    <>
      <PageHero
        eyebrow="Met je vrienden of collega's"
        title={
          <>
            Small group <span className="text-volt">training</span>
          </>
        }
        intro="Wil je samen met een groepje, je bedrijf of vrienden binnen of buiten trainen? Ik kom op locatie om jullie een top workout te geven: krachttraining, bootcamp, boksen of een combinatie. Groepen tot maximaal 6 personen."
        image="/images/steyn-team-gym.jpg"
        imageAlt="Steyn met een groep sporters"
      >
        <ButtonLink href="/contact">
          Vraag een gratis kennismaking aan <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
      </PageHero>

      <section className="container-site py-20 lg:py-28">
        <SectionHeading eyebrow="Mogelijkheden" title="Jullie groep, jullie training" />
        <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {formats.map(({ icon: Icon, title, text }) => (
            <div key={title} className="card p-6">
              <span className="grid size-11 place-items-center rounded-xl bg-ink text-volt">
                <Icon className="size-5" aria-hidden="true" />
              </span>
              <h3 className="mt-5 text-lg font-semibold">{title}</h3>
              <p className="mt-2 text-[15px] text-muted">{text}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-sand py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading
            eyebrow="Tarieven"
            title="Small group pakketten"
            intro="Vanaf 3 personen trainen we in small group classes. Ideaal voor bedrijven of sportteams."
          />
          <div className="mt-14 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            {GROUP_PRICES.map((card) => (
              <PriceCard key={card.name} card={card} />
            ))}
          </div>
        </div>
      </section>

      <CtaBand
        title="Train samen, word samen sterker"
        text="Neem contact op voor de mogelijkheden. Combineer het met een ademcoaching-sessie voor het complete teamprogramma."
        primary={{ href: "/contact", label: "Neem contact op" }}
        secondary={{ href: "/ademcoaching", label: "Ademcoaching voor teams" }}
      />
    </>
  );
}
