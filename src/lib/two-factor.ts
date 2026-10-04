import "server-only";
import { and, count, eq, isNull } from "drizzle-orm";
import { db, loginChallenges, recoveryCodes, sessions, users } from "./db";
import { generateRecoveryCodes, hashRecoveryCode } from "./totp";

/** Nieuwe herstelcodes: de oude vervallen. Alleen de hashes worden bewaard; de codes zelf zie je één keer. */
export async function replaceRecoveryCodes(userId: string) {
  const codes = generateRecoveryCodes();
  await db.transaction(async (tx) => {
    await tx.delete(recoveryCodes).where(eq(recoveryCodes.userId, userId));
    await tx.insert(recoveryCodes).values(codes.map((code) => ({ userId, codeHash: hashRecoveryCode(code) })));
  });
  return codes;
}

/** Tweestapsverificatie uitzetten en overal uitloggen; bij de volgende keer inloggen stelt het lid hem opnieuw in. */
export async function clearTwoFactor(userId: string) {
  await db.transaction(async (tx) => {
    await tx.update(users).set({ totpSecret: null, totpEnabledAt: null, totpLastStep: null }).where(eq(users.id, userId));
    await tx.delete(recoveryCodes).where(eq(recoveryCodes.userId, userId));
    await tx.delete(loginChallenges).where(eq(loginChallenges.userId, userId));
    await tx.delete(sessions).where(eq(sessions.userId, userId));
  });
}

export async function remainingRecoveryCodes(userId: string) {
  const [row] = await db
    .select({ n: count() })
    .from(recoveryCodes)
    .where(and(eq(recoveryCodes.userId, userId), isNull(recoveryCodes.usedAt)));
  return row.n;
}
