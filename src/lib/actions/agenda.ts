"use server";

import { and, count, eq, gt, lt, ne } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { z } from "zod";
import { requireAdmin, requireUser } from "../auth";
import { addDays, BOOKING_RULES, getAppointmentType, isValidDay, zonedParts, zonedTimeToUtc } from "../agenda";
import { rotateIcalToken, slotsForDay } from "../agenda-server";
import { appointments, availability, blockedPeriods, db, users } from "../db";
import { fieldErrorsFrom, type FormState, formValues } from "./types";

const bookingSchema = z.object({
  type: z.string(),
  location: z.string(),
  start: z.iso.datetime({ offset: true, error: "Kies een tijd" }),
  note: z.string().trim().max(500).optional(),
});

export async function bookAppointmentAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/agenda");
  const parsed = bookingSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { error: "Kies een tijd om te boeken." };
  const type = getAppointmentType(parsed.data.type);
  if (!type || !(type.locations as readonly string[]).includes(parsed.data.location)) return { error: "Dit afspraaktype of deze locatie bestaat niet." };
  if ("requiresCoaching" in type && type.requiresCoaching && user.coachingStatus !== "actief") {
    return { error: "Deze afspraak is alleen voor klanten met actieve online coaching." };
  }

  const start = new Date(parsed.data.start);
  const now = new Date();
  const result = await db.transaction(async (tx) => {
    const upcoming = await tx
      .select({ type: appointments.type })
      .from(appointments)
      .where(and(eq(appointments.userId, user.id), eq(appointments.status, "gepland"), gt(appointments.startsAt, now)));
    if (upcoming.length >= BOOKING_RULES.maxUpcomingPerClient) return "max" as const;
    if ("maxUpcoming" in type && upcoming.filter((a) => a.type === type.id).length >= type.maxUpcoming) return "type-max" as const;

    // Opnieuw berekenen binnen de transactie: zo kan een tijdslot nooit dubbel geboekt worden.
    const slots = await slotsForDay(type, parsed.data.location, zonedParts(start).day, now, tx);
    if (!slots.some((s) => s.getTime() === start.getTime())) return "taken" as const;

    const [row] = await tx
      .insert(appointments)
      .values({
        userId: user.id,
        type: type.id,
        location: parsed.data.location,
        startsAt: start,
        endsAt: new Date(start.getTime() + type.minutes * 60_000),
        note: parsed.data.note || null,
      })
      .returning({ id: appointments.id });
    return row.id;
  });

  if (result === "max") return { error: `Je hebt al ${BOOKING_RULES.maxUpcomingPerClient} afspraken staan. Neem contact op als je meer wilt plannen.` };
  if (result === "type-max") return { error: `Je hebt al een ${type.label.toLowerCase()} gepland.` };
  if (result === "taken") return { error: "Dit tijdstip is net niet meer beschikbaar. Kies een ander tijdstip." };

  revalidatePath("/account", "layout");
  revalidatePath("/admin", "layout");
  redirect(`/account/agenda?geboekt=${result}`);
}

export async function cancelMyAppointmentAction(formData: FormData) {
  const user = await requireUser("/account/agenda");
  const id = Number(formData.get("id"));
  if (!Number.isInteger(id)) return;
  const [appointment] = await db.select().from(appointments).where(and(eq(appointments.id, id), eq(appointments.userId, user.id)));
  if (!appointment || appointment.status !== "gepland") return;
  if (appointment.startsAt.getTime() - Date.now() < BOOKING_RULES.cancelUntilHours * 3600_000) return;
  await db.update(appointments).set({ status: "geannuleerd", cancelledBy: "klant", cancelledAt: new Date() }).where(eq(appointments.id, id));
  revalidatePath("/account", "layout");
  revalidatePath("/admin", "layout");
}

// ---------------------------------------------------------------------------
// Beheer

export async function cancelAppointmentAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  if (!Number.isInteger(id)) return;
  await db
    .update(appointments)
    .set({ status: "geannuleerd", cancelledBy: "steyn", cancelledAt: new Date() })
    .where(and(eq(appointments.id, id), eq(appointments.status, "gepland")));
  revalidatePath("/admin", "layout");
  revalidatePath("/account", "layout");
}

const time = z.string().regex(/^([01]\d|2[0-3]):[0-5]\d$/, "Vul een tijd in");

const adminBookingSchema = z.object({
  userId: z.string().min(1, "Kies een klant"),
  type: z.string().min(1, "Kies een soort afspraak"),
  location: z.string().min(1, "Kies een locatie"),
  day: z.string().refine(isValidDay, "Kies een datum"),
  time,
  note: z.string().trim().max(500).optional(),
  replaces: z.preprocess((v) => (v === "" || v == null ? undefined : v), z.coerce.number().int().positive().optional()),
});

const hhmm = (d: Date) => zonedParts(d).time;

/**
 * Steyn plant zelf een afspraak in, of verplaatst er een (`replaces`): dan wordt de nieuwe
 * afspraak gemaakt en de oude in dezelfde transactie geannuleerd. Steyn mag buiten de
 * beschikbaarheid plannen; alleen dubbel boeken wordt tegengehouden.
 */
