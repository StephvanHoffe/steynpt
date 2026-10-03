import { STAGES, type Stage, type StageGroup } from "@/lib/plans/pipeline";

// Kleur per fase; de tekst van het label maakt het verschil ook zonder kleur duidelijk.
export const GROUP_TONE: Record<StageGroup, { badge: string; dot: string }> = {
  wacht: { badge: "bg-danger/10 text-danger", dot: "bg-danger" },
  controleren: { badge: "bg-accent-tint text-accent", dot: "bg-accent" },
  binnenkort: { badge: "bg-[#fdf3e1] text-[#8a4b08]", dot: "bg-[#c97a12]" },
  ingepland: { badge: "bg-[#eef0ff] text-[#3730a3]", dot: "bg-[#4f46e5]" },
  actief: { badge: "bg-success/10 text-success", dot: "bg-success" },
  intake: { badge: "bg-surface text-ink", dot: "bg-muted" },
  pauze: { badge: "bg-surface text-muted", dot: "bg-line" },
};

export function StageBadge({ stage, coachingStatus }: { stage: Stage; coachingStatus?: string }) {
  const { group, label } = STAGES[stage];
  const tone = stage === "mislukt" ? GROUP_TONE.wacht.badge : GROUP_TONE[group].badge;
  return (
    <span className={`inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ${tone}`}>
      {stage === "pauze" && coachingStatus === "gestopt" ? "Gestopt" : label}
    </span>
  );
}

const dayFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", timeZone: "UTC" });
const dayLongFmt = new Intl.DateTimeFormat("nl-NL", { weekday: "long", day: "numeric", month: "long", timeZone: "UTC" });
/** "14 nov." voor een dag "YYYY-MM-DD". */
export const formatPlanDay = (day: string) => dayFmt.format(new Date(`${day}T12:00:00Z`));
export const formatPlanDayLong = (day: string) => dayLongFmt.format(new Date(`${day}T12:00:00Z`));
