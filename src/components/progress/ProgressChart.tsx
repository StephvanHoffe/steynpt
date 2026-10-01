"use client";

import { useEffect, useId, useRef, useState, type KeyboardEvent, type PointerEvent } from "react";
import { formatNumber, niceTicks } from "@/lib/progress";

// Lijngrafiek voor één meetwaarde (één reeks, één as). Kleuren volgen de ontwerptokens:
// lijn en punten in accent, tekst in ink/muted, raster als haarlijn.

// De breedte van het SVG-coördinatenstelsel volgt de werkelijke breedte, zodat tekst
// altijd op ware grootte blijft (ook in een smalle kolom of op mobiel).
const H = 210;
const M = { top: 16, right: 56, bottom: 30, left: 44 };
const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short" });
const longDateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", year: "numeric" });

export function ProgressChart({ label, unit, points }: { label: string; unit: string; points: { t: number; value: number }[] }) {
  const [active, setActive] = useState<number | null>(null);
  const [W, setW] = useState(560);
  const ref = useRef<HTMLElement>(null);
  const titleId = useId();

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const observer = new ResizeObserver(([entry]) => setW(Math.max(260, Math.round(entry.contentRect.width))));
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  const values = points.map((p) => p.value);
  const ticks = niceTicks(Math.min(...values), Math.max(...values));
  const yMin = ticks[0];
  const yMax = ticks.at(-1)!;
  const tMin = points[0].t;
  const tMax = points.at(-1)!.t;
  const plotW = W - M.left - M.right;
  const plotH = H - M.top - M.bottom;
  const x = (t: number) => (tMax === tMin ? M.left + plotW / 2 : M.left + ((t - tMin) / (tMax - tMin)) * plotW);
  const y = (v: number) => M.top + (1 - (v - yMin) / (yMax - yMin)) * plotH;
  const baseline = M.top + plotH;

  const line = points.map((p, i) => `${i ? "L" : "M"}${x(p.t).toFixed(1)},${y(p.value).toFixed(1)}`).join(" ");
  const area = `${line} L${x(tMax).toFixed(1)},${baseline} L${x(tMin).toFixed(1)},${baseline} Z`;
  const last = points.at(-1)!;
  const xLabels = points.length > 2 ? [points[0], points[Math.floor(points.length / 2)], last] : points;

  function nearest(clientX: number, rect: DOMRect) {
    const svgX = ((clientX - rect.left) / rect.width) * W;
    let best = 0;
    points.forEach((p, i) => {
      if (Math.abs(x(p.t) - svgX) < Math.abs(x(points[best].t) - svgX)) best = i;
    });
    return best;
  }

  const onPointerMove = (e: PointerEvent<SVGSVGElement>) => setActive(nearest(e.clientX, e.currentTarget.getBoundingClientRect()));
  const onKeyDown = (e: KeyboardEvent<SVGSVGElement>) => {
    if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
      e.preventDefault();
      const step = e.key === "ArrowRight" ? 1 : -1;
      setActive((i) => Math.min(points.length - 1, Math.max(0, (i ?? points.length - 1) + step)));
    }
  };

  const point = active != null ? points[active] : null;

  return (
    <figure ref={ref} className="relative">
      <figcaption id={titleId} className="flex items-baseline justify-between gap-3">
        <span className="font-semibold">{label}</span>
        <span className="text-xs text-muted">{unit}</span>
      </figcaption>
      <svg
        viewBox={`0 0 ${W} ${H}`}
        className="mt-2 block h-auto w-full touch-pan-y outline-none focus-visible:ring-2 focus-visible:ring-accent"
        role="img"
        aria-labelledby={titleId}
        aria-describedby={`${titleId}-desc`}
        tabIndex={0}
        onPointerMove={onPointerMove}
        onPointerLeave={() => setActive(null)}
        onFocus={() => setActive(points.length - 1)}
        onBlur={() => setActive(null)}
        onKeyDown={onKeyDown}
      >
        <desc id={`${titleId}-desc`}>
          {`${label}: ${points.length} metingen, van ${formatNumber(points[0].value)} ${unit} op ${longDateFmt.format(tMin)} naar ${formatNumber(last.value)} ${unit} op ${longDateFmt.format(tMax)}. Gebruik de pijltjestoetsen om metingen te bekijken.`}
        </desc>
        {ticks.map((t) => (
          <g key={t}>
            <line x1={M.left} x2={W - M.right} y1={y(t)} y2={y(t)} stroke="var(--color-line)" strokeWidth={1} />
            <text x={M.left - 8} y={y(t)} dy="0.32em" textAnchor="end" className="fill-muted text-[11px]">
              {formatNumber(t)}
            </text>
          </g>
        ))}
        {xLabels.map((p, i) => (
          <text
            key={p.t}
            x={x(p.t)}
            y={H - 8}
            textAnchor={xLabels.length > 1 && i === 0 ? "start" : xLabels.length > 1 && i === xLabels.length - 1 ? "end" : "middle"}
            className="fill-muted text-[11px]"
          >
            {dateFmt.format(p.t)}
          </text>
        ))}

        {points.length > 1 && <path d={area} fill="var(--color-accent)" opacity={0.1} />}
        {points.length > 1 && <path d={line} fill="none" stroke="var(--color-accent)" strokeWidth={2} strokeLinejoin="round" strokeLinecap="round" />}

        {point && <line x1={x(point.t)} x2={x(point.t)} y1={M.top} y2={baseline} stroke="var(--color-ink)" strokeOpacity={0.35} strokeWidth={1} />}

        {points.map((p, i) => (
          <circle key={p.t} cx={x(p.t)} cy={y(p.value)} r={i === active ? 6 : 4} fill="var(--color-accent)" stroke="#fff" strokeWidth={2} />
        ))}

        <text x={x(last.t) + 10} y={y(last.value)} dy="0.32em" className="fill-ink text-[12px] font-semibold">
          {formatNumber(last.value)}
        </text>
      </svg>

      {point && (
        <div
          className="pointer-events-none absolute top-8 z-10 -translate-x-1/2 rounded-md border border-line bg-white px-3 py-2 text-sm shadow-md"
          style={{ left: `${Math.min(85, Math.max(15, (x(point.t) / W) * 100))}%` }}
          role="status"
        >
          <span className="block font-semibold text-ink">
            {formatNumber(point.value)} {unit}
          </span>
          <span className="block text-xs text-muted">{longDateFmt.format(point.t)}</span>
        </div>
      )}
    </figure>
  );
}