export async function createAppointmentAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const values = formValues(formData);
  const parsed = adminBookingSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };
  const { userId, day, note, replaces } = parsed.data;
  const type = getAppointmentType(parsed.data.type);
  if (!type) return { fieldErrors: { type: "Kies een soort afspraak" }, values };
  if (!(type.locations as readonly string[]).includes(parsed.data.location)) {
    return { fieldErrors: { location: `${type.label} kan niet op deze locatie` }, values };
  }
  const start = zonedTimeToUtc(day, parsed.data.time);
  const end = new Date(start.getTime() + type.minutes * 60_000);
  if (start.getTime() < Date.now()) return { fieldErrors: { time: "Kies een tijdstip in de toekomst" }, values };

  const result = await db.transaction(async (tx) => {
    const [member] = await tx.select({ id: users.id }).from(users).where(eq(users.id, userId));
    if (!member) return { error: "Deze klant bestaat niet meer." };
    const [old] = replaces
      ? await tx.select().from(appointments).where(and(eq(appointments.id, replaces), eq(appointments.status, "gepland")))
      : [undefined];
    if (replaces && !old) return { error: "De afspraak die je wilt verplaatsen is al geannuleerd." };

    const [clash] = await tx
      .select({ startsAt: appointments.startsAt, endsAt: appointments.endsAt, firstName: users.firstName, lastName: users.lastName })
      .from(appointments)
      .innerJoin(users, eq(appointments.userId, users.id))
      .where(
        and(
          eq(appointments.status, "gepland"),
          lt(appointments.startsAt, end),
          gt(appointments.endsAt, start),
          replaces ? ne(appointments.id, replaces) : undefined,
        ),
      )
      .limit(1);
    if (clash) {
      return { error: `Op dit tijdstip staat al een afspraak met ${clash.firstName} ${clash.lastName} (${hhmm(clash.startsAt)}–${hhmm(clash.endsAt)}).` };
    }

    const [row] = await tx
      .insert(appointments)
      .values({ userId, type: type.id, location: parsed.data.location, startsAt: start, endsAt: end, note: note || old?.note || null })
      .returning({ id: appointments.id });
    if (old) {
      await tx.update(appointments).set({ status: "geannuleerd", cancelledBy: "steyn", cancelledAt: new Date() }).where(eq(appointments.id, old.id));
    }
    return { id: row.id };
  });

  if ("error" in result) return { error: result.error, values };
  revalidatePath("/admin", "layout");
  revalidatePath("/account", "layout");
  redirect(`/admin/agenda?weergave=dag&datum=${day}&afspraak=${result.id}&melding=${replaces ? "verplaatst" : "gepland"}`);
}

const availabilitySchema = z
  .object({
    weekday: z.coerce.number().int().min(1).max(7),
    startTime: time,
    endTime: time,
    location: z.string().min(1),
  })
  .refine((a) => a.startTime < a.endTime, { message: "De eindtijd moet na de begintijd liggen", path: ["endTime"] });

export async function addAvailabilityAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const parsed = availabilitySchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { error: parsed.error.issues[0]?.message ?? "Controleer de invoer." };
  await db.insert(availability).values(parsed.data);
  revalidatePath("/admin", "layout");
  return { success: "Beschikbaarheid toegevoegd." };
}

export async function deleteAvailabilityAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  if (Number.isInteger(id)) await db.delete(availability).where(eq(availability.id, id));
  revalidatePath("/admin", "layout");
}

const blockSchema = z
  .object({
    fromDay: z.string().refine(isValidDay, "Kies een begindatum"),
    toDay: z.string().refine(isValidDay, "Kies een einddatum"),
    reason: z.string().trim().max(200).optional(),
  })
  .refine((b) => b.fromDay <= b.toDay, { message: "De einddatum moet na de begindatum liggen", path: ["toDay"] });

export async function addBlockedPeriodAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const parsed = blockSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { error: parsed.error.issues[0]?.message ?? "Controleer de invoer." };
  const { fromDay, toDay, reason } = parsed.data;
  // Hele dagen blokkeren, van 00:00 op de eerste dag tot 00:00 op de dag na de laatste dag.
  const startsAt = zonedTimeToUtc(fromDay, "00:00");
  const endsAt = zonedTimeToUtc(addDays(toDay, 1), "00:00");
  await db.insert(blockedPeriods).values({ startsAt, endsAt, reason: reason || null });

  const [{ n }] = await db
    .select({ n: count() })
    .from(appointments)
    .where(and(eq(appointments.status, "gepland"), lt(appointments.startsAt, endsAt), gt(appointments.endsAt, startsAt)));
  revalidatePath("/admin", "layout");
  return { success: n ? `Periode geblokkeerd. Let op: er staan nog ${n} afspraken in deze periode.` : "Periode geblokkeerd." };
}

export async function deleteBlockedPeriodAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  if (Number.isInteger(id)) await db.delete(blockedPeriods).where(eq(blockedPeriods.id, id));
  revalidatePath("/admin", "layout");
}

export async function rotateIcalTokenAction() {
  await requireAdmin();
  await rotateIcalToken();
  revalidatePath("/admin", "layout");
}
