"use server";

import { and, eq, isNull } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { z } from "zod";
import { requireAdmin } from "../auth";
import { COACHING_STATUSES, contactRequests, db, users } from "../db";

const memberSchema = z.object({
  userId: z.string().min(1),
  coachingStatus: z.enum(COACHING_STATUSES),
  coachNote: z.string().trim().max(2000).optional(),
});

export async function updateMemberAction(formData: FormData) {
  await requireAdmin();
  const parsed = memberSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, coachingStatus, coachNote } = parsed.data;

  await db
    .update(users)
    .set({ coachingStatus, coachNote: coachNote || null })
    .where(eq(users.id, userId));
  revalidatePath("/admin");
}

/** Vriendenactie: de korting voor de uitnodiger is verrekend. */
export async function markReferralRewardAction(formData: FormData) {
  await requireAdmin();
  const friendId = String(formData.get("friendId") ?? "");
  if (!friendId) return;
  await db
    .update(users)
    .set({ referralRewardAt: new Date() })
    .where(and(eq(users.id, friendId), isNull(users.referralRewardAt)));
  revalidatePath("/admin");
}

export async function toggleContactHandledAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  if (!Number.isInteger(id)) return;
  const [request] = await db.select().from(contactRequests).where(eq(contactRequests.id, id));
  if (!request) return;
  await db.update(contactRequests).set({ handled: !request.handled }).where(eq(contactRequests.id, id));
  revalidatePath("/admin");
}
