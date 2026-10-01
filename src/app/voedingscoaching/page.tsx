import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import Image from "next/image";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";

export const metadata: Metadata = {
  title: "Voedingscoaching",
  description:
    "Voedingscoaching door orthomoleculair voedingstherapeut Steyn van Leeuwen. Bij afvallen, aankomen, darmklachten, energie en meer. Een gericht voedingsplan op maat.",
};

const complaints = [
  "Afvallen of aankomen",
  "Darmklachten",
  "Verhoogd cholesterol",
  "Verhoogde bloeddruk",
  "Acne",
  "Weinig energie",
];

export default function VoedingscoachingPage() {
  return (
    <>
      <PageHero
        eyebrow="Alles over voedingscoaching"
        title={
          <>
            Voeding die <span className="text-volt">werkt</span> voor jou
          </>
        }
        intro="Aan de hand van jouw doelen maak ik een gericht voedingsplan voor je. Stap voor stap verbeteren we je voeding en je gezondheid. Orthomoleculaire, leefstijl- en vitaliteitscoaching."
        image="/images/steyn-intake.jpg"
        imageAlt="Steyn tijdens een voedingsgesprek"
      >
        <ButtonLink href="/contact">
          Vraag een gratis kennismaking aan <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <div>
          <SectionHeading eyebrow="Voedingsbegeleiding" title="De juiste balans in macro's én micro's" />
          <div className="prose-site lead mt-6 text-muted">
            <p>
              Ik ben orthomoleculair voedingstherapeut en help je aan de juiste balans in macro- en micronutriënten. Geen
              crashdiëten, maar een plan dat past bij jouw leven en dat je volhoudt.
            </p>
            <p>
              Wil je afvallen, aankomen of heb je klachten? Door je voeding aan te passen kunnen we samen veel bereiken.
            </p>
          </div>
        </div>
        <div className="card self-start p-8">
          <h3 className="display text-2xl">Ik help je onder andere bij</h3>
          <div className="mt-6">
            <CheckList items={complaints} />
          </div>
        </div>
      </section>

      <section className="bg-ink text-paper">
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
            <SectionHeading
              tone="dark"
              eyebrow="Meten is weten"
              title="Resultaat dat je kunt zien"
              intro="We starten met een nulmeting en meten tussentijds je voortgang, zodat we precies weten wat werkt."
            />
            <div className="mt-8">
              <CheckList tone="dark" items={["Intake en analyse van je eetpatroon", "Voedingsplan op maat", "Tussentijdse metingen en bijsturing", "Onderdeel van elk PT- en online pakket"]} />
            </div>
          </div>
        </div>
      </section>

      <CtaBand
        title="Ook online mogelijk"
        text="Voedingscoaching is onderdeel van online coaching. Krijg je voedingsplan en wekelijkse feedback gewoon via je dashboard."
        primary={{ href: "/online-coaching", label: "Bekijk online coaching" }}
        secondary={{ href: "/contact", label: "Gratis kennismaking" }}
      />
    </>
  );
}
