// Regels van het SteynPT Rewards-programma. Pure functies zonder database,
// zodat de website-teksten en de puntentoekenning altijd dezelfde bron gebruiken.

export const POINTS = {
  welcome: 100,
  invitedBonus: 100,
  profileComplete: 50,
  intake: 50,
  weeklyCheckIn: 25,
  streakBonus: 100,
  streakLength: 4,
  friendSignup: 250,
  friendStarts: 500,
  friendsMilestone: 750,
  friendsMilestoneCount: 3,
} as const;

export const POINT_TYPES = {
  welcome: "welkom",
  invitedBonus: "uitgenodigd",
  profileComplete: "profiel",
  intake: "intake",
  checkIn: "check-in",
  streak: "streak",
  friendSignup: "vriend-aangemeld",
  friendStarts: "vriend-gestart",
  friendsMilestone: "vrienden-mijlpaal",
  redemption: "inwisseling",
  refund: "terugboeking",
  correction: "correctie",
} as const;

export type Tier = {
  id: string;
  name: string;
  minPoints: number;
  perks: string[];
};

// Niveaus zijn gebaseerd op het totaal ooit verdiende punten, zodat inwisselen
// je niveau niet verlaagt.
export const TIERS: Tier[] = [
  {
    id: "brons",
    name: "Brons",
    minPoints: 0,
    perks: ["Toegang tot je persoonlijke dashboard", "Punten voor elke wekelijkse check-in", "Persoonlijke uitnodigingslink"],
  },
  {
    id: "zilver",
    name: "Zilver",
    minPoints: 500,
    perks: ["Alles van Brons", "Als eerste toegang tot nieuwe ademcoaching-sessies", "Maandelijkse tip van Steyn in je dashboard"],
  },
  {
    id: "goud",
    name: "Goud",
    minPoints: 1500,
    perks: ["Alles van Zilver", "Gratis halfjaarlijkse meting (vetpercentage & omvang)", "Voorrang bij het inplannen van PT-sessies"],
  },
  {
    id: "platina",
    name: "Platina",
    minPoints: 3000,
    perks: ["Alles van Goud", "Jaarlijkse 1-op-1 strategiesessie met Steyn", "Exclusieve SteynPT community-events"],
  },
];

export function getTierProgress(lifetimePoints: number) {
  const index = TIERS.findLastIndex((t) => lifetimePoints >= t.minPoints);
  const current = TIERS[Math.max(index, 0)];
  const next = TIERS[index + 1] ?? null;
  const progress = next
    ? Math.min(1, (lifetimePoints - current.minPoints) / (next.minPoints - current.minPoints))
    : 1;
  return { current, next, progress, pointsToNext: next ? next.minPoints - lifetimePoints : 0 };
}

export type Reward = {
  id: string;
  title: string;
  description: string;
  cost: number;
};

export const REWARDS: Reward[] = [
  {
    id: "korting-online-10",
    title: "10% korting",
    description: "Op je volgende maand online coaching.",
    cost: 400,
  },
  {
    id: "ademsessie",
    title: "Ademcoaching-sessie",
    description: "Een gratis plek bij een groepssessie ademcoaching.",
    cost: 600,
  },
  {
    id: "voedingscheck",
    title: "Voedingscheck",
    description: "30 minuten 1-op-1 (online) je voeding doorlopen met Steyn.",
    cost: 800,
  },
  {
    id: "pt-sessie",
    title: "Personal training",
    description: "Een gratis 1-op-1 training van 60 minuten in Amsterdam.",
    cost: 1500,
  },
  {
    id: "maand-online",
    title: "Maand online coaching",
    description: "Een volledige maand online coaching cadeau.",
    cost: 2500,
  },
];

export function getReward(id: string) {
  return REWARDS.find((r) => r.id === id);
}

export type Promotion = {
  id: string;
  title: string;
  description: string;
  startsAt: string;
  endsAt: string | null;
  welcomeMultiplier?: number;
};

// Acties: pas data en teksten hier aan. Actieve acties worden automatisch
// toegepast bij registratie en getoond op de website.
export const PROMOTIONS: Promotion[] = [
  {
    id: "lancering-online",
    title: "Lanceringsactie online coaching",
    description: "Maak vóór 1 januari 2027 je account aan en ontvang dubbele welkomstpunten.",
    startsAt: "2026-10-01",
    endsAt: "2027-01-01",
    welcomeMultiplier: 2,
  },
  {
    id: "breng-een-vriend",
    title: "Samen sterker",
    description: `Jij en je vriend krijgen allebei bonuspunten. Starten ${POINTS.friendsMilestoneCount} vrienden? Dan krijg je er nog eens ${POINTS.friendsMilestone} punten bovenop.`,
    startsAt: "2026-10-01",
    endsAt: null,
  },
];

export function isPromotionActive(promo: Promotion, now = new Date()) {
  const start = new Date(`${promo.startsAt}T00:00:00+02:00`);
  const end = promo.endsAt ? new Date(`${promo.endsAt}T00:00:00+01:00`) : null;
  return now >= start && (!end || now < end);
}

export function activePromotions(now = new Date()) {
  return PROMOTIONS.filter((p) => isPromotionActive(p, now));
}

export function welcomePoints(now = new Date()) {
  const multiplier = activePromotions(now).reduce((m, p) => Math.max(m, p.welcomeMultiplier ?? 1), 1);
  return POINTS.welcome * multiplier;
}

/** ISO-weeknummer, bijvoorbeeld "2026-W40". */
export function isoWeekKey(date: Date) {
  const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
  const day = d.getUTCDay() || 7;
  d.setUTCDate(d.getUTCDate() + 4 - day);
  const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
  const week = Math.ceil(((d.getTime() - yearStart.getTime()) / 86400000 + 1) / 7);
  return `${d.getUTCFullYear()}-W${String(week).padStart(2, "0")}`;
}

/**
 * Aantal opeenvolgende weken met een check-in, eindigend in deze week
 * (of vorige week, als deze week nog geen check-in heeft).
 */
export function checkInStreak(weeks: string[], now = new Date()) {
  const done = new Set(weeks);
  const cursor = new Date(now);
  if (!done.has(isoWeekKey(cursor))) cursor.setDate(cursor.getDate() - 7);
  let streak = 0;
  while (done.has(isoWeekKey(cursor))) {
    streak++;
    cursor.setDate(cursor.getDate() - 7);
  }
  return streak;
}

const CODE_ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

export function makeReferralCode(firstName: string, random: () => number = Math.random) {
  const base =
    firstName
      .normalize("NFD")
      .replace(/[^A-Za-z]/g, "")
      .toUpperCase()
      .slice(0, 8) || "STEYN";
  let suffix = "";
  for (let i = 0; i < 4; i++) suffix += CODE_ALPHABET[Math.floor(random() * CODE_ALPHABET.length)];
  return `${base}-${suffix}`;
}

export function normalizeReferralCode(code: string | null | undefined) {
  const clean = (code ?? "").trim().toUpperCase();
  return /^[A-Z]{1,8}-[A-Z0-9]{4}$/.test(clean) ? clean : null;
}
