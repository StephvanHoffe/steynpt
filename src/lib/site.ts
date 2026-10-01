// Centrale content van SteynPT. Prijzen van bestaande diensten zijn
// overgenomen van de vorige website (steynpt.nl) en ongewijzigd.

export const SITE = {
  name: "SteynPT",
  url: process.env.NEXT_PUBLIC_SITE_URL ?? "https://www.steynpt.nl",
  description:
    "Personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam. 1-op-1 begeleiding voor een gezondere leefstijl, specifieke doelen en topsporters.",
  instagram: { url: "https://www.instagram.com/bigtimesteyn/", handle: "@bigtimesteyn" },
  responseTime: "Ik streef ernaar om binnen 24 uur contact met je op te nemen.",
  // Balk bovenaan de site; zet op null om hem te verbergen.
  announcement: { text: "Nieuw: online coaching. Nodig een vriend uit en krijg samen 50% korting", href: "/vriend-uitnodigen" } as {
    text: string;
    href: string;
  } | null,
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

export const LOCATIONS = [
  {
    name: "Workout Amsterdam",
    street: "H.J.E. Wenckebachweg 123",
    city: "1096 AM Amsterdam",
    maps: "https://maps.google.com/?q=H.J.E.+Wenckebachweg+123,+1096+AM+Amsterdam",
  },
];

export const ON_LOCATION =
  "Naast onze vaste locatie Workout Amsterdam komen we ook op locatie: in jouw favoriete park of in de kantine van je werk.";

export type PriceCard = {
  name: string;
  label: string;
  price: string;
  unit?: string;
  features: string[];
  note?: string;
  featured?: boolean;
};

export const PT_PRICES: PriceCard[] = [
  {
    name: "Losse training",
    label: "Personal training",
    price: "120",
    unit: "per uur",
    features: ["Verbeter je gezondheid", "Flexibele uren", "Stap-voor-stap aanpak", "Op maat gemaakt"],
    note: "Duo-training: € 15,- toeslag per sessie",
  },
  {
    name: "Introductiepakket",
    label: "10× 1-op-1 training",
    price: "1050",
    features: ["Intakegesprek", "Start- en eindmeting", "Bewegingsassessment", "Wekelijks voedingsadvies"],
  },
  {
    name: "Gezondheidspakket",
    label: "20× 1-op-1 training",
    price: "2000",
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
    label: "40× 1-op-1 training",
    price: "3800",
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

// Online coaching is nieuw; deze prijzen zijn een voorstel en kunnen hier
// centraal worden aangepast.
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
      "Contact via chat op werkdagen",
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
  { id: "ademcoaching", label: "Ademcoaching in groepsverband" },
  { id: "voedingscoaching", label: "Voedingsbegeleiding" },
];

export const REVIEWS = [
  {
    quote: "Steyn heeft mij geleerd dat het niet alleen gaat om afvallen, maar om bewustwording van je leefstijl.",
    name: "Miranda 'd Weegman",
    role: "Operatieassistente",
  },
  {
    quote: "Ik ben al jaren bezig met afvallen maar bleef altijd rond hetzelfde gewicht. Sinds ik met Steyn train behaal ik eindelijk resultaat.",
    name: "Steve van Maanen",
    role: "Bakker",
  },
];

export const EXPERTISE = [
  "Personal Trainer",
  "Orthomoleculair voedingstherapeut",
  "Leefstijl- en vitaliteitscoaching",
  "Ademcoaching",
  "Powerliften",
  "Boksen",
  "CrossFit",
  "Sportspecifieke training",
];

export const METHOD_STEPS = [
  {
    title: "Intake",
    text: "We beginnen met een gesprek over jouw doelen, je achtergrond en wat je tot nu toe hebt geprobeerd.",
  },
  {
    title: "Nulmeting",
    text: "We wegen, meten en bewegen: zo zien we precies waar we aan moeten werken.",
  },
  {
    title: "Plan op maat",
    text: "Je krijgt een persoonlijk schema en voedingsplan met de ideale balans in macro- en micronutriënten.",
  },
  {
    title: "Coaching",
    text: "Ook buiten de trainingen hebben we contact. We sturen bij tot je doel bereikt is, en daarna.",
  },
];
