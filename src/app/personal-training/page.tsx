import { Activity, ArrowRight, CalendarRange, HeartPulse, ShieldCheck, Target, Video } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { PriceCard } from "@/components/PriceCard";
import { Reviews } from "@/components/Reviews";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { PT_PRICES } from "@/lib/site";

export const metadata: Metadata = {
  title: "Personal training",
  description:
    "1-op-1 personal training in Amsterdam met Steyn van Leeuwen. Voor een gezondere leefstijl, specifieke doelen en topsporters. Bij Workout Amsterdam of op locatie.",
};

const topsport = [
  { icon: Target, title: "Doelgerichte periodisering", text: "Een plan dat toewerkt naar jouw wedstrijd, seizoen of moment suprême." },
  { icon: Activity, title: "Sportspecifieke kracht", text: "Kracht, snelheid en explosiviteit vertaald naar jouw sport." },
  { icon: ShieldCheck, title: "Blessurepreventie", text: "Bewegingsassessment en gerichte oefeningen om sterker én heler te blijven." },
  { icon: HeartPulse, title: "Herstel & ademhaling", text: "Slaap, voeding en ademtechnieken voor optimaal herstel en focus onder druk." },
  { icon: Video, title: "Techniekanalyse", text: "We analyseren je uitvoering en sturen bij, ook tussen de sessies door." },
  { icon: CalendarRange, title: "Begeleiding rond je schema", text: "Afgestemd op trainingen bij je club, wedstrijden en reizen." },
];

export default function PersonalTrainingPage() {
  return (
    <>
      <PageHero
        eyebrow="Alles over personal training"
        title={
          <>
            1-op-1. <span className="text-accent">100%</span> voor jouw doel.
          </>
        }
        intro="Ongeacht jouw doel of sport: SteynPT gaat er 100% voor. Met een persoonlijk trainingsplan werken we zo efficiënt mogelijk naar jouw doel toe, met veel energie, aandacht voor de juiste uitvoering en een fijne sfeer."
        image="/images/steyn-deadlift-portret.jpg"
        imageAlt="Steyn coacht een sporter tijdens de deadlift"
      >
        <ButtonLink href="/contact">
          Vraag een gratis proefles aan <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="#tarieven" variant="outline">
          Bekijk de pakketten
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <div>
          <SectionHeading eyebrow="Voor een gezondere leefstijl" title="Een duidelijk plan, samen uitgevoerd" />
          <div className="prose-site lead mt-6 text-muted">
            <p>
              Ik maak een gepersonaliseerd trainingsplan voor je, zodat we zo efficiënt mogelijk naar je doel toewerken. Met
              een duidelijk en overzichtelijk plan weet je precies wat je te wachten staat en wat je moet doen om jouw doel
              te bereiken.
            </p>
            <p>
              Naast ervaring in krachttraining heb ik een achtergrond in powerliften, boksen, CrossFit en sportspecifieke
              training. Samen maken we, indien gewenst, een mooie combinatie om jouw doel te bereiken.
            </p>
          </div>
        </div>
        <div className="card self-start p-8">
          <h3 className="display text-2xl">Altijd inbegrepen</h3>
          <div className="mt-6">
            <CheckList
              items={[
                "Intakegesprek over je doelen en achtergrond",
                "Nulmeting: wegen, meten en bewegen",
                "Persoonlijk trainingsschema",
                "Voedingsadvies op basis van jouw doel",
                "Contactmomenten ook buiten de trainingen",
                "Trainen bij Workout Amsterdam of op locatie",
              ]}
            />
          </div>
        </div>
      </section>

      <section id="topsport" className="scroll-mt-28 bg-surface py-20 lg:py-28">
        <div className="container-site">
          <div className="grid items-end gap-10 lg:grid-cols-[1.3fr_1fr]">
            <SectionHeading
             
              eyebrow="Specifieke doelen & topsport"
              title={
                <>
                  Begeleiding voor sporters die <span className="text-accent">meer</span> willen
                </>
              }
              intro="Werk je naar een wedstrijd, wil je terugkomen na een blessure of zoek je die laatste procenten? Steyn is gespecialiseerd in het 1-op-1 begeleiden van specifieke doelen en (top)sporters."
            />
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
            {topsport.map(({ icon: Icon, title, text }) => (
              <div key={title} className="card-soft p-7">
                <Icon className="size-7 text-accent" aria-hidden="true" />
                <h3 className="mt-5 text-lg font-semibold">{title}</h3>
                <p className="mt-2 text-[15px] leading-relaxed text-muted">{text}</p>
              </div>
            ))}
          </div>
          <div className="mt-10 flex flex-col gap-3 sm:flex-row">
            <ButtonLink href="/contact">Bespreek jouw doel</ButtonLink>
            <ButtonLink href="/online-coaching" variant="outline">
              Combineer met online coaching
            </ButtonLink>
          </div>
        </div>
      </section>

      <section id="tarieven" className="container-site scroll-mt-28 py-20 lg:py-28">
        <SectionHeading
          eyebrow="Tarieven"
          title="1-op-1 pakketten"
          intro="Sport je graag individueel en wil je samen met Steyn alles uit je sessie halen? Kies dan één van de 1-op-1 pakketten."
        />
        <div className="mt-14 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
          {PT_PRICES.map((card) => (
            <PriceCard key={card.name} card={card} />
          ))}
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading eyebrow="Reviews" title="Resultaat dat blijft" />
          <div className="mt-12">
            <Reviews />
          </div>
        </div>
      </section>

      <CtaBand
        title="Zin om kennis te maken?"
        text="Plan een gratis proefles of kennismaking. We ontvangen je graag bij Workout Amsterdam."
        primary={{ href: "/contact", label: "Gratis kennismaking" }}
        secondary={{ href: "/online-coaching", label: "Of start online" }}
      />
    </>
  );
}
