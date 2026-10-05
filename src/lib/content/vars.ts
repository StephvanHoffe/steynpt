// Automatische waarden: {code} in een tekst wordt vervangen door een waarde die op één plek wordt ingesteld.
// Zonder paginadefinities (alleen types), zodat ook het bewerkscherm in de browser dit kan gebruiken.
import type { PageValues } from "./fields";
import type { algemeen } from "./pages/algemeen";
import type { pakketten } from "./pages/pakketten";
import { priceNumber } from "./values";

export const VARS = [
  { key: "actie", label: "Naam van de vriendenactie", page: "algemeen", section: "vriendenactie" },
  { key: "vriendkorting", label: "Wat de vriend krijgt", page: "algemeen", section: "vriendenactie" },
  { key: "jouwkorting", label: "Wat de uitnodiger krijgt", page: "algemeen", section: "vriendenactie" },
  { key: "ademprijs", label: "Prijs ademsessie 1-op-1", page: "pakketten", section: "adem" },
  { key: "ademduur", label: "Duur ademsessie 1-op-1", page: "pakketten", section: "adem" },
  { key: "online-vanaf", label: "Laagste prijs online coaching", page: "pakketten", section: "online" },
] as const;
export const VAR_KEYS = VARS.map((v) => v.key);
export type VarInfo = { key: string; label: string; page: string; section: string };

const formatPrice = (n: number) => (Number.isInteger(n) ? String(n) : n.toFixed(2).replace(".", ","));

export function computeVars(shared: PageValues<typeof algemeen>, prices: PageValues<typeof pakketten>): Record<string, string> {
  const online = prices.online.plans.map((p) => priceNumber(p.price)).filter((n) => Number.isFinite(n));
  return {
    actie: shared.vriendenactie.headline,
    vriendkorting: shared.vriendenactie.friendReward,
    jouwkorting: shared.vriendenactie.referrerReward,
    ademprijs: prices.adem.price,
    ademduur: prices.adem.duration,
    "online-vanaf": online.length ? formatPrice(Math.min(...online)) : "",
  };
}
