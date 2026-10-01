import "server-only";
import { eq } from "drizzle-orm";
import { cookies } from "next/headers";
import { REFERRAL_COOKIE } from "./constants";
import { db, users } from "./db";
import { normalizeReferralCode } from "./loyalty";

/** Zoekt de uitnodiger op basis van een code (uit de URL of de uitnodigingscookie). */
export async function resolveInvitation(codeFromUrl?: string | string[]) {
  const raw = typeof codeFromUrl === "string" ? codeFromUrl : (await cookies()).get(REFERRAL_COOKIE)?.value;
  const code = normalizeReferralCode(raw);
  if (!code) return null;
  const [inviter] = await db
    .select({ firstName: users.firstName })
    .from(users)
    .where(eq(users.referralCode, code))
    .limit(1);
  return inviter ? { code, firstName: inviter.firstName } : null;
}
