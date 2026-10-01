// Kalenderweergaven voor het beheer: welke dagen een weergave toont, bladeren en de
// indeling van afspraken in het tijdrooster. Puur (geen database), zodat het los te testen is.

import { addDays, weekdayOf, zonedParts } from "./agenda";

export const CALENDAR_VIEWS = [
  { id: "dag", label: "Dag" },
  { id: "week", label: "Week" },
  { id: "maand", label: "Maand" },
  { id: "lijst", label: "Lijst" },
] as const;
export type CalendarView = (typeof CALENDAR_VIEWS)[number]["id"];

export const parseView = (v: unknown): CalendarView => (CALENDAR_VIEWS.some((x) => x.id === v) ? (v as CalendarView) : "week");

/** Aantal dagen dat de lijstweergave toont. */
export const LIST_DAYS = 28;

export const startOfWeek = (day: string) => addDays(day, 1 - weekdayOf(day));
export const startOfMonth = (day: string) => `${day.slice(0, 7)}-01`;

export function addMonths(day: string, n: number) {
  const [y, m] = day.split("-").map(Number);
  const d = new Date(Date.UTC(y, m - 1 + n, 1));
  return d.toISOString().slice(0, 10);
}

/** Dagen die een weergave toont (bij de maand: hele weken, maandag t/m zondag). */
export function viewDays(view: CalendarView, day: string): string[] {
  const range = (from: string, n: number) => Array.from({ length: n }, (_, i) => addDays(from, i));
  if (view === "dag") return [day];
  if (view === "week") return range(startOfWeek(day), 7);
  if (view === "lijst") return range(day, LIST_DAYS);
  const first = startOfMonth(day);
  const gridStart = startOfWeek(first);
  const last = addDays(addMonths(first, 1), -1);
  const gridEnd = addDays(startOfWeek(last), 6);
  const n = Math.round((Date.parse(gridEnd) - Date.parse(gridStart)) / 864e5) + 1;
  return range(gridStart, n);
}

/** Vorige of volgende periode. */
export function shiftDay(view: CalendarView, day: string, direction: -1 | 1) {
  if (view === "dag") return addDays(day, direction);
  if (view === "week") return addDays(day, 7 * direction);
  if (view === "lijst") return addDays(day, LIST_DAYS * direction);
  return addMonths(startOfMonth(day), direction);
}

/** ISO-weeknummer van een dag ("2026-10-05" -> 41). */
export function isoWeek(day: string) {
  const [y, m, d] = day.split("-").map(Number);
  const date = new Date(Date.UTC(y, m - 1, d));
  date.setUTCDate(date.getUTCDate() + 4 - (date.getUTCDay() || 7));
  const yearStart = Date.UTC(date.getUTCFullYear(), 0, 1);
  return Math.ceil(((date.getTime() - yearStart) / 864e5 + 1) / 7);
}

// ---------------------------------------------------------------------------
// Tijdrooster

/** Minuten sinds middernacht (Nederlandse tijd). */
export function minutesOfDay(date: Date) {
  const [h, m] = zonedParts(date).time.split(":").map(Number);
  return h * 60 + m;
}

export const timeToMinutes = (time: string) => {
  const [h, m] = time.split(":").map(Number);
  return h * 60 + m;
};

/** Uren die het rooster toont: standaard 07:00–21:00, ruimer als er eerder of later iets staat. */
export function gridHours(items: { start: number; end: number }[], fallback = { start: 7, end: 21 }) {
  let start = fallback.start;
  let end = fallback.end;
  for (const item of items) {
    start = Math.min(start, Math.floor(item.start / 60));
    end = Math.max(end, Math.ceil(item.end / 60));
  }
  return { start: Math.max(0, start), end: Math.min(24, end) };
}

/**
 * Zet afspraken die elkaar overlappen naast elkaar: elke afspraak krijgt een kolom (lane)
 * en het aantal kolommen van de groep waarin ze valt.
 */
export function layoutLanes<T extends { id: number; start: number; end: number }>(events: T[]) {
  const sorted = [...events].sort((a, b) => a.start - b.start || b.end - a.end);
  const result = new Map<number, { lane: number; lanes: number }>();
  let group: { id: number; lane: number }[] = [];
  let laneEnds: number[] = [];
  let groupEnd = -Infinity;

  const closeGroup = () => {
    for (const g of group) result.set(g.id, { lane: g.lane, lanes: laneEnds.length });
    group = [];
    laneEnds = [];
  };

  for (const e of sorted) {
    if (e.start >= groupEnd) {
      closeGroup();
      groupEnd = -Infinity;
    }
    let lane = laneEnds.findIndex((end) => end <= e.start);
    if (lane === -1) {
      lane = laneEnds.length;
      laneEnds.push(e.end);
    } else {
      laneEnds[lane] = e.end;
    }
    group.push({ id: e.id, lane });
    groupEnd = Math.max(groupEnd, e.end);
  }
  closeGroup();
  return result;
}
