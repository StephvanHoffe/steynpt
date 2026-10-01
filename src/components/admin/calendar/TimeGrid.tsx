import Link from "next/link";
import { getAgendaLocation, weekdayOf, zonedTimeToUtc } from "@/lib/agenda";
import { layoutLanes, timeToMinutes } from "@/lib/agenda-calendar";
import { agendaHref, type AgendaParams, type CalendarEvent, typeColor } from "./shared";

const HOUR_PX = 56;
const PX = HOUR_PX / 60;

const weekdayFmt = new Intl.DateTimeFormat("nl-NL", { weekday: "short", timeZone: "UTC" });
const dayNum = (day: string) => Number(day.slice(8));
const label = (day: string) => weekdayFmt.format(new Date(`${day}T12:00:00Z`)).replace(".", "");
const hhmm = (min: number) => `${String(Math.floor(min / 60)).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;

type Window = { weekday: number; startTime: string; endTime: string; location: string };
type Block = { day: string; start: number; end: number; reason: string | null };

/** Tijdrooster voor de dag- en weekweergave. */
export function TimeGrid({
  params,
  days,
  events,
  windows,
  blocks,
  hours,
  today,
  nowMinutes,
  now,
}: {
  params: AgendaParams;
  days: string[];
  events: CalendarEvent[];
  windows: Window[];
  blocks: Block[];
  hours: { start: number; end: number };
  today: string;
  nowMinutes: number;
  now: Date;
}) {
  const gridStart = hours.start * 60;
  const height = (hours.end - hours.start) * HOUR_PX;
  const single = days.length === 1;
  const columns = `3.5rem repeat(${days.length}, minmax(${single ? "0" : "7rem"}, 1fr))`;

  return (
    <div className="overflow-auto rounded-xl border border-line bg-white" style={{ maxHeight: "calc(100dvh - 15rem)", minHeight: "26rem" }}>
      <div className="grid" style={{ gridTemplateColumns: columns, minWidth: single ? undefined : "52rem" }}>
        {/* Kop met dagen */}
        <div className="sticky left-0 top-0 z-40 border-b border-line bg-white" />
        {days.map((day) => {
          const isToday = day === today;
          const content = (
            <>
              <span className="text-xs font-medium uppercase tracking-wide text-muted">{label(day)}</span>
              <span className={`grid size-8 place-items-center rounded-full text-lg font-semibold tabular-nums ${isToday ? "bg-ink text-white" : ""}`}>{dayNum(day)}</span>
            </>
          );
          return (
            <div key={day} className="sticky top-0 z-30 border-b border-l border-line bg-white px-2 py-2">
              {single ? (
                <div className="flex items-center gap-2">{content}</div>
              ) : (
                <Link href={agendaHref({ ...params, view: "dag", day })} className="flex items-center gap-2 rounded-md hover:bg-surface" title="Bekijk deze dag">
                  {content}
                </Link>
              )}
            </div>
          );
        })}

        {/* Tijdkolom */}
        <div className="sticky left-0 z-20 bg-white" style={{ height }}>
          {Array.from({ length: hours.end - hours.start }, (_, i) => (
            <span key={i} className={`absolute right-2 text-[11px] tabular-nums text-muted ${i === 0 ? "top-1" : "-translate-y-1/2"}`} style={i === 0 ? undefined : { top: i * HOUR_PX }}>
              {hhmm((hours.start + i) * 60)}
            </span>
          ))}
        </div>

        {/* Dagkolommen */}
        {days.map((day) => {
          const dayWindows = windows.filter((w) => w.weekday === weekdayOf(day));
          const dayBlocks = blocks.filter((b) => b.day === day);
          const dayEvents = events.filter((e) => e.day === day);
          const lanes = layoutLanes(dayEvents);
          const isToday = day === today;
          return (
            <div key={day} className="relative border-l border-line bg-[#f6f7f8]" style={{ height }}>
              {/* Beschikbare tijd (wit) */}
              {dayWindows.map((w, i) => {
                const s = Math.max(timeToMinutes(w.startTime), gridStart);
                const e = Math.min(timeToMinutes(w.endTime), hours.end * 60);
                if (e <= s) return null;
                return (
                  <div key={i} className="absolute inset-x-0 bg-white" style={{ top: (s - gridStart) * PX, height: (e - s) * PX }}>
                    {single && (
                      <span className="absolute right-2 top-1 text-[11px] text-muted">
                        Beschikbaar · {getAgendaLocation(w.location)?.label ?? w.location}
                      </span>
                    )}
                  </div>
                );
              })}

              {/* Geblokkeerd */}
              {dayBlocks.map((b, i) => {
                const s = Math.max(b.start, gridStart);
                const e = Math.min(b.end, hours.end * 60);
                if (e <= s) return null;
                return (
                  <div
                    key={i}
                    className="absolute inset-x-0 z-[1] border-y border-line"
                    style={{
                      top: (s - gridStart) * PX,
                      height: (e - s) * PX,
                      backgroundImage: "repeating-linear-gradient(135deg, rgb(17 19 21 / 0.06) 0 8px, transparent 8px 16px)",
                    }}
                  >
                    <span className="m-1.5 inline-block rounded bg-white/90 px-1.5 py-0.5 text-[11px] font-medium text-muted">
                      Vrij{b.reason ? ` · ${b.reason}` : ""}
                    </span>
                  </div>
                );
              })}

              {/* Rasterlijnen */}
              <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 z-[2]"
                style={{
                  backgroundImage: `linear-gradient(to bottom, var(--color-line) 1px, transparent 1px), linear-gradient(to bottom, rgb(226 229 232 / 0.55) 1px, transparent 1px)`,
                  backgroundSize: `100% ${HOUR_PX}px, 100% ${HOUR_PX / 2}px`,
                }}
              />

              {/* Klik op een leeg moment om een afspraak in te plannen (met toetsenbord: knop Nieuwe afspraak) */}
              {Array.from({ length: (hours.end - hours.start) * 2 }, (_, i) => {
                const min = gridStart + i * 30;
                if (zonedTimeToUtc(day, hhmm(min)).getTime() < now.getTime()) return null;
                return (
                  <Link
                    key={i}
                    href={`/admin/agenda/nieuw?datum=${day}&tijd=${hhmm(min)}`}
                    tabIndex={-1}
                    aria-hidden="true"
                    className="group absolute inset-x-0 z-[3] flex items-start px-1.5 pt-0.5 text-[11px] font-medium text-accent opacity-0 hover:bg-accent-tint/70 hover:opacity-100"
                    style={{ top: i * 30 * PX, height: 30 * PX }}
                  >
                    + {hhmm(min)}
                  </Link>
                );
              })}

              {/* Afspraken */}
              {dayEvents.map((e) => {
                const lane = lanes.get(e.id) ?? { lane: 0, lanes: 1 };
                const top = (Math.max(e.start, gridStart) - gridStart) * PX;
                const h = Math.max((Math.min(e.end, hours.end * 60) - Math.max(e.start, gridStart)) * PX - 2, 20);
                const color = typeColor(e.type);
                const compact = h < 40;
                return (
                  <Link
                    key={e.id}
                    href={e.href}
                    scroll={false}
                    className={`absolute z-10 overflow-hidden rounded-md border-l-[3px] px-1.5 py-1 text-xs leading-tight shadow-sm ring-1 ring-black/5 transition hover:z-20 hover:shadow-md focus-visible:z-20 ${
                      e.cancelled ? "border-dashed opacity-60" : ""
                    }`}
                    style={{
                      top: top + 1,
                      height: h,
                      left: `calc(${(lane.lane / lane.lanes) * 100}% + 2px)`,
                      width: `calc(${100 / lane.lanes}% - 4px)`,
                      borderLeftColor: color,
                      background: e.cancelled ? "#fff" : `color-mix(in srgb, ${color} 9%, white)`,
                    }}
                  >
                    <span className={`block truncate font-semibold ${e.cancelled ? "line-through" : ""}`}>
                      {compact ? `${hhmm(e.start)} ${e.client}` : e.client}
                    </span>
                    {!compact && (
                      <span className="block truncate text-muted">
                        {hhmm(e.start)}–{hhmm(e.end)} · {e.typeLabel}
                      </span>
                    )}
                    {!compact && h > 70 && <span className="block truncate text-muted">{getAgendaLocation(e.location)?.label ?? e.location}</span>}
                    <span className="sr-only">
                      {e.typeLabel}, {e.cancelled ? "geannuleerd" : ""}
                    </span>
                  </Link>
                );
              })}

              {/* Nu */}
              {isToday && nowMinutes >= gridStart && nowMinutes <= hours.end * 60 && (
                <div aria-hidden="true" className="pointer-events-none absolute inset-x-0 z-[25] border-t-2 border-[#d92d20]" style={{ top: (nowMinutes - gridStart) * PX }}>
                  <span className="absolute -left-1 -top-[5px] size-2 rounded-full bg-[#d92d20]" />
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
