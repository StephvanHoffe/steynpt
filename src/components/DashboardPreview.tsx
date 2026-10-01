import { CalendarCheck, Flame, Gift } from "lucide-react";

/** Visuele preview van het ledendashboard voor de marketingpagina's. */
export function DashboardPreview() {
  return (
    <div className="relative mx-auto w-full max-w-md" aria-hidden="true">
      <div className="absolute -inset-6 rounded-[2rem] bg-[radial-gradient(circle_at_30%_20%,rgb(243_207_199/0.7),transparent_60%)] blur-2xl" />
      <div className="relative rounded-[1.5rem] border border-line bg-white p-5 text-ink shadow-xl">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-xs text-muted">Goedemorgen</p>
            <p className="display text-2xl">Hoi Lisa!</p>
          </div>
          <span className="rounded-full bg-petal px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">Zilver</span>
        </div>
        <div className="mt-5 grid grid-cols-2 gap-3">
          <div className="rounded-2xl bg-paper p-4">
            <p className="text-xs text-muted">Punten</p>
            <p className="display mt-1 text-4xl text-rose">865</p>
            <div className="mt-3 h-1.5 rounded-full bg-sand">
              <div className="h-full w-[37%] rounded-full bg-rose" />
            </div>
            <p className="mt-2 text-[11px] text-muted">635 tot Goud</p>
          </div>
          <div className="rounded-2xl bg-paper p-4">
            <p className="flex items-center gap-1.5 text-xs text-muted">
              <Flame className="size-3.5 text-rose" /> Streak
            </p>
            <p className="display mt-1 text-4xl">6 wk</p>
            <p className="mt-3 text-[11px] text-muted">Nog 2 weken tot +100 bonus</p>
          </div>
        </div>
        <div className="mt-3 rounded-2xl bg-paper p-4">
          <p className="flex items-center gap-2 text-sm font-semibold">
            <CalendarCheck className="size-4 text-rose" /> Check-in week 40
          </p>
          <div className="mt-3 grid grid-cols-3 gap-2 text-center text-[11px]">
            {[
              ["Energie", "4/5"],
              ["Slaap", "5/5"],
              ["Trainingen", "3×"],
            ].map(([k, v]) => (
              <div key={k} className="rounded-xl bg-blush px-2 py-2">
                <p className="text-muted">{k}</p>
                <p className="mt-0.5 text-base font-semibold">{v}</p>
              </div>
            ))}
          </div>
          <p className="mt-3 rounded-xl border border-rose/30 bg-blush p-3 text-xs leading-relaxed text-ink/85">
            <span className="font-semibold text-rose">Steyn:</span> Sterke week! Deze week voeren we het gewicht bij de
            squat op met 2,5 kg.
          </p>
        </div>
        <div className="mt-3 flex items-center justify-between rounded-2xl bg-petal p-4 text-ink">
          <p className="flex items-center gap-2 text-sm font-semibold">
            <Gift className="size-4" /> Nodig een vriend uit
          </p>
          <p className="text-sm font-bold">+250 punten</p>
        </div>
      </div>
    </div>
  );
}
