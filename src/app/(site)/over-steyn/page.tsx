import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { ButtonLink, SectionHeading } from "@/components/ui";
import { EXPERTISE, METHOD_STEPS } from "@/lib/site";

export const metadata: Metadata = {
  title: "Over Steyn",
  description:
    "Maak kennis met Steyn van Leeuwen: full-time personal trainer, voedingscoach en orthomoleculair voedingstherapeut in Amsterdam.",
};

export default function OverSteynPage() {
  return (
    <>
      <PageHero
        eyebrow="Kom alles te weten"
        title={
          <>
            Over <span className="text-accent">Steyn</span>
          </>
        }
        intro="Full-time personal trainer en voedingscoach. Geboren in Hoevelaken, werkzaam in Amsterdam, en elke dag nog aan het doorleren."
        image="/images/steyn-headshot.jpg"
        imageAlt="Portret van Steyn van Leeuwen"
      >
        <ButtonLink href="/contact">
          Vraag een kennismaking aan <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-[1.4fr_1fr] lg:py-28">
        <div>
          <SectionHeading eyebrow="Mijn verhaal" title="Van hernia naar passie" />
          <div className="prose-site lead mt-6 text-muted">
            <p>
              Ik ben Steyn van Leeuwen, full-time personal trainer en voedingscoach. Ik ben geboren in Hoevelaken en nu
              werkzaam als PT in Amsterdam. Toen ik 15 was begon ik met fitness: door een beginnende hernia in mijn onderrug,
              als gevolg van het hockeyen, begon ik op aanraden van de fysio met krachttraining.
            </p>
            <p>
              Ik vond hierin mijn passie en kwam er al snel achter dat er veel onduidelijkheid is in de fitnesswereld:
              iedereen vindt er iets anders van. Daar is mijn interesse begonnen. Ik heb meerdere opleidingen gevolgd in
              personal training en voeding, en ik blijf elke dag doorleren.
            </p>
            <p>
              Ik geef persoonlijke trainingen, maak voedingsplannen op maat, begeleid sporters naar specifieke doelen en geef
              ademcoaching in groepsverband. Samen werken we aan jouw doelen: binnen, buiten, in de gym, thuis, op kantoor of
              online.
            </p>
            <p>
              Wil je serieus aan de slag met je gezondheid en weten wat ik voor je kan betekenen? Neem dan contact met mij op!
            </p>
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
            <h3 className="display text-2xl">Expertises</h3>
            <ul className="mt-5 flex flex-wrap gap-2">
              {EXPERTISE.map((e) => (
                <li key={e} className="rounded-full bg-surface px-3.5 py-1.5 text-sm">
                  {e}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </section>

      <section className="bg-surface py-20 lg:py-28">
        <div className="container-site">
          <SectionHeading
           
            eyebrow="Werkwijze"
            title="Zo werken we samen"
            intro="Ik coach je niet alleen tijdens de trainingen. Ook daarbuiten hebben we contactmomenten om je gezondheid naar een hoger level te tillen."
          />
          <ol className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            {METHOD_STEPS.map((step, i) => (
              <li key={step.title} className="card-soft p-7">
                <span className="display text-5xl text-accent">0{i + 1}</span>
                <h3 className="display mt-5 text-2xl">{step.title}</h3>
                <p className="mt-3 text-[15px] leading-relaxed text-muted">{step.text}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <CtaBand
        title="Leuk om eens kennis te maken!"
        text="We ontvangen je graag bij Gymbase, of start direct online."
        primary={{ href: "/contact", label: "Gratis kennismaking" }}
        secondary={{ href: "/online-coaching", label: "Start online coaching" }}
      />
    </>
  );
}
