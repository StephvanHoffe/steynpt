"use server";

import { randomUUID } from "node:crypto";
import { eq } from "drizzle-orm";
import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import { z } from "zod";
import {
  clearAttempts,
  createSession,
  hashPassword,
  isAdminEmail,
  isRateLimited,
  registerFailedAttempt,
  safeNextPath,
  verifyPassword,
} from "../auth";
import { REFERRAL_COOKIE } from "../constants";
import { db, users } from "../db";
import { makeReferralCode, normalizeReferralCode } from "../referral-program";
import { getOnlinePlan, GOALS } from "../site";
import { fieldErrorsFrom, formValues, type FormState } from "./types";

const registerSchema = z.object({
  firstName: z.string().trim().min(1, "Vul je voornaam in").max(60),
  lastName: z.string().trim().min(1, "Vul je achternaam in").max(80),
  email: z.email("Vul een geldig e-mailadres in").trim().toLowerCase().max(200),
  phone: z.string().trim().max(30).optional(),
  password: z.string().min(8, "Kies een wachtwoord van minimaal 8 tekens").max(200),
  goal: z.enum(GOALS.map((g) => g.id) as [string, ...string[]], "Kies je belangrijkste doel"),
  plan: z.string().optional(),
  referralCode: z.string().optional(),
  terms: z.literal("on", "Je moet akkoord gaan met de privacyverklaring"),
  marketing: z.literal("on").optional(),
});

export async function registerAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const values = formValues(formData, ["password"]);
  const parsed = registerSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };
  const data = parsed.data;

  const existing = await db.select({ id: users.id }).from(users).where(eq(users.email, data.email)).limit(1);
  if (existing.length) {
    return {
      fieldErrors: { email: "Er bestaat al een account met dit e-mailadres. Log in om verder te gaan." },
      values,
    };
  }

  const plan = getOnlinePlan(data.plan);
  const code = normalizeReferralCode(data.referralCode);
  const referrer = code
    ? (
        await db
          .select({ id: users.id })
          .from(users)
          .where(eq(users.referralCode, code))
          .limit(1)
      )[0]
    : undefined;
  if (data.referralCode?.trim() && !referrer) {
    return { fieldErrors: { referralCode: "Deze uitnodigingscode kennen we niet. Controleer de code of laat het veld leeg." }, values };
  }

  const id = randomUUID();
  const passwordHash = await hashPassword(data.password);
  const phone = data.phone || null;

  let inserted = false;
  for (let attempt = 0; attempt < 5 && !inserted; attempt++) {
    const result = await db
      .insert(users)
      .values({
        id,
        email: data.email,
        passwordHash,
        firstName: data.firstName,
        lastName: data.lastName,
        phone,
        goal: data.goal,
        plan: plan?.id ?? null,
        coachingStatus: plan ? "aangevraagd" : "geen",
        referralCode: makeReferralCode(data.firstName),
        referredById: referrer?.id ?? null,
        role: isAdminEmail(data.email) ? "admin" : "member",
        marketingOptIn: data.marketing === "on",
      })
      .onConflictDoNothing();
    inserted = result.rowsAffected > 0;
    if (!inserted) {
      // Botsing op e-mail (gelijktijdige registratie) of op de uitnodigingscode.
      const dupe = await db.select({ id: users.id }).from(users).where(eq(users.email, data.email)).limit(1);
      if (dupe.length) return { fieldErrors: { email: "Er bestaat al een account met dit e-mailadres." }, values };
    }
  }
  if (!inserted) return { error: "Er ging iets mis bij het aanmaken van je account. Probeer het opnieuw.", values };

  (await cookies()).delete(REFERRAL_COOKIE);
  await createSession(id);
  redirect("/account?welkom=1");
}

const loginSchema = z.object({
  email: z.email("Vul een geldig e-mailadres in").trim().toLowerCase(),
  password: z.string().min(1, "Vul je wachtwoord in"),
  next: z.string().optional(),
});

export async function loginAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const values = formValues(formData, ["password"]);
  const parsed = loginSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return { fieldErrors: fieldErrorsFrom(parsed.error.issues), values };
  const { email, password, next } = parsed.data;

  const ip = (await headers()).get("x-forwarded-for")?.split(",")[0]?.trim() ?? "local";
  const key = `${email}|${ip}`;
  if (isRateLimited(key)) {
    return { error: "Te veel inlogpogingen. Probeer het over een kwartier opnieuw.", values };
  }

  const [user] = await db.select().from(users).where(eq(users.email, email)).limit(1);
  if (!user || !(await verifyPassword(password, user.passwordHash))) {
    registerFailedAttempt(key);
    return { error: "E-mailadres of wachtwoord klopt niet.", values };
  }
  clearAttempts(key);

  if (user.role !== "admin" && isAdminEmail(user.email)) {
    await db.update(users).set({ role: "admin" }).where(eq(users.id, user.id));
  }

  await createSession(user.id);
  redirect(safeNextPath(next));
}
