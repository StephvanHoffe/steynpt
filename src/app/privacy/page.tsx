import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Privacyverklaring",
  description: "Hoe SteynPT omgaat met je persoonsgegevens.",
};

const sections = [
  {
    title: "Welke gegevens verwerken we?",
    body: [
      "Contactaanvragen: je naam, e-mailadres, (optioneel) telefoonnummer, je interesse en je bericht.",
      "Account: je naam, e-mailadres, telefoonnummer, doel, gekozen pakket en wie je hebt uitgenodigd (vriendenactie).",
      "Afspraken: type, datum, tijd, locatie en je eventuele opmerking. Steyn zet deze afspraken via een beveiligde, geheime link in zijn eigen agenda (bijvoorbeeld Google of Apple Agenda).",
      "Metingen: gewicht, vetpercentage, spiermassa en omtrekmaten die Steyn met je bijhoudt. Dit zijn gezondheidsgegevens; je ziet ze zelf in Mijn omgeving.",
      "Check-ins: je wekelijkse scores voor energie, slaap en voeding, aantal trainingen, eventueel je gewicht en opmerkingen. Dit zijn gezondheidsgegevens; we verwerken ze alleen met jouw uitdrukkelijke toestemming en uitsluitend voor je coaching.",
      "Intake: je doel, geslacht, geboortejaar, lengte, gewicht, activiteit, trainingservaring en -wensen, blessures, eetstijl, allergieën en eventuele medische aandachtspunten. Ook dit zijn gezondheidsgegevens; we gebruiken ze alleen met jouw uitdrukkelijke toestemming en alleen om je trainings- en voedingsschema te maken.",
    ],
  },
  {
    title: "Gebruik van AI voor je schema",
    body: [
      "Voor een eerste opzet van je trainings- en voedingsschema gebruiken we het AI-model Claude van Anthropic. We sturen daarvoor alleen de intakegegevens die nodig zijn voor het schema, zonder je naam, e-mailadres of telefoonnummer.",
      "De AI neemt geen beslissingen over jou: Steyn controleert en past elk schema aan voordat je het te zien krijgt. Anthropic verwerkt de gegevens als verwerker en gebruikt ze volgens zijn zakelijke voorwaarden niet om AI-modellen te trainen. Anthropic is gevestigd in de Verenigde Staten; de doorgifte gebeurt op basis van passende waarborgen, zoals de standaardcontractbepalingen van de Europese Commissie.",
      "Je kunt je toestemming altijd intrekken. Neem dan contact met ons op; Steyn maakt je schema dan volledig zelf.",
    ],
  },
  {
    title: "Waarom?",
    body: [
      "Om contact met je op te nemen, afspraken te plannen, je coaching te verzorgen, je voortgang te volgen en de vriendenactie uit te voeren. Nieuwsbrieven en acties ontvang je alleen als je daar zelf voor kiest.",
    ],
  },
  {
    title: "Hoe lang bewaren we je gegevens?",
    body: [
      "Zolang je account bestaat of zolang nodig is voor je traject. Contactaanvragen zonder vervolg verwijderen we uiterlijk na 12 maanden. Wettelijke bewaarplichten (zoals voor facturen) blijven gelden.",
    ],
  },
  {
    title: "Delen met anderen",
    body: [
      "We verkopen je gegevens nooit. We delen ze alleen met partijen die nodig zijn om de website en je coaching te laten werken (zoals hosting en de AI-dienst hierboven), onder passende afspraken.",
    ],
  },
  {
    title: "Beveiliging",
    body: [
      "Wachtwoorden worden versleuteld opgeslagen en je sessie is beveiligd met een veilige cookie. Alleen Steyn heeft toegang tot je coachinggegevens en intake.",
    ],
  },
  {
    title: "Jouw rechten",
    body: [
      "Je kunt je gegevens altijd inzien en aanpassen in je profiel, en je account met alle bijbehorende gegevens zelf verwijderen. Voor andere verzoeken (zoals een kopie van je gegevens of het intrekken van toestemming) kun je contact met ons opnemen. Ben je het niet eens met hoe we met je gegevens omgaan? Dan kun je een klacht indienen bij de Autoriteit Persoonsgegevens.",
    ],
  },
  {
    title: "Cookies",
    body: [
      "We gebruiken alleen functionele cookies: om je ingelogd te houden en om bij te houden via wiens uitnodiging je binnenkomt. Er worden geen tracking- of advertentiecookies geplaatst.",
    ],
  },
];

export default function PrivacyPage() {
  return (
    <div className="container-site max-w-3xl py-16 lg:py-24">
      <p className="eyebrow text-muted">SteynPT</p>
      <h1 className="display display-lg mt-4">Privacyverklaring</h1>
      <p className="lead mt-6 text-muted">
        SteynPT gaat zorgvuldig om met je persoonsgegevens en houdt zich aan de Algemene Verordening Gegevensbescherming
        (AVG). Hieronder lees je welke gegevens we verwerken en waarom.
      </p>
      <div className="mt-12 space-y-10">
        {sections.map((s) => (
          <section key={s.title}>
            <h2 className="text-xl font-semibold">{s.title}</h2>
            <div className="prose-site mt-3 text-muted">
              {s.body.map((p) => (
                <p key={p}>{p}</p>
              ))}
            </div>
          </section>
        ))}
      </div>
      <p className="mt-12 text-sm text-muted">
        Vragen? <Link href="/contact" className="font-semibold text-ink underline">Neem contact op</Link>.
      </p>
    </div>
  );
}
