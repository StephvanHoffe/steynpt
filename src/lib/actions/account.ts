"use server";

import { eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { z } from "zod";
import { endOtherSessions, getCurrentUser, hashPassword, requireUser, safeNextPath, SESSION_COOKIE_NAME, verifyPassword } from "../auth";
import { appointments, checkIns, db, intakes, loginChallenges, measurements, plans, recoveryCodes, sessions, users } from "../db";
import { isDemoAccount } from "../demo";
import { checkInStreak, isoWeekKey } from "../weeks";
import { getOnlinePlan, GOALS } from "../site";
import { fieldErrorsFrom, formValues, type FormState } from "./types";

const score = (label: string) =>
  z.coerce
    .number({ error: `Geef een score voor ${label}` })
    .int()
    .min(1, `Geef een score voor ${label}`)
    .max(5);

const checkInSchema = z.object({
  weight: z
    .string()
    .trim()
    .transform((v) => (v ? Number(v.replace(",", ".")) : null))
    .refine((v) => v === null || (Number.isFinite(v) && v > 25 && v < 350), "Vul een geldig gewicht in (kg)"),
  energy: score("energie"),
  sleep: score("slaap"),
  nutrition: score("voeding"),
  workouts: z.coerce.number({ error: "Vul een aantal trainingen in tussen 0 en 21" }).int("Vul een aantal trainingen in tussen 0 en 21").min(0, "Vul een aantal trainingen in tussen 0 en 21").max(21, "Vul een aantal trainingen in tussen 0 en 21"),
  note: z.string().trim().max(1000).optional(),
});

export async function checkInAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser();
  const values = formValues(formData);
  const parsed = checkInSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };

  const week = isoWeekKey(new Date());
  const result = await db
    .insert(checkIns)
    .values({ userId: user.id, week, ...parsed.data, note: parsed.data.note || null })
    .onConflictDoNothing();
  if (result.rowsAffected === 0) return { error: "Je hebt deze week al ingecheckt. Tot volgende week!" };

  const weeks = await db.select({ week: checkIns.week }).from(checkIns).where(eq(checkIns.userId, user.id));
  const streak = checkInStreak(weeks.map((w) => w.week));

  revalidatePath("/account");
  return { success: streak > 1 ? `Check-in opgeslagen. ${streak} weken op rij, goed bezig!` : "Check-in opgeslagen. Steyn kijkt ernaar." };
}

const profileSchema = z.object({
  firstName: z.string().trim().min(1, "Vul je voornaam in").max(60),
  lastName: z.string().trim().min(1, "Vul je achternaam in").max(80),
  phone: z.string().trim().max(30).optional(),
  goal: z.enum(GOALS.map((g) => g.id) as [string, ...string[]], "Kies je belangrijkste doel"),
  marketing: z.literal("on").optional(),
});

export async function updateProfileAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  const values = formValues(formData);
  const parsed = profileSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };
  const data = parsed.data;

  await db
    .update(users)
    .set({
      firstName: data.firstName,
      lastName: data.lastName,
      phone: data.phone || null,
      goal: data.goal,
      marketingOptIn: data.marketing === "on",
    })
    .where(eq(users.id, user.id));

  revalidatePath("/account", "layout");
  return { success: "Je profiel is bijgewerkt." };
}

const passwordSchema = z
  .object({
    current: z.string().min(1, "Vul je huidige wachtwoord in"),
    password: z.string().min(8, "Kies een wachtwoord van minimaal 8 tekens").max(200),
    confirm: z.string(),
  })
  .refine((d) => d.password === d.confirm, { message: "De wachtwoorden komen niet overeen", path: ["confirm"] })
  .refine((d) => d.password !== d.current, { message: "Kies een ander wachtwoord dan je huidige", path: ["password"] });

/** Nieuw wachtwoord opslaan: de termijn van 8 weken begint opnieuw en andere apparaten worden uitgelogd. */
async function setNewPassword(user: { id: string; email: string; passwordHash: string }, formData: FormData): Promise<FormState | null> {
  if (isDemoAccount(user.email)) return { error: "In de demo kun je het wachtwoord van dit voorbeeldaccount niet wijzigen." };
  const parsed = passwordSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues) };
  if (!(await verifyPassword(parsed.data.current, user.passwordHash))) {
    return { fieldErrors: { current: "Je huidige wachtwoord klopt niet" } };
  }
  await db
    .update(users)
    .set({ passwordHash: await hashPassword(parsed.data.password), passwordChangedAt: new Date() })
    .where(eq(users.id, user.id));
  await endOtherSessions(user.id);
  return null;
}

export async function changePasswordAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  const failed = await setNewPassword(user, formData);
  if (failed) return failed;
  revalidatePath("/", "layout");
  return { success: "Je wachtwoord is gewijzigd. Op andere apparaten moet je opnieuw inloggen." };
}

/** Verplicht nieuw wachtwoord na 8 weken (of eerder, vanuit de herinnering). */
export async function renewPasswordAction(_prev: FormState, formData: FormData): Promise<FormState> {
  // Hier geen requireUser: die stuurt bij een verlopen wachtwoord juist naar deze pagina.
  const user = await getCurrentUser();
  if (!user) redirect("/inloggen");
  const failed = await setNewPassword(user, formData);
  if (failed) return failed;
  revalidatePath("/", "layout");
  redirect(safeNextPath(formData.get("next"), user.role === "admin" ? "/admin" : "/account"));
}

export async function requestCoachingAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser();
  const plan = getOnlinePlan(String(formData.get("plan") ?? ""));
  if (!plan) return { error: "Kies een pakket om te starten." };
  if (user.coachingStatus === "actief") return { error: "Je online coaching is al actief." };

  await db
    .update(users)
    .set({ plan: plan.id, coachingStatus: "aangevraagd" })
    .where(eq(users.id, user.id));
  revalidatePath("/account");
  return { success: `Je aanvraag voor online coaching ${plan.name} is ontvangen. Steyn neemt binnen 24 uur contact met je op.` };
}

export async function deleteAccountAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  if (isDemoAccount(user.email)) {
    return { error: "In de demo kun je dit voorbeeldaccount niet verwijderen. Maak een eigen account aan om het te proberen." };
  }
  const password = String(formData.get("password") ?? "");
  if (formData.get("confirm") !== "on") return { fieldErrors: { confirm: "Bevestig dat je je account wilt verwijderen" } };
  if (!(await verifyPassword(password, user.passwordHash))) return { fieldErrors: { password: "Je wachtwoord klopt niet" } };

  // Expliciet verwijderen, ook als foreign keys op de database uit staan.
  await db.transaction(async (tx) => {
    await tx.delete(sessions).where(eq(sessions.userId, user.id));
    await tx.delete(loginChallenges).where(eq(loginChallenges.userId, user.id));
    await tx.delete(recoveryCodes).where(eq(recoveryCodes.userId, user.id));
    await tx.delete(checkIns).where(eq(checkIns.userId, user.id));
    await tx.delete(appointments).where(eq(appointments.userId, user.id));
    await tx.delete(measurements).where(eq(measurements.userId, user.id));
    await tx.delete(plans).where(eq(plans.userId, user.id));
    await tx.delete(intakes).where(eq(intakes.userId, user.id));
    await tx.update(users).set({ referredById: null }).where(eq(users.referredById, user.id));
    await tx.delete(users).where(eq(users.id, user.id));
  });
  (await cookies()).delete(SESSION_COOKIE_NAME);
  redirect("/?account=verwijderd");
}
