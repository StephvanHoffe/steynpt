import "server-only";
import { and, count, desc, eq, gt, sql, sum } from "drizzle-orm";
import { db, pointTransactions, users } from "./db";
import { POINT_TYPES, POINTS } from "./loyalty";

type Tx = Parameters<Parameters<typeof db.transaction>[0]>[0];
type Executor = typeof db | Tx;

/**
 * Kent punten toe. Dankzij de unieke index op (user, type, ref) wordt dezelfde
 * toekenning nooit dubbel geboekt. Geeft true terug als er iets is geboekt.
 */
export async function awardPoints(
  userId: string,
  amount: number,
  type: string,
  description: string,
  refId: string | null = null,
  executor: Executor = db,
) {
  const result = await executor
    .insert(pointTransactions)
    .values({ userId, amount, type, description, refId })
    .onConflictDoNothing();
  return result.rowsAffected > 0;
}

export async function getPointsSummary(userId: string) {
  const [row] = await db
    .select({
      balance: sum(pointTransactions.amount),
      lifetime: sql<number>`coalesce(sum(case when ${pointTransactions.amount} > 0 and ${pointTransactions.type} != ${POINT_TYPES.refund} then ${pointTransactions.amount} else 0 end), 0)`,
    })
    .from(pointTransactions)
    .where(eq(pointTransactions.userId, userId));
  return { balance: Number(row?.balance ?? 0), lifetime: Number(row?.lifetime ?? 0) };
}

export async function getPointsHistory(userId: string, limit = 20) {
  return db
    .select()
    .from(pointTransactions)
    .where(eq(pointTransactions.userId, userId))
    .orderBy(desc(pointTransactions.createdAt), desc(pointTransactions.id))
    .limit(limit);
}

/**
 * Wordt aangeroepen wanneer een lid start met een betaald traject (status
 * "actief"). Beloont de uitnodiger en controleert de vrienden-mijlpaal.
 */
export async function rewardReferrerForStart(friendId: string) {
  const [friend] = await db
    .select({ id: users.id, firstName: users.firstName, referredById: users.referredById })
    .from(users)
    .where(eq(users.id, friendId));
  if (!friend?.referredById) return;

  await awardPoints(
    friend.referredById,
    POINTS.friendStarts,
    POINT_TYPES.friendStarts,
    `${friend.firstName} is gestart met een traject`,
    friend.id,
  );

  const [{ started }] = await db
    .select({ started: count() })
    .from(pointTransactions)
    .where(
      and(
        eq(pointTransactions.userId, friend.referredById),
        eq(pointTransactions.type, POINT_TYPES.friendStarts),
        gt(pointTransactions.amount, 0),
      ),
    );
  if (started >= POINTS.friendsMilestoneCount) {
    await awardPoints(
      friend.referredById,
      POINTS.friendsMilestone,
      POINT_TYPES.friendsMilestone,
      `Mijlpaal: ${POINTS.friendsMilestoneCount} vrienden gestart`,
      `mijlpaal-${POINTS.friendsMilestoneCount}`,
    );
  }
}
