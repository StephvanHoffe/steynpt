import { Clock, MapPin } from "lucide-react";
import Link from "next/link";
import { dayToDate, formatDayLong, getAgendaLocation } from "@/lib/agenda";
import { EmptyState } from "../ui";
import { type CalendarEvent, typeColor } from "./shared";

const afspraken = (n: number) => `${n} ${n === 1 ? "afspraak" : "afspraken"}`;
const hhmm = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;

/** Lijstweergave: afspraken per dag onder elkaar. */
export function AgendaList({ days, events, today, blockedDays }: { days: string[]; events: CalendarEvent[]; today: string; blockedDays: Map<string, string | null> }) {
  const withContent = days.filter((d) => events.some((e) => e.day === d) || blockedDays.has(d));
  if (withContent.length === 0) return <EmptyState>Geen afspraken in deze periode.</EmptyState>;
  return (
    <div className="grid gap-4">
      {withContent.map((day) => {
        const dayEvents = events.filter((e) => e.day === day);
        return (
          <section key={day} className="overflow-hidden rounded-xl border border-line bg-white">
            <h2 className="flex items-center justify-between gap-3 border-b border-line bg-[#f6f7f8] px-4 py-2.5 text-sm font-semibold">
              <span className="first-letter:uppercase">{formatDayLong(dayToDate(day))}</span>
              <span className="text-xs font-medium text-muted">
                {day === today ? "Vandaag · " : ""}
                {blockedDays.has(day) ? `Vrij${blockedDays.get(day) ? ` (${blockedDays.get(day)})` : ""}` : afspraken(dayEvents.filter((e) => !e.cancelled).length)}
              </span>
            </h2>
            {dayEvents.length > 0 && (
              <ul className="divide-y divide-line">
                {dayEvents.map((e) => (
                  <li key={e.id}>
                    <Link href={e.href} scroll={false} className={`flex items-center gap-4 px-4 py-3 hover:bg-surface ${e.cancelled ? "opacity-60" : ""}`}>
                      <span className="w-24 shrink-0 text-sm font-semibold tabular-nums">
                        {hhmm(e.start)}–{hhmm(e.end)}
                      </span>
                      <span className="h-9 w-1 shrink-0 rounded-full" style={{ background: typeColor(e.type) }} aria-hidden="true" />
                      <span className="min-w-0 flex-1">
                        <span className={`block truncate font-semibold ${e.cancelled ? "line-through" : ""}`}>{e.client}</span>
                        <span className="flex flex-wrap gap-x-3 text-sm text-muted">
                          <span className="inline-flex items-center gap-1">
                            <Clock className="size-3.5" aria-hidden="true" /> {e.typeLabel}
                          </span>
                          <span className="inline-flex items-center gap-1">
                            <MapPin className="size-3.5" aria-hidden="true" /> {getAgendaLocation(e.location)?.label ?? e.location}
                          </span>
                          {e.cancelled && <span className="font-medium text-danger">Geannuleerd</span>}
                        </span>
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </section>
        );
      })}
    </div>
  );
}
