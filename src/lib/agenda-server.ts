import "server-only";
import { randomBytes, timingSafeEqual } from "node:crypto";
import { and, eq, gt, lt } from "drizzle-orm";
import { addDays, BOOKING_RULES, computeSlots, getAgendaLocation, getAppointmentType, zonedParts, zonedTimeToUtc, type AppointmentType, type CalendarEvent } from "./agenda";
import { appointments, availability, blockedPeriods, db, settings, type Appointment, type User } from "./db";

type Executor = typeof db | Parameters<Parameters<typeof db.transaction>[0]>[0];

/** Alles wat een tijdslot bezet maakt: geplande afspraken en geblokkeerde periodes. */
export async function getBusy(from: Date, to: Date, executor: Executor = db) {
  const [booked, blocked] = await Promise.all([
    executor
      .select({ startsAt: appointments.startsAt, endsAt: appointments.endsAt })
      .from(appointments)
      .where(and(eq(appointments.status, "gepland"), lt(appointments.startsAt, to), gt(appointments.endsAt, from))),
    executor
      .select({ startsAt: blockedPeriods.startsAt, endsAt: blockedPeriods.endsAt })
      .from(blockedPeriods)
      .where(and(lt(blockedPeriods.startsAt, to), gt(blockedPeriods.endsAt, from))),
  ]);
  return [...booked, ...blocked];
}

export async function slotsForDay(type: AppointmentType, location: string, day: string, now = new Date(), executor: Executor = db) {
  const windows = await executor.select().from(availability);
  const busy = await getBusy(zonedTimeToUtc(day, "00:00"), zonedTimeToUtc(addDays(day, 1), "00:00"), executor);
  return computeSlots({ type, location, day, windows, busy, now });
}

/** Dagen binnen de boekingshorizon met minstens één vrij tijdslot. */
export async function availableDays(type: AppointmentType, location: string, now = new Date()) {
  const windows = await db.select().from(availability);
  const today = zonedParts(now).day;
  const end = addDays(today, BOOKING_RULES.horizonDays + 1);
  const busy = await getBusy(now, zonedTimeToUtc(end, "00:00"));
  const days: { day: string; slots: number }[] = [];
  for (let i = 0; i <= BOOKING_RULES.horizonDays; i++) {
    const day = addDays(today, i);
    const slots = computeSlots({ type, location, day, windows, busy, now }).length;
    if (slots) days.push({ day, slots });
  }
  return days;
}

/** Locaties waar dit type de komende weken überhaupt boekbaar is. */
export async function locationsWithAvailability(type: AppointmentType) {
  const windows = await db.select({ location: availability.location }).from(availability);
  return type.locations.filter((l) => windows.some((w) => w.location === l));
}

// ---------------------------------------------------------------------------
// iCal-feed voor Steyn

const ICAL_KEY = "ical_token";

export async function getIcalToken() {
  const [row] = await db.select().from(settings).where(eq(settings.key, ICAL_KEY));
  if (row) return row.value;
  const token = randomBytes(24).toString("base64url");
  await db.insert(settings).values({ key: ICAL_KEY, value: token }).onConflictDoNothing();
  const [saved] = await db.select().from(settings).where(eq(settings.key, ICAL_KEY));
  return saved.value;
}

export async function rotateIcalToken() {
  const token = randomBytes(24).toString("base64url");
  await db.insert(settings).values({ key: ICAL_KEY, value: token }).onConflictDoUpdate({ target: settings.key, set: { value: token } });
  return token;
}

export async function isValidIcalToken(candidate: string) {
  const [row] = await db.select().from(settings).where(eq(settings.key, ICAL_KEY));
  if (!row) return false;
  const a = Buffer.from(candidate);
  const b = Buffer.from(row.value);
  return a.length === b.length && timingSafeEqual(a, b);
}

/** Afspraak als agenda-item; voor Steyn met klantgegevens, voor de klant zonder. */
export function toCalendarEvent(appointment: Appointment, client: Pick<User, "firstName" | "lastName" | "email" | "phone">, view: "steyn" | "klant"): CalendarEvent {
  const type = getAppointmentType(appointment.type);
  const location = getAgendaLocation(appointment.location);
  const typeLabel = type?.label ?? appointment.type;
  return {
    uid: `appointment-${appointment.id}@steynpt.nl`,
    start: appointment.startsAt,
    end: appointment.endsAt,
    summary: view === "steyn" ? `${typeLabel} – ${client.firstName} ${client.lastName}` : `${typeLabel} met Steyn (SteynPT)`,
    location: !location ? appointment.location : location.id === "online" || location.id === "op-locatie" ? location.label : `${location.label}, ${location.address}`,
    description:
      view === "steyn"
        ? [`${client.firstName} ${client.lastName}`, client.phone && `Tel: ${client.phone}`, `E-mail: ${client.email}`, appointment.note && `Notitie: ${appointment.note}`]
            .filter(Boolean)
            .join("\n")
        : "Afzeggen kan tot 24 uur van tevoren via Mijn omgeving op steynpt.nl.",
    cancelled: appointment.status === "geannuleerd",
    updatedAt: appointment.cancelledAt ?? appointment.createdAt,
  };
}
