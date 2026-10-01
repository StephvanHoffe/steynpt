import type { CoachingStatus } from "@/lib/db/schema";

export const COACHING_LABEL: Record<CoachingStatus, string> = {
  geen: "Geen coaching",
  aangevraagd: "Aangevraagd",
  actief: "Actief",
  gepauzeerd: "Gepauzeerd",
  gestopt: "Gestopt",
};
