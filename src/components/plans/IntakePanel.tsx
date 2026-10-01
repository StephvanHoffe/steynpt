import { AlertTriangle } from "lucide-react";
import { estimateTargets, intakeSummary, type IntakeData } from "@/lib/intake";

const SECTIONS = [
  ["algemeen", "Algemeen"],
  ["training", "Training"],
  ["voeding", "Voeding"],
] as const;

const IMPORTANT = new Set(["Allergieën en intoleranties", "Medische aandachtspunten", "Blessures of beperkingen", "Eetstijl"]);

/** Intake van een lid voor Steyn, met de belangrijkste aandachtspunten uitgelicht. */
export function IntakePanel({ intake, updatedAt }: { intake: IntakeData; updatedAt?: Date }) {
  const rows = intakeSummary(intake);
  const t = estimateTargets(intake);
  return (
    <div className="grid gap-5">
      {updatedAt && <p className="text-xs text-muted">Laatst bijgewerkt {updatedAt.toLocaleString("nl-NL", { dateStyle: "medium", timeStyle: "short" })}</p>}
      {SECTIONS.map(([id, title]) => (
        <section key={id}>
          <h3 className="text-xs font-bold uppercase tracking-[0.14em] text-accent">{title}</h3>
          <dl className="mt-2 divide-y divide-line text-sm">
            {rows
              .filter((r) => r.section === id)
              .map((r) => (
                <div key={r.label} className={`grid grid-cols-[9rem_1fr] gap-3 py-2 ${IMPORTANT.has(r.label) ? "rounded-md bg-accent-tint px-2" : ""}`}>
                  <dt className="text-muted">
                    {IMPORTANT.has(r.label) && <AlertTriangle className="mr-1 inline size-3.5 text-accent" aria-hidden="true" />}
                    {r.label}
                  </dt>
                  <dd className="whitespace-pre-line font-medium">{r.value}</dd>
                </div>
              ))}
          </dl>
        </section>
      ))}
      <section className="rounded-xl bg-surface p-4 text-sm">
        <h3 className="font-semibold">Berekende richtwaarden</h3>
        <p className="mt-1 text-muted">
          Rust {t.bmr} kcal · onderhoud {t.maintenance} kcal · doel {t.calories} kcal · eiwit {t.protein} g · koolhydraten {t.carbs} g · vet {t.fat} g
        </p>
      </section>
    </div>
  );
}
