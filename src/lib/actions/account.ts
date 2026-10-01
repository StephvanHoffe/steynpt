"use server";

import { eq, sum } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { z } from "zod";
import { hashPassword, requireUser, SESSION_COOKIE_NAME, verifyPassword } from "../auth";
import { checkIns, db, pointTransactions, redemptions, sessions, users } from "../db";
import { checkInStreak, getReward, isoWeekKey, POINT_TYPES, POINTS } from "../loyalty";
import { awardPoints } from "../points";
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
  workouts: z.coerce.number().int().min(0, "Aantal trainingen klopt niet").max(21, "Aantal trainingen klopt niet"),
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

  await awardPoints(user.id, POINTS.weeklyCheckIn, POINT_TYPES.checkIn, `Wekelijkse check-in ${week}`, week);

  const weeks = await db.select({ week: checkIns.week }).from(checkIns).where(eq(checkIns.userId, user.id));
  const streak = checkInStreak(weeks.map((w) => w.week));
  let message = `Check-in opgeslagen: +${POINTS.weeklyCheckIn} punten.`;
  if (streak > 0 && streak % POINTS.streakLength === 0) {
    await awardPoints(user.id, POINTS.streakBonus, POINT_TYPES.streak, `${streak} weken op rij ingecheckt`, `streak-${week}`);
    message += ` Streak van ${streak} weken: +${POINTS.streakBonus} bonuspunten!`;
  }

  revalidatePath("/account");
  return { success: message };
}

export async function redeemRewardAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser();
  const reward = getReward(String(formData.get("rewardId") ?? ""));
  if (!reward) return { error: "Deze beloning bestaat niet (meer)." };

  const outcome = await db.transaction(async (tx) => {
    const { balance } = await getPointsSummaryTx(tx, user.id);
    if (balance < reward.cost) return "insufficient" as const;
    const [redemption] = await tx
      .insert(redemptions)
      .values({ userId: user.id, rewardId: reward.id, cost: reward.cost })
      .returning({ id: redemptions.id });
    await awardPoints(
      user.id,
      -reward.cost,
      POINT_TYPES.redemption,
      `Ingewisseld: ${reward.title}`,
      `inwisseling-${redemption.id}`,
      tx,
    );
    return "ok" as const;
  });

  if (outcome === "insufficient") return { error: "Je hebt nog niet genoeg punten voor deze beloning." };
  revalidatePath("/account");
  return { success: `Gelukt! Steyn neemt contact met je op over je beloning: ${reward.title}.` };
}

type Tx = Parameters<Parameters<typeof db.transaction>[0]>[0];

async function getPointsSummaryTx(tx: Tx, userId: string) {
  const [row] = await tx
    .select({ balance: sum(pointTransactions.amount) })
    .from(pointTransactions)
    .where(eq(pointTransactions.userId, userId));
  return { balance: Number(row?.balance ?? 0) };
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

  let success = "Je profiel is bijgewerkt.";
  if (data.phone && (await awardPoints(user.id, POINTS.profileComplete, POINT_TYPES.profileComplete, "Profiel compleet", "profiel"))) {
    success += ` +${POINTS.profileComplete} punten voor een compleet profiel!`;
  }
  revalidatePath("/account", "layout");
  return { success };
}

const passwordSchema = z
  .object({
    current: z.string().min(1, "Vul je huidige wachtwoord in"),
    password: z.string().min(8, "Kies een wachtwoord van minimaal 8 tekens").max(200),
    confirm: z.string(),
  })
  .refine((d) => d.password === d.confirm, { message: "De wachtwoorden komen niet overeen", path: ["confirm"] });

export async function changePasswordAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  const parsed = passwordSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues) };
  if (!(await verifyPassword(parsed.data.current, user.passwordHash))) {
    return { fieldErrors: { current: "Je huidige wachtwoord klopt niet" } };
  }
  await db
    .update(users)
    .set({ passwordHash: await hashPassword(parsed.data.password) })
    .where(eq(users.id, user.id));
  return { success: "Je wachtwoord is gewijzigd." };
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
  return { success: `Je aanvraag voor Online coaching ${plan.name} is ontvangen. Steyn neemt binnen 24 uur contact met je op.` };
}

export async function deleteAccountAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  const password = String(formData.get("password") ?? "");
  if (formData.get("confirm") !== "on") return { fieldErrors: { confirm: "Bevestig dat je je account wilt verwijderen" } };
  if (!(await verifyPassword(password, user.passwordHash))) return { fieldErrors: { password: "Je wachtwoord klopt niet" } };

  // Expliciet verwijderen, ook als foreign keys op de database uit staan.
  await db.transaction(async (tx) => {
    await tx.delete(sessions).where(eq(sessions.userId, user.id));
    await tx.delete(pointTransactions).where(eq(pointTransactions.userId, user.id));
    await tx.delete(checkIns).where(eq(checkIns.userId, user.id));
    await tx.delete(redemptions).where(eq(redemptions.userId, user.id));
    await tx.update(users).set({ referredById: null }).where(eq(users.referredById, user.id));
    await tx.delete(users).where(eq(users.id, user.id));
  });
  (await cookies()).delete(SESSION_COOKIE_NAME);
  redirect("/?account=verwijderd");
}
