import { ArrowDownRight, ArrowUpRight, Minus } from "lucide-react";
import type { Measurement } from "@/lib/db";
import { formatNumber, MEASUREMENT_FIELDS, progressSummary } from "@/lib/progress";
import { ProgressChart } from "./ProgressChart";

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric", timeZone: "Europe/Amsterdam" });
const shortFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", timeZone: "Europe/Amsterdam" });
const shortDate = (d: Date) => (d.getFullYear() === new Date().getFullYear() ? shortFmt.format(d) : dateFmt.format(d));

/** Stat tiles + grafieken (en optioneel de volledige tabel) van de metingen van één klant. */
export function ProgressOverview({ rows, charts = "all", table = false }: { rows: Measurement[]; charts?: "all" | "main"; table?: boolean }) {
  const summary = progressSummary(rows);
  const withTrend = summary.filter((s) => s.points.length > 1);
  const shown = charts === "main" ? withTrend.slice(0, 2) : withTrend;
  const sorted = [...rows].sort((a, b) => b.measuredAt.getTime() - a.measuredAt.getTime());
  const columns = MEASUREMENT_FIELDS.filter((f) => rows.some((r) => r[f.key] != null));

  return (
    <div className="grid gap-6">
      <dl className={`grid grid-cols-2 gap-3 ${charts === "main" ? "" : "sm:grid-cols-4"}`}>
        {summary.slice(0, 4).map((s) => {
          const Icon = s.change == null || s.change === 0 ? Minus : s.change < 0 ? ArrowDownRight : ArrowUpRight;
          return (
            <div key={s.key} className="rounded-lg border border-line p-4">
              <dt className="text-xs text-muted">{s.label}</dt>
              <dd className="mt-1 text-2xl font-semibold tabular-nums">
                {formatNumber(s.latest!.value)} <span className="text-sm font-normal text-muted">{s.unit}</span>
              </dd>
              <dd className="mt-1 flex items-center gap-1 text-xs text-muted">
                {s.change != null ? (
                  <>
                    <Icon className="size-3.5" aria-hidden="true" />
                    {s.change > 0 ? "+" : ""}
                    {formatNumber(s.change)} {s.unit} sinds {shortDate(s.since!)}
                  </>
                ) : (
                  <>Gemeten op {shortDate(s.latest!.date)}</>
                )}
              </dd>
            </div>
          );
        })}
      </dl>

      {shown.length > 0 && (
        <div className="grid gap-6 md:grid-cols-2">
          {shown.map((s) => (
            <div key={s.key} className="rounded-lg border border-line p-4">
              <ProgressChart label={s.label} unit={s.unit} points={s.points.map((p) => ({ t: p.date.getTime(), value: p.value }))} />
            </div>
          ))}
        </div>
      )}

      {table && (
        <div className="relative overflow-x-auto rounded-lg border border-line">
          <table className="w-full min-w-[560px] text-left text-sm">
            <caption className="sr-only">Alle metingen</caption>
            <thead className="bg-surface text-xs uppercase tracking-wider text-muted">
              <tr>
                <th className="px-4 py-2.5 font-semibold">Datum</th>
                {columns.map((c) => (
                  <th key={c.key} className="px-4 py-2.5 font-semibold">
                    {c.label} <span className="normal-case">({c.unit})</span>
                  </th>
                ))}
                <th className="px-4 py-2.5 font-semibold">Notitie</th>
              </tr>
            </thead>
            <tbody>
              {sorted.map((r) => (
                <tr key={r.id} className="border-t border-line align-top">
                  <td className="whitespace-nowrap px-4 py-2.5 font-medium">{dateFmt.format(r.measuredAt)}</td>
                  {columns.map((c) => (
                    <td key={c.key} className="px-4 py-2.5 tabular-nums">
                      {r[c.key] != null ? formatNumber(r[c.key] as number) : "–"}
                    </td>
                  ))}
                  <td className="px-4 py-2.5 text-muted">{r.note}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
