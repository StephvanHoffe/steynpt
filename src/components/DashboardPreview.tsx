import { CalendarCheck, CalendarDays, LineChart } from "lucide-react";

/** Visuele preview van Mijn omgeving voor de marketingpagina's (voorbeeldgegevens). */
export function DashboardPreview() {
  const weights = [82.4, 81.9, 81.1, 80.6, 80.2, 79.4];
  const min = 79;
  const max = 83;
  const points = weights.map((w, i) => `${(i / (weights.length - 1)) * 100},${((max - w) / (max - min)) * 40 + 4}`).join(" ");
  return (
    <div className="relative mx-auto w-full max-w-md" aria-hidden="true">
      <div className="rounded-xl border border-line bg-white p-5 shadow-xl shadow-ink/5">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs text-muted">Mijn omgeving</p>
            <p className="display text-xl">Hoi Lisa</p>
          </div>
          <span className="rounded bg-ink px-2 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">Pro</span>
        </div>

        <div className="mt-4 rounded-lg border border-line p-4">
          <p className="flex items-center gap-2 text-xs font-semibold text-muted">
            <LineChart className="size-3.5" /> Gewicht
          </p>
          <div className="mt-1 flex items-end justify-between gap-4">
            <p className="text-2xl font-semibold tabular-nums">
              79,4 <span className="text-sm font-normal text-muted">kg</span>
            </p>
            <p className="text-xs text-muted">−3,0 kg sinds 1 aug</p>
          </div>
          <svg viewBox="0 0 100 48" preserveAspectRatio="none" className="mt-2 h-14 w-full">
            <polygon points={`0,48 ${points} 100,48`} fill="var(--color-accent)" opacity="0.1" />
            <polyline points={points} fill="none" stroke="var(--color-accent)" strokeWidth="2" vectorEffect="non-scaling-stroke" strokeLinejoin="round" />
          </svg>
        </div>

        <div className="mt-3 grid grid-cols-2 gap-3">
          <div className="rounded-lg bg-surface p-3">
            <p className="flex items-center gap-1.5 text-xs text-muted">
              <CalendarDays className="size-3.5" /> Volgende afspraak
            </p>
            <p className="mt-1 text-sm font-semibold">Ma 12 okt, 07:30</p>
            <p className="text-xs text-muted">Personal training · Workout</p>
          </div>
          <div className="rounded-lg bg-surface p-3">
            <p className="flex items-center gap-1.5 text-xs text-muted">
              <CalendarCheck className="size-3.5" /> Check-in week 41
            </p>
            <p className="mt-1 text-sm font-semibold">Energie 4/5</p>
            <p className="text-xs text-muted">3 trainingen</p>
          </div>
        </div>

        <p className="mt-3 rounded-lg border border-accent/30 bg-accent-tint p-3 text-xs leading-relaxed">
          <span className="font-semibold text-accent">Steyn:</span> Sterke week. We voeren het gewicht bij de squat op met 2,5 kg.
        </p>
      </div>
    </div>
  );
}
