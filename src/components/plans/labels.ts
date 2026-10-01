import type { PlanStatus, PlanType } from "@/lib/db";

export const PLAN_TYPE_LABEL: Record<PlanType, string> = { training: "Trainingsschema", voeding: "Voedingsschema" };

export const PLAN_STATUS: Record<PlanStatus, { label: string; tone: string }> = {
  genereren: { label: "AI is bezig", tone: "bg-sand text-ink" },
  fout: { label: "Mislukt", tone: "bg-danger/10 text-danger" },
  concept: { label: "Te controleren", tone: "bg-petal text-ink" },
  gepubliceerd: { label: "Gepubliceerd", tone: "bg-rose text-white" },
  vervangen: { label: "Oude versie", tone: "bg-sand text-muted" },
};

/** Een generatie die na 10 minuten nog loopt is vrijwel zeker afgebroken (bijv. herstart van de server). */
export const isStuck = (status: PlanStatus, updatedAt: Date) => status === "genereren" && Date.now() - updatedAt.getTime() > 10 * 60 * 1000;
