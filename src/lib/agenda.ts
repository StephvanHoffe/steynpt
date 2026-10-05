// Agenda: afspraaktypes, tijdzone-hulpjes, berekening van vrije tijdsloten en iCal-export.
// Alles hier is puur (geen database), zodat het los te testen is.

export const TIME_ZONE = "Europe/Amsterdam";

export const AGENDA_LOCATIONS = [
  { id: "gymbase", label: "Gymbase", address: "Overtoom 371-w, 1054 JN Amsterdam" },
  { id: "op-locatie", label: "Op locatie", address: "Locatie in overleg" },
  { id: "online", label: "Online (videocall)", address: "Online, Steyn stuurt een link" },
] as const;
export type AgendaLocationId = (typeof AGENDA_LOCATIONS)[number]["id"];

export const APPOINTMENT_TYPES = [
  {
    id: "personal-training",
    label: "Personal training",
    minutes: 60,
    locations: ["gymbase", "op-locatie"],
    description: "1-op-1-training van 60 minuten.",
  },
  {
    id: "kennismaking",
    label: "Gratis kennismaking",
    minutes: 30,
    locations: ["gymbase", "online"],
    description: "Kennismaken en je doelen bespreken (bij Gymbase met rondleiding).",
    maxUpcoming: 1,
  },
  {
    id: "meting",
    label: "Meting",
    minutes: 30,
    locations: ["gymbase"],
    description: "Wegen, meten en vetpercentage bepalen.",
  },
  {
    id: "online-call",
    label: "Online coaching videocall",
    minutes: 30,
    locations: ["online"],
    description: "Evaluatie en bijsturen van je online coaching.",
    requiresCoaching: true,
  },
] as const satisfies readonly {
  id: string;
  label: string;
  minutes: number;
  locations: readonly AgendaLocationId[];
  description: string;
  maxUpcoming?: number;
  requiresCoaching?: boolean;
}[];
export type AppointmentType = (typeof APPOINTMENT_TYPES)[number];

export const BOOKING_RULES = {
  horizonDays: 42,
  minNoticeHours: 12,
  cancelUntilHours: 24,
  slotStepMinutes: 30,
  maxUpcomingPerClient: 8,
};

export const WEEKDAYS = ["maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag", "zondag"];

export const getAppointmentType = (id: string | null | undefined) => APPOINTMENT_TYPES.find((t) => t.id === id);
export const getAgendaLocation = (id: string | null | undefined) => AGENDA_LOCATIONS.find((l) => l.id === id);

// ---------------------------------------------------------------------------
// Tijdzones (zonder externe library, via Intl)

const partsFormatter = new Intl.DateTimeFormat("en-US", {
  timeZone: TIME_ZONE,
  hourCycle: "h23",
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  hour: "2-digit",
  minute: "2-digit",
  second: "2-digit",
});

function zonedFields(date: Date) {
  const get = (type: string) => Number(partsFormatter.formatToParts(date).find((p) => p.type === type)!.value);
  return { year: get("year"), month: get("month"), day: get("day"), hour: get("hour"), minute: get("minute"), second: get("second") };
}

function offsetMinutes(date: Date) {
  const f = zonedFields(date);
  return (Date.UTC(f.year, f.month - 1, f.day, f.hour, f.minute, f.second) - Math.floor(date.getTime() / 1000) * 1000) / 60000;
}

/** "2026-10-05" + "07:30" in Nederlandse tijd -> Date (UTC). */
export function zonedTimeToUtc(day: string, time: string): Date {
  const [y, m, d] = day.split("-").map(Number);
  const [hh, mm] = time.split(":").map(Number);
  const guess = Date.UTC(y, m - 1, d, hh, mm);
  const first = guess - offsetMinutes(new Date(guess)) * 60000;
  const second = guess - offsetMinutes(new Date(first)) * 60000;
  return new Date(second);
}

/** Datum, tijd en weekdag (1 = maandag) van een moment in Nederlandse tijd. */
export function zonedParts(date: Date) {
  const f = zonedFields(date);
  const day = `${f.year}-${String(f.month).padStart(2, "0")}-${String(f.day).padStart(2, "0")}`;
  return { day, time: `${String(f.hour).padStart(2, "0")}:${String(f.minute).padStart(2, "0")}`, weekday: weekdayOf(day) };
}

export function weekdayOf(day: string) {
  const [y, m, d] = day.split("-").map(Number);
  return new Date(Date.UTC(y, m - 1, d)).getUTCDay() || 7;
}

export function addDays(day: string, n: number) {
  const [y, m, d] = day.split("-").map(Number);
  return new Date(Date.UTC(y, m - 1, d + n)).toISOString().slice(0, 10);
}

export const isValidDay = (day: unknown): day is string => typeof day === "string" && /^\d{4}-\d{2}-\d{2}$/.test(day) && !Number.isNaN(Date.parse(day));

// ---------------------------------------------------------------------------
// Vrije tijdsloten

type Window = { weekday: number; startTime: string; endTime: string; location: string };
type Busy = { startsAt: Date; endsAt: Date };

const overlaps = (aStart: number, aEnd: number, bStart: number, bEnd: number) => aStart < bEnd && bStart < aEnd;

