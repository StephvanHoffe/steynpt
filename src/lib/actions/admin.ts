"use server";

import { eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { z } from "zod";
import { requireAdmin } from "../auth";
import { COACHING_STATUSES, contactRequests, db, redemptions, users } from "../db";
import { POINT_TYPES } from "../loyalty";
import { awardPoints, rewardReferrerForStart } from "../points";

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
  if (coachingStatus === "actief") await rewardReferrerForStart(userId);
  revalidatePath("/admin");
}

export async function handleRedemptionAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("id"));
  const status = formData.get("status");
  if (!Number.isInteger(id) || (status !== "geleverd" && status !== "geannuleerd")) return;

  await db.transaction(async (tx) => {
    const [redemption] = await tx.select().from(redemptions).where(eq(redemptions.id, id));
    if (!redemption || redemption.status !== "aangevraagd") return;
    await tx.update(redemptions).set({ status, handledAt: new Date() }).where(eq(redemptions.id, id));
    if (status === "geannuleerd") {
      await awardPoints(
        redemption.userId,
        redemption.cost,
        POINT_TYPES.refund,
        "Inwisseling geannuleerd, punten teruggeboekt",
        `terugboeking-${redemption.id}`,
        tx,
      );
    }
  });
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

const adjustSchema = z.object({
  userId: z.string().min(1),
  amount: z.coerce.number().int().refine((n) => n !== 0 && Math.abs(n) <= 10000),
  description: z.string().trim().min(2).max(200),
});

export async function adjustPointsAction(formData: FormData) {
  await requireAdmin();
  const parsed = adjustSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, amount, description } = parsed.data;
  await awardPoints(userId, amount, POINT_TYPES.correction, description, `correctie-${Date.now()}`);
  revalidatePath("/admin");
}
