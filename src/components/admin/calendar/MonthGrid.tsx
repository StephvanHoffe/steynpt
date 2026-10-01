import Link from "next/link";
import { agendaHref, type AgendaParams, type CalendarEvent, typeColor } from "./shared";

const HEAD = ["Ma", "Di", "Wo", "Do", "Vr", "Za", "Zo"];
const MAX = 3;
const hhmm = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;

/** Maandoverzicht: per dag de eerste afspraken, de rest via de dagweergave. */
export function MonthGrid({
  params,
  days,
  events,
  blockedDays,
  today,
}: {
  params: AgendaParams;
  days: string[];
  events: CalendarEvent[];
  blockedDays: Map<string, string | null>;
  today: string;
}) {
  const month = params.day.slice(0, 7);
  return (
    <div className="overflow-hidden rounded-xl border border-line bg-white">
      <div className="grid grid-cols-7 border-b border-line bg-white text-center text-xs font-medium uppercase tracking-wide text-muted">
        {HEAD.map((d) => (
          <div key={d} className="py-2">
            {d}
          </div>
        ))}
      </div>
      <div className="grid grid-cols-7">
        {days.map((day, i) => {
          const inMonth = day.slice(0, 7) === month;
          const dayEvents = events.filter((e) => e.day === day);
          const shown = dayEvents.slice(0, MAX);
          const blocked = blockedDays.has(day);
          const dayHref = agendaHref({ ...params, view: "dag", day });
          return (
            <div
              key={day}
              className={`relative min-h-20 border-line p-1 sm:min-h-32 sm:p-1.5 ${i % 7 ? "border-l" : ""} ${i >= 7 ? "border-t" : ""} ${inMonth ? "bg-white" : "bg-[#f6f7f8]"}`}
              style={blocked ? { backgroundImage: "repeating-linear-gradient(135deg, rgb(17 19 21 / 0.05) 0 8px, transparent 8px 16px)" } : undefined}
            >
              <div className="flex items-center justify-between gap-1">
                <Link
                  href={dayHref}
                  className={`grid size-7 place-items-center rounded-full text-sm tabular-nums hover:bg-surface ${day === today ? "bg-ink font-semibold text-white hover:bg-ink" : inMonth ? "font-medium" : "text-muted"}`}
                  aria-label={`Bekijk ${day}`}
                >
                  {Number(day.slice(8))}
                </Link>
                {blocked && <span className="hidden truncate text-[11px] text-muted sm:block">{blockedDays.get(day) ?? "Vrij"}</span>}
              </div>

              {/* Telefoon: alleen gekleurde stipjes */}
              {dayEvents.length > 0 && (
                <Link href={dayHref} className="mt-1 flex flex-wrap gap-1 px-1 sm:hidden" aria-label={`${dayEvents.length} afspraken`}>
                  {dayEvents.map((e) => (
                    <span key={e.id} className={`size-2 rounded-full ${e.cancelled ? "opacity-40" : ""}`} style={{ background: typeColor(e.type) }} />
                  ))}
                </Link>
              )}

              <ul className="mt-1 hidden space-y-0.5 sm:block">
                {shown.map((e) => (
                  <li key={e.id}>
                    <Link
                      href={e.href}
                      scroll={false}
                      className={`flex items-center gap-1.5 rounded px-1 py-0.5 text-xs hover:bg-surface ${e.cancelled ? "text-muted line-through" : ""}`}
                      title={`${hhmm(e.start)} ${e.typeLabel} · ${e.client}`}
                    >
                      <span className="size-2 shrink-0 rounded-full" style={{ background: typeColor(e.type) }} aria-hidden="true" />
                      <span className="tabular-nums text-muted">{hhmm(e.start)}</span>
                      <span className="truncate font-medium">{e.client}</span>
                    </Link>
                  </li>
                ))}
                {dayEvents.length > MAX && (
                  <li>
                    <Link href={dayHref} className="block px-1 text-xs font-semibold text-accent hover:underline">
                      +{dayEvents.length - MAX} meer
                    </Link>
                  </li>
                )}
              </ul>
            </div>
          );
        })}
      </div>
    </div>
  );
}
