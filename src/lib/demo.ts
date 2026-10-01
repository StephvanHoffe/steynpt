// Demoversie (DEMO_MODE=1): voorbeeldgegevens, inloggen met één klik en niet vindbaar in zoekmachines.
// Lokaal of op hosting: zet DEMO_MODE=1 en draai `npm run demo:start` (zie README).

export const DEMO_MODE = process.env.DEMO_MODE === "1";

export const DEMO_ACCOUNTS = [
  { id: "klant", email: "lisa.jansen@example.com", label: "Klant", role: "Klant", name: "Lisa Jansen", description: "Online coaching Pro, met afspraken, schema's en voortgang", next: "/account" },
  { id: "steyn", email: "steyn@example.com", label: "Steyn", role: "Beheer", name: "Steyn van Leeuwen", description: "Beheer: agenda, leden, metingen en schema's controleren", next: "/admin" },
] as const;

/** De vaste demo-accounts mogen niet worden verwijderd of van wachtwoord wisselen. */
export const isDemoAccount = (email: string) => DEMO_MODE && DEMO_ACCOUNTS.some((a) => a.email === email);
