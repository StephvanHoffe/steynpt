import { ArrowRight } from "lucide-react";
import type { Metadata } from "next";
import { CtaBand } from "@/components/CtaBand";
import { PageHero } from "@/components/PageHero";
import { ReferralSteps } from "@/components/ReferralSteps";
import { ButtonLink, CheckList, SectionHeading } from "@/components/ui";
import { REFERRAL } from "@/lib/referral-program";

export const metadata: Metadata = {
  title: "Vriend uitnodigen",
  description: `Nodig een vriend uit voor online coaching bij SteynPT. ${REFERRAL.headline}: je vriend krijgt ${REFERRAL.friendReward}, jij ${REFERRAL.referrerReward}.`,
};

export default function ReferralPage() {
  return (
    <>
      <PageHero
        eyebrow="Vriendenactie online coaching"
        title={
          <>
            Breng een vriend mee. <span className="text-accent">{REFERRAL.headline}.</span>
          </>
        }
        intro={`Train je al bij Steyn? Nodig een vriend uit voor online coaching. Je vriend krijgt ${REFERRAL.friendReward} en jij krijgt ${REFERRAL.referrerReward} zodra je vriend start.`}
      >
        <ButtonLink href="/account">
          Naar mijn uitnodigingslink <ArrowRight className="size-4" aria-hidden="true" />
        </ButtonLink>
        <ButtonLink href="/registreren" variant="outline">
          Nog geen account? Meld je aan
        </ButtonLink>
      </PageHero>

      <section className="container-site grid gap-14 py-20 lg:grid-cols-2 lg:py-28">
        <SectionHeading
          eyebrow="Zo werkt het"
          title="In drie stappen"
          intro="Je persoonlijke link staat in Mijn omgeving. Wie zich via jouw link aanmeldt, wordt automatisch aan jou gekoppeld; in je dashboard zie je wie zich heeft aangemeld en wie al is gestart."
        />
        <ReferralSteps />
      </section>

      <section id="voorwaarden" className="scroll-mt-28 bg-surface py-20">
        <div className="container-site max-w-3xl">
          <h2 className="display display-sm">Voorwaarden vriendenactie</h2>
          <div className="mt-6 text-sm text-muted">
            <CheckList
              items={[
                "De actie geldt voor nieuwe klanten van online coaching die zich aanmelden via een persoonlijke uitnodigingslink of -code.",
                `De nieuwe klant krijgt ${REFERRAL.friendReward}.`,
                `De uitnodiger krijgt ${REFERRAL.referrerReward} per vriend die daadwerkelijk start; Steyn verrekent dit met een volgende factuur.`,
                "Kortingen zijn niet inwisselbaar voor geld en niet te combineren met andere acties.",
                "Jezelf uitnodigen of meerdere accounts aanmaken is niet toegestaan.",
                "SteynPT kan de actie aanpassen of beëindigen; reeds verdiende kortingen blijven geldig.",
              ]}
            />
          </div>
        </div>
      </section>

      <CtaBand
        title="Nog geen klant?"
        text="Maak een gratis account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op."
        primary={{ href: "/online-coaching", label: "Bekijk online coaching" }}
        secondary={{ href: "/contact", label: "Gratis kennismaking" }}
      />
    </>
  );
}
