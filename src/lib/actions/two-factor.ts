"use server";

import { and, eq, isNull, lt, or } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import {
  clearCodeFailures,
  clearLoginChallenge,
  createSession,
  getLoginChallenge,
  isCodeLocked,
  MAX_CODE_ATTEMPTS,
  registerCodeFailure,
  requireAdmin,
  requireUser,
} from "../auth";
import { db, loginChallenges, recoveryCodes, users } from "../db";
import { hashRecoveryCode, isRecoveryCodeFormat, verifyTotp } from "../totp";
import { clearTwoFactor, replaceRecoveryCodes } from "../two-factor";
import type { FormState } from "./types";

export type CodesState = FormState & { codes?: string[] };

const LOCKED = "Te veel onjuiste codes. Probeer het over een uur opnieuw, of neem contact op met SteynPT.";

/** Code uit de app (6 cijfers) of een herstelcode. Werkt de laatst gebruikte tijdstap bij. */
async function checkCode(user: { id: string; totpSecret: string | null; totpLastStep: number | null }, raw: string) {
  const code = raw.trim();
  if (user.totpSecret && /^\d{3}\s?\d{3}$/.test(code)) {
    const step = verifyTotp(user.totpSecret, code, { lastStep: user.totpLastStep });
    if (step === null) return false;
    // Alleen als niemand deze code intussen al gebruikte (twee keer tegelijk versturen).
    const claimed = await db
      .update(users)
      .set({ totpLastStep: step })
      .where(and(eq(users.id, user.id), or(isNull(users.totpLastStep), lt(users.totpLastStep, step))))
      .returning({ id: users.id });
    return claimed.length > 0;
  }
  if (isRecoveryCodeFormat(code)) {
    const used = await db
      .update(recoveryCodes)
      .set({ usedAt: new Date() })
      .where(and(eq(recoveryCodes.userId, user.id), eq(recoveryCodes.codeHash, hashRecoveryCode(code)), isNull(recoveryCodes.usedAt)))
      .returning({ id: recoveryCodes.id });
    return used.length > 0;
  }
  return false;
}

/** Tweede stap van het inloggen: de code uit de authenticator-app (of een herstelcode). */
export async function verifyLoginCodeAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const pending = await getLoginChallenge();
  if (!pending) return { error: "Je inlogpoging is verlopen. Log opnieuw in." };
  const { challenge, user } = pending;
  if (!user.totpEnabledAt) return { error: "Stel eerst de tweestapsverificatie in. Ververs de pagina." };
  if (isCodeLocked(user.id)) return { error: LOCKED };

  if (!(await checkCode(user, String(formData.get("code") ?? "")))) {
    registerCodeFailure(user.id);
    const attempts = challenge.attempts + 1;
    if (attempts >= MAX_CODE_ATTEMPTS) {
      await clearLoginChallenge();
      redirect("/inloggen?melding=te-veel-codes");
    }
    await db.update(loginChallenges).set({ attempts }).where(eq(loginChallenges.id, challenge.id));
    return { fieldErrors: { code: "Deze code klopt niet. Kijk of de tijd op je telefoon goed staat en probeer de nieuwste code." } };
  }

  clearCodeFailures(user.id);
  await clearLoginChallenge();
  await createSession(user.id);
  redirect(challenge.next);
}

/** Tweestapsverificatie instellen: bevestigen met de eerste code uit de app, daarna de herstelcodes tonen. */
export async function confirmTwoFactorSetupAction(_prev: CodesState, formData: FormData): Promise<CodesState> {
  const pending = await getLoginChallenge();
  if (!pending) return { error: "Je inlogpoging is verlopen. Log opnieuw in." };
  const { challenge, user } = pending;
  if (user.totpEnabledAt || !challenge.pendingSecret) return { error: "Er ging iets mis. Log opnieuw in." };
  if (isCodeLocked(user.id)) return { error: LOCKED };

  const step = verifyTotp(challenge.pendingSecret, String(formData.get("code") ?? ""));
  if (step === null) {
    registerCodeFailure(user.id);
    return { fieldErrors: { code: "Deze code klopt niet. Scan de QR-code opnieuw en vul de code in die de app nu toont." } };
  }

  const enabled = await db
    .update(users)
    .set({ totpSecret: challenge.pendingSecret, totpEnabledAt: new Date(), totpLastStep: step })
    .where(and(eq(users.id, user.id), isNull(users.totpEnabledAt)))
    .returning({ id: users.id });
  if (!enabled.length) return { error: "Er ging iets mis. Log opnieuw in." };
  const codes = await replaceRecoveryCodes(user.id);
  clearCodeFailures(user.id);
  // Nog niet inloggen: eerst de herstelcodes tonen. De tussenstap blijft staan tot "Verder".
  return { success: "Tweestapsverificatie staat aan.", codes };
}

/** Na het bewaren van de herstelcodes: inloggen en door naar de pagina waar het lid heen wilde. */
export async function finishTwoFactorSetupAction() {
  const pending = await getLoginChallenge();
  if (!pending) redirect("/inloggen?melding=verlopen");
  const { challenge, user } = pending;
  if (!user.totpEnabledAt || !challenge.pendingSecret || challenge.pendingSecret !== user.totpSecret) redirect("/inloggen/verificatie");
  await clearLoginChallenge();
  await createSession(user.id);
  redirect(challenge.next);
}

/** Profiel: nieuwe herstelcodes, na bevestiging met een code uit de app. */
export async function regenerateRecoveryCodesAction(_prev: CodesState, formData: FormData): Promise<CodesState> {
  const user = await requireUser("/account/profiel");
  if (!user.totpEnabledAt) return { error: "Tweestapsverificatie staat niet aan." };
  if (isCodeLocked(user.id)) return { error: LOCKED };
  const code = String(formData.get("code") ?? "");
  if (!/^\d{3}\s?\d{3}$/.test(code.trim()) || !(await checkCode(user, code))) {
    registerCodeFailure(user.id);
    return { fieldErrors: { code: "Vul de code in die je app nu toont." } };
  }
  const codes = await replaceRecoveryCodes(user.id);
  revalidatePath("/account/profiel");
  return { success: "Nieuwe herstelcodes gemaakt. De oude werken niet meer.", codes };
}

/** Profiel: andere telefoon. Zet de tweestapsverificatie uit; bij de volgende keer inloggen stel je hem opnieuw in. */
export async function resetOwnTwoFactorAction(_prev: FormState, formData: FormData): Promise<FormState> {
  const user = await requireUser("/account/profiel");
  if (isCodeLocked(user.id)) return { error: LOCKED };
  if (!(await checkCode(user, String(formData.get("code") ?? "")))) {
    registerCodeFailure(user.id);
    return { fieldErrors: { code: "Deze code klopt niet." } };
  }
  await clearTwoFactor(user.id);
  redirect("/inloggen?melding=2fa-opnieuw");
}

/** Beheer: tweestapsverificatie van een lid resetten (telefoon kwijt en geen herstelcodes meer). */
export async function resetMemberTwoFactorAction(formData: FormData) {
  await requireAdmin();
  const userId = String(formData.get("userId") ?? "");
  const [member] = await db.select({ id: users.id, role: users.role }).from(users).where(eq(users.id, userId));
  if (!member || member.role !== "member") return;
  await clearTwoFactor(member.id);
  revalidatePath(`/admin/leden/${member.id}`);
}
