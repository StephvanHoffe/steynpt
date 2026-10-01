// Vriendenactie voor online coaching. Beloningen zijn een voorstel en hier centraal aan te passen.

export const REFERRAL = {
  /** Wat de nieuwe klant krijgt bij aanmelding via een uitnodiging. */
  friendReward: "50% korting op de eerste maand online coaching",
  /** Wat de uitnodiger krijgt zodra de vriend daadwerkelijk start. */
  referrerReward: "50% korting op een maand online coaching",
  headline: "Samen 50% korting",
};

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
