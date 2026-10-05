// Centrale gegevens van SteynPT. Prijzen van bestaande diensten zijn overgenomen van de vorige website (steynpt.nl).
// Pakketten, prijzen en adres zijn hier de standaardwaarden: Steyn past ze aan in het beheer onder Website-teksten
// (src/lib/content). Toon ze op de site daarom via src/lib/content/texts.ts, niet rechtstreeks vanuit dit bestand.

export const SITE = {
  name: "SteynPT",
  url: process.env.NEXT_PUBLIC_SITE_URL ?? "https://www.steynpt.nl",
  description:
    "Personal trainer bij Gymbase aan de Overtoom in Amsterdam Oud-West, vlak bij het Vondelpark. 1-op-1 training, online coaching, voedingscoaching en ademcoaching.",
  instagram: { url: "https://www.instagram.com/bigtimesteyn/", handle: "@bigtimesteyn" },
};

// `desktop: false` = alleen in het mobiele menu en de footer.
export const NAV: { href: string; label: string; highlight?: boolean; desktop?: boolean }[] = [
  { href: "/online-coaching", label: "Online coaching", highlight: true },
  { href: "/personal-training", label: "Personal training" },
  { href: "/ademcoaching", label: "Ademcoaching" },
  { href: "/voedingscoaching", label: "Voeding" },
  { href: "/tarieven", label: "Tarieven" },
  { href: "/account/agenda", label: "Afspraak maken" },
  { href: "/over-steyn", label: "Over Steyn" },
];

// Standaardadres; Steyn past het aan in het beheer onder Website-teksten.
export const LOCATIONS = [{ name: "Gymbase", street: "Overtoom 371-w", city: "1054 JN Amsterdam" }];

/** Telefoonnummer voor een tel:-link: alleen cijfers en een + vooraan ("020 123 4567" → "0201234567"). */
export const telHref = (phone: string) => (phone.trim().startsWith("+") ? "+" : "") + phone.replace(/\D+/g, "");

/** Routelink naar Google Maps voor een adres. */
export const mapsUrl = (street: string, city: string) =>
  `https://maps.google.com/?q=${encodeURIComponent(`${street}, ${city}`).replace(/%20/g, "+").replace(/%2C/g, ",")}`;

export type PriceCard = {
  name: string;
  label: string;
  price: string;
  unit?: string;
  features: string[];
  note?: string;
  featured?: boolean;
};

// Ademcoaching: 1-op-1 een vast tarief, groepssessies op aanvraag.
export const BREATHWORK_SESSION = {
  minutes: 90,
  duration: "1,5 uur",
  price: "210",
  features: [
    "Persoonlijke begeleiding door Steyn",
    "Afgestemd op jouw vraag: stress, slaap, sport of herstel",
    "Oefeningen om zelf mee verder te gaan",
    "Geen ervaring nodig",
  ],
};

export const PT_PRICES: PriceCard[] = [
  {
    name: "Losse training",
    label: "Personal training",
    price: "120",
    unit: "per uur",
    features: ["Verbeter je gezondheid", "Flexibele tijden", "Stap voor stap naar je doel", "Op maat gemaakt"],
    note: "Duo-training: € 15 toeslag per sessie",
  },
  {
    name: "Introductiepakket",
    label: "10× 1-op-1-training",
    price: "1.050",
    features: ["Intakegesprek", "Start- en eindmeting", "Bewegingsassessment", "Wekelijks voedingsadvies"],
  },
  {
    name: "Gezondheidspakket",
    label: "20× 1-op-1-training",
    price: "2.000",
    features: [
      "Intakegesprek",
      "Start-, tussen- en eindmeting",
      "Bewegingsassessment",
      "Verbeter je gezondheid",
      "Verander je leefstijl",
    ],
    featured: true,
  },
  {
    name: "Lifechanger-pakket",
    label: "40× 1-op-1-training",
    price: "3.800",
    features: [
      "Intakegesprek",
      "Start-, tussen- en eindmeting",
      "Bewegingsassessment",
      "Verbeter je gezondheid",
      "Verander je leefstijl",
    ],
  },
];

export type OnlinePlan = {
  id: string;
  name: string;
  tagline: string;
  price: string;
  features: string[];
  featured?: boolean;
};

// Online coaching is nieuw; deze prijzen zijn een voorstel. De id's zijn vast (ze staan bij leden opgeslagen).
export const ONLINE_PLANS: OnlinePlan[] = [
  {
    id: "online-start",
    name: "Start",
    tagline: "Zelfstandig trainen met een plan dat klopt",
    price: "79",
    features: [
      "Trainingsschema op maat",
      "Voedingsrichtlijnen op basis van je doel",
      "Maandelijkse evaluatie en schema-update",
      "Wekelijkse check-in in je dashboard",
      "Je voortgang en metingen in je dashboard",
    ],
  },
  {
    id: "online-pro",
    name: "Pro",
    tagline: "Wekelijkse sturing voor maximaal resultaat",
    price: "129",
    features: [
      "Alles uit Start",
      "Persoonlijk voedingsplan (macro's & micro's)",
      "Wekelijkse feedback van Steyn op je check-in",
      "Tussentijds contact op werkdagen",
      "Schema-updates wanneer jij ze nodig hebt",
    ],
    featured: true,
  },
  {
    id: "online-performance",
    name: "Performance",
    tagline: "Voor specifieke doelen en (top)sporters",
    price: "199",
    features: [
      "Alles uit Pro",
      "Twee videocalls per maand",
      "Sportspecifieke periodisering",
      "Techniekanalyse op basis van je video's",
      "Ademhalingsprotocol voor focus en herstel",
    ],
  },
];

/** Bestaat dit pakket? Voor controle van een id; toon naam en prijs via src/lib/content/texts.ts (aanpasbaar in het beheer). */
export function getOnlinePlan(id: string | null | undefined) {
  return ONLINE_PLANS.find((p) => p.id === id);
}

export const GOALS = [
  { id: "afvallen", label: "Afvallen" },
  { id: "spieropbouw", label: "Spiermassa opbouwen" },
  { id: "fitter", label: "Fitter en meer energie" },
  { id: "leefstijl", label: "Gezondere leefstijl" },
  { id: "prestatie", label: "Sportprestatie / topsport" },
  { id: "herstel", label: "Sterker na blessure of klachten" },
];

export const INTERESTS = [
  { id: "online-coaching", label: "Online coaching" },
  { id: "personal-training", label: "Personal training (1-op-1)" },
  { id: "topsport", label: "Begeleiding specifiek doel / topsport" },
  { id: "ademcoaching", label: "Ademsessie 1-op-1" },
  { id: "ademcoaching-groep", label: "Ademcoaching in groepsverband (op aanvraag)" },
  { id: "voedingscoaching", label: "Voedingsbegeleiding" },
];
