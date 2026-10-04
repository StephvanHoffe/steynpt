import "server-only";
import { createHash, randomBytes } from "node:crypto";
import bcrypt from "bcryptjs";
import { and, eq, gt, ne } from "drizzle-orm";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { cache } from "react";
import { db, loginChallenges, sessions, users, type User } from "./db";
import { isDemoAccount } from "./demo";
import { passwordDaysLeft } from "./totp";

export const SESSION_COOKIE_NAME = "steynpt_session";
const SESSION_COOKIE = SESSION_COOKIE_NAME;
const SESSION_DAYS = 30;
// Tussenstap na het wachtwoord: de code uit de authenticator-app.
const CHALLENGE_COOKIE = "steynpt_2fa";
const CHALLENGE_MINUTES = 15;
export const MAX_CODE_ATTEMPTS = 5;

export async function hashPassword(password: string) {
  return bcrypt.hash(password, 12);
}

export async function verifyPassword(password: string, hash: string) {
  return bcrypt.compare(password, hash);
}

function hashToken(token: string) {
  return createHash("sha256").update(token).digest("hex");
}

export async function createSession(userId: string) {
  const token = randomBytes(32).toString("base64url");
  const expiresAt = new Date(Date.now() + SESSION_DAYS * 24 * 60 * 60 * 1000);
  await db.insert(sessions).values({ id: hashToken(token), userId, expiresAt });
  const jar = await cookies();
  jar.set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    expires: expiresAt,
  });
}

export async function destroySession() {
  const jar = await cookies();
  const token = jar.get(SESSION_COOKIE)?.value;
  if (token) await db.delete(sessions).where(eq(sessions.id, hashToken(token)));
  jar.delete(SESSION_COOKIE);
  await clearLoginChallenge();
}

/** Alle sessies van een lid beëindigen, behalve (optioneel) de huidige. */
export async function endOtherSessions(userId: string, keepCurrent = true) {
  const token = keepCurrent ? (await cookies()).get(SESSION_COOKIE)?.value : undefined;
  await db
    .delete(sessions)
    .where(token ? and(eq(sessions.userId, userId), ne(sessions.id, hashToken(token))) : eq(sessions.userId, userId));
}

/** Tweestapsverificatie is verplicht; alleen de vaste demo-accounts (één klik inloggen) zijn uitgezonderd. */
export const needsTwoFactor = (user: Pick<User, "email">) => !isDemoAccount(user.email);

export function passwordExpired(user: Pick<User, "email" | "passwordChangedAt" | "createdAt">, now = new Date()) {
  return !isDemoAccount(user.email) && passwordDaysLeft(user.passwordChangedAt ?? user.createdAt, now) <= 0;
}

export const getCurrentUser = cache(async (): Promise<User | null> => {
  const token = (await cookies()).get(SESSION_COOKIE)?.value;
  if (!token) return null;
  const rows = await db
    .select({ user: users })
    .from(sessions)
    .innerJoin(users, eq(sessions.userId, users.id))
    .where(and(eq(sessions.id, hashToken(token)), gt(sessions.expiresAt, new Date())))
    .limit(1);
  const user = rows[0]?.user ?? null;
  // Een sessie van vóór de tweestapsverificatie (of na een reset ervan) telt niet meer.
  if (user && needsTwoFactor(user) && !user.totpEnabledAt) return null;
  return user;
});

/** Ingelogd, maar een verlopen wachtwoord moet eerst vernieuwd worden. */
export async function requireUser(next = "/account") {
  const user = await getCurrentUser();
  if (!user) redirect(`/inloggen?next=${encodeURIComponent(next)}`);
  if (passwordExpired(user)) redirect(`/wachtwoord-vernieuwen?next=${encodeURIComponent(next)}`);
  return user;
}

// ---------------------------------------------------------------------------
// Inloggen in twee stappen: na het wachtwoord een korte tussenstap (cookie + rij in login_challenges).

export async function startLoginChallenge(userId: string, next: string) {
  const token = randomBytes(32).toString("base64url");
  const expiresAt = new Date(Date.now() + CHALLENGE_MINUTES * 60 * 1000);
  // Eén openstaande tussenstap per lid.
  await db.delete(loginChallenges).where(eq(loginChallenges.userId, userId));
  await db.insert(loginChallenges).values({ id: hashToken(token), userId, next, expiresAt });
  (await cookies()).set(CHALLENGE_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    expires: expiresAt,
  });
}

export async function getLoginChallenge() {
  const token = (await cookies()).get(CHALLENGE_COOKIE)?.value;
  if (!token) return null;
  const [row] = await db
    .select({ challenge: loginChallenges, user: users })
    .from(loginChallenges)
    .innerJoin(users, eq(loginChallenges.userId, users.id))
    .where(and(eq(loginChallenges.id, hashToken(token)), gt(loginChallenges.expiresAt, new Date())))
    .limit(1);
  return row ?? null;
}

export async function clearLoginChallenge() {
  const jar = await cookies();
  const token = jar.get(CHALLENGE_COOKIE)?.value;
  if (token) await db.delete(loginChallenges).where(eq(loginChallenges.id, hashToken(token)));
  jar.delete(CHALLENGE_COOKIE);
}

export async function requireAdmin(next = "/admin") {
  const user = await requireUser(next);
  if (user.role !== "admin") redirect("/account");
  return user;
}

export function isAdminEmail(email: string) {
  return (process.env.ADMIN_EMAILS ?? "")
    .split(",")
    .map((e) => e.trim().toLowerCase())
    .filter(Boolean)
    .includes(email.toLowerCase());
}

/** Alleen interne paden toestaan als redirect-doel na inloggen. */
export function safeNextPath(next: unknown, fallback = "/account") {
  return typeof next === "string" && next.startsWith("/") && !next.startsWith("//") ? next : fallback;
}

// Eenvoudige bescherming tegen brute-force op inloggen (per serverinstantie).
const attempts = new Map<string, { count: number; resetAt: number }>();
const MAX_ATTEMPTS = 8;
const WINDOW_MS = 15 * 60 * 1000;

export function isRateLimited(key: string) {
  const entry = attempts.get(key);
  return !!entry && entry.resetAt > Date.now() && entry.count >= MAX_ATTEMPTS;
}

export function registerFailedAttempt(key: string) {
  const now = Date.now();
  const entry = attempts.get(key);
  if (!entry || entry.resetAt <= now) attempts.set(key, { count: 1, resetAt: now + WINDOW_MS });
  else entry.count++;
}

export function clearAttempts(key: string) {
  attempts.delete(key);
}

// Onjuiste codes per lid: hooguit 6 per uur, ook verspreid over meerdere inlogpogingen.
const codeFailures = new Map<string, { count: number; resetAt: number }>();
const MAX_CODE_FAILURES = 6;
const CODE_WINDOW_MS = 60 * 60 * 1000;

export function isCodeLocked(userId: string) {
  const entry = codeFailures.get(userId);
  return !!entry && entry.resetAt > Date.now() && entry.count >= MAX_CODE_FAILURES;
}

export function registerCodeFailure(userId: string) {
  const now = Date.now();
  const entry = codeFailures.get(userId);
  if (!entry || entry.resetAt <= now) codeFailures.set(userId, { count: 1, resetAt: now + CODE_WINDOW_MS });
  else entry.count++;
}

export function clearCodeFailures(userId: string) {
  codeFailures.delete(userId);
}