/** Starttijden die voor dit afspraaktype op deze dag en locatie vrij zijn. */
export function computeSlots({
  type,
  location,
  day,
  windows,
  busy,
  now = new Date(),
}: {
  type: AppointmentType;
  location: string;
  day: string;
  windows: Window[];
  busy: Busy[];
  now?: Date;
}): Date[] {
  if (!(type.locations as readonly string[]).includes(location)) return [];
  const earliest = now.getTime() + BOOKING_RULES.minNoticeHours * 3600_000;
  const latest = zonedTimeToUtc(addDays(zonedParts(now).day, BOOKING_RULES.horizonDays), "23:59").getTime();
  const duration = type.minutes * 60_000;
  const step = BOOKING_RULES.slotStepMinutes * 60_000;
  const weekday = weekdayOf(day);
  const slots = new Map<number, Date>();

  for (const w of windows) {
    if (w.weekday !== weekday || w.location !== location) continue;
    const windowEnd = zonedTimeToUtc(day, w.endTime).getTime();
    for (let t = zonedTimeToUtc(day, w.startTime).getTime(); t + duration <= windowEnd; t += step) {
      if (t < earliest || t > latest) continue;
      if (busy.some((b) => overlaps(t, t + duration, b.startsAt.getTime(), b.endsAt.getTime()))) continue;
      slots.set(t, new Date(t));
    }
  }
  return [...slots.values()].sort((a, b) => a.getTime() - b.getTime());
}

// ---------------------------------------------------------------------------
// iCal (RFC 5545)

export type CalendarEvent = {
  uid: string;
  start: Date;
  end: Date;
  summary: string;
  location?: string;
  description?: string;
  cancelled?: boolean;
  updatedAt?: Date;
};

const icsDate = (d: Date) => d.toISOString().replace(/[-:]/g, "").replace(/\.\d{3}/, "");

export function escapeIcsText(text: string) {
  return text.replace(/\\/g, "\\\\").replace(/;/g, "\\;").replace(/,/g, "\\,").replace(/\r?\n/g, "\\n");
}

/** Vouwt regels langer dan 75 octets (UTF-8) zoals de standaard voorschrijft. */
export function foldIcsLine(line: string) {
  const encoder = new TextEncoder();
  if (encoder.encode(line).length <= 75) return line;
  const parts: string[] = [];
  let current = "";
  for (const char of line) {
    const limit = parts.length === 0 ? 75 : 74; // vervolgregels beginnen met een spatie
    if (encoder.encode(current + char).length > limit) {
      parts.push(current);
      current = char;
    } else {
      current += char;
    }
  }
  parts.push(current);
  return parts.join("\r\n ");
}

export function buildIcs(events: CalendarEvent[], { name, now = new Date() }: { name: string; now?: Date }) {
  const lines = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//SteynPT//Agenda//NL",
    "CALSCALE:GREGORIAN",
    "METHOD:PUBLISH",
    `X-WR-CALNAME:${escapeIcsText(name)}`,
    `X-WR-TIMEZONE:${TIME_ZONE}`,
    "REFRESH-INTERVAL;VALUE=DURATION:PT1H",
    "X-PUBLISHED-TTL:PT1H",
  ];
  for (const e of events) {
    lines.push(
      "BEGIN:VEVENT",
      `UID:${e.uid}`,
      `DTSTAMP:${icsDate(now)}`,
      `DTSTART:${icsDate(e.start)}`,
      `DTEND:${icsDate(e.end)}`,
      `SUMMARY:${escapeIcsText(e.summary)}`,
      ...(e.location ? [`LOCATION:${escapeIcsText(e.location)}`] : []),
      ...(e.description ? [`DESCRIPTION:${escapeIcsText(e.description)}`] : []),
      `STATUS:${e.cancelled ? "CANCELLED" : "CONFIRMED"}`,
      `SEQUENCE:${e.cancelled ? 1 : 0}`,
      ...(e.updatedAt ? [`LAST-MODIFIED:${icsDate(e.updatedAt)}`] : []),
      "END:VEVENT",
    );
  }
  lines.push("END:VCALENDAR");
  return lines.map(foldIcsLine).join("\r\n") + "\r\n";
}

// ---------------------------------------------------------------------------
// Weergave: altijd in Nederlandse tijd, ongeacht de tijdzone van de server.

const fmt = (options: Intl.DateTimeFormatOptions) => new Intl.DateTimeFormat("nl-NL", { timeZone: TIME_ZONE, ...options });
const dayLongFmt = fmt({ weekday: "long", day: "numeric", month: "long" });
const dayShortFmt = fmt({ weekday: "short", day: "numeric", month: "short" });
const timeFmt = fmt({ hour: "2-digit", minute: "2-digit" });

export const formatDayLong = (d: Date) => dayLongFmt.format(d);
export const formatDayShort = (d: Date) => dayShortFmt.format(d);
export const formatTime = (d: Date) => timeFmt.format(d);
/** Een dag ("2026-10-05") als datum om 12:00, zodat formatteren altijd de juiste dag geeft. */
export const dayToDate = (day: string) => zonedTimeToUtc(day, "12:00");
