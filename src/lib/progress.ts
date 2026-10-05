import { z } from "zod";

// Metingen die Steyn invoert. Volgorde = volgorde in tabellen en grafieken.
export const MEASUREMENT_FIELDS = [
  { key: "weight", label: "Gewicht", unit: "kg", min: 30, max: 300 },
  { key: "bodyFat", label: "Vetpercentage", unit: "%", min: 2, max: 70 },
  { key: "muscleMass", label: "Spiermassa", unit: "kg", min: 10, max: 150 },
  { key: "waist", label: "Taille", unit: "cm", min: 40, max: 200 },
  { key: "hip", label: "Heup", unit: "cm", min: 50, max: 200 },
  { key: "chest", label: "Borst", unit: "cm", min: 50, max: 200 },
  { key: "arm", label: "Bovenarm", unit: "cm", min: 15, max: 70 },
  { key: "thigh", label: "Bovenbeen", unit: "cm", min: 25, max: 110 },
] as const;

export type MeasurementKey = (typeof MEASUREMENT_FIELDS)[number]["key"];
export type MeasurementValues = Record<MeasurementKey, number | null>;

const optionalNumber = (label: string, min: number, max: number) =>
  z.preprocess(
    (v) => (v === undefined || v === null ? null : typeof v === "string" ? (v.trim() === "" ? null : v.replace(",", ".").trim()) : v),
    z.union(
      [z.null(), z.coerce.number({ error: `${label}: vul een getal in` }).min(min, `${label} lijkt niet te kloppen`).max(max, `${label} lijkt niet te kloppen`)],
      { error: `${label}: vul een getal in` },
    ),
  );

export const measurementSchema = z
  .object({
    measuredAt: z.iso.date("Kies een datum"),
    note: z
      .string("Controleer de notitie")
      .trim()
      .max(1000, "De notitie mag maximaal 1000 tekens hebben")
      .optional()
      .transform((v) => v || null),
    ...(Object.fromEntries(MEASUREMENT_FIELDS.map((f) => [f.key, optionalNumber(f.label, f.min, f.max)])) as Record<
      MeasurementKey,
      ReturnType<typeof optionalNumber>
    >),
  })
  .refine((m) => MEASUREMENT_FIELDS.some((f) => m[f.key] != null), { message: "Vul minimaal één meetwaarde in", path: ["weight"] });

export const formatNumber = (n: number, digits = 1) => n.toLocaleString("nl-NL", { maximumFractionDigits: digits });

/** Verschil tussen eerste en laatste meting per waarde (alleen als er minstens twee metingen zijn). */
export function progressSummary<T extends Partial<MeasurementValues> & { measuredAt: Date }>(rows: T[]) {
  const sorted = [...rows].sort((a, b) => a.measuredAt.getTime() - b.measuredAt.getTime());
  return MEASUREMENT_FIELDS.map((field) => {
    const points = sorted.filter((r) => r[field.key] != null).map((r) => ({ date: r.measuredAt, value: r[field.key] as number }));
    const latest = points.at(-1) ?? null;
    const first = points[0] ?? null;
    return {
      ...field,
      points,
      latest,
      change: latest && first && points.length > 1 ? latest.value - first.value : null,
      since: first?.date ?? null,
    };
  }).filter((f) => f.points.length > 0);
}

/** "Mooie" astikken voor een grafiek, bijvoorbeeld 70, 72, 74, 76. */
export function niceTicks(min: number, max: number, count = 4) {
  if (min === max) {
    const pad = Math.max(Math.abs(min) * 0.05, 1);
    min -= pad;
    max += pad;
  }
  const raw = (max - min) / count;
  const magnitude = 10 ** Math.floor(Math.log10(raw));
  const step = [1, 2, 2.5, 5, 10].map((m) => m * magnitude).find((s) => s >= raw) ?? 10 * magnitude;
  const start = Math.floor(min / step) * step;
  const end = Math.ceil(max / step) * step;
  const ticks: number[] = [];
  for (let t = start; t <= end + step / 1000; t += step) ticks.push(Number(t.toFixed(6)));
  return ticks;
}
