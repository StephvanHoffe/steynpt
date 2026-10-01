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
      "Account: je naam, e-mailadres, telefoonnummer, doel, gekozen pakket, puntensaldo en uitnodigingen.",
      "Check-ins: je wekelijkse scores voor energie, slaap en voeding, aantal trainingen, eventueel je gewicht en opmerkingen. Dit zijn gezondheidsgegevens; we verwerken ze alleen met jouw uitdrukkelijke toestemming en uitsluitend voor je coaching.",
    ],
  },
  {
    title: "Waarom?",
    body: [
      "Om contact met je op te nemen, je coaching te verzorgen, je voortgang te volgen en het Rewards-programma uit te voeren. Nieuwsbrieven en acties ontvang je alleen als je daar zelf voor kiest.",
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
      "We verkopen je gegevens nooit. We delen ze alleen met partijen die nodig zijn om de website te laten werken (zoals hosting), onder passende afspraken.",
    ],
  },
  {
    title: "Beveiliging",
    body: [
      "Wachtwoorden worden versleuteld opgeslagen en je sessie is beveiligd met een veilige cookie. Alleen Steyn heeft toegang tot je coachinggegevens.",
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
