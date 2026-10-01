import { sql } from "drizzle-orm";
import { index, integer, real, sqliteTable, text, uniqueIndex } from "drizzle-orm/sqlite-core";

const createdAt = () =>
  integer("created_at", { mode: "timestamp" })
    .notNull()
    .default(sql`(unixepoch())`);

export const COACHING_STATUSES = ["geen", "aangevraagd", "actief", "gepauzeerd", "gestopt"] as const;
export type CoachingStatus = (typeof COACHING_STATUSES)[number];

export const users = sqliteTable(
  "users",
  {
    id: text("id").primaryKey(),
    email: text("email").notNull(),
    passwordHash: text("password_hash").notNull(),
    firstName: text("first_name").notNull(),
    lastName: text("last_name").notNull(),
    phone: text("phone"),
    goal: text("goal"),
    plan: text("plan"),
    coachingStatus: text("coaching_status", { enum: COACHING_STATUSES }).notNull().default("geen"),
    coachNote: text("coach_note"),
    referralCode: text("referral_code").notNull(),
    referredById: text("referred_by_id"),
    role: text("role", { enum: ["member", "admin"] }).notNull().default("member"),
    marketingOptIn: integer("marketing_opt_in", { mode: "boolean" }).notNull().default(false),
    createdAt: createdAt(),
  },
  (t) => [
    uniqueIndex("users_email_idx").on(t.email),
    uniqueIndex("users_referral_code_idx").on(t.referralCode),
    index("users_referred_by_idx").on(t.referredById),
  ],
);

export const sessions = sqliteTable(
  "sessions",
  {
    // SHA-256 hash van het sessietoken; het token zelf staat alleen in de cookie.
    id: text("id").primaryKey(),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    expiresAt: integer("expires_at", { mode: "timestamp" }).notNull(),
    createdAt: createdAt(),
  },
  (t) => [index("sessions_user_idx").on(t.userId)],
);

export const pointTransactions = sqliteTable(
  "point_transactions",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    amount: integer("amount").notNull(),
    type: text("type").notNull(),
    description: text("description").notNull(),
    // Maakt toekenningen idempotent: dezelfde (user, type, ref) kan maar één keer.
    refId: text("ref_id"),
    createdAt: createdAt(),
  },
  (t) => [
    index("points_user_idx").on(t.userId),
    uniqueIndex("points_unique_award_idx").on(t.userId, t.type, t.refId),
  ],
);

export const checkIns = sqliteTable(
  "check_ins",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    week: text("week").notNull(),
    weight: real("weight"),
    energy: integer("energy").notNull(),
    sleep: integer("sleep").notNull(),
    nutrition: integer("nutrition").notNull(),
    workouts: integer("workouts").notNull(),
    note: text("note"),
    createdAt: createdAt(),
  },
  (t) => [uniqueIndex("check_ins_user_week_idx").on(t.userId, t.week)],
);

export const REDEMPTION_STATUSES = ["aangevraagd", "geleverd", "geannuleerd"] as const;

export const redemptions = sqliteTable(
  "redemptions",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    rewardId: text("reward_id").notNull(),
    cost: integer("cost").notNull(),
    status: text("status", { enum: REDEMPTION_STATUSES }).notNull().default("aangevraagd"),
    createdAt: createdAt(),
    handledAt: integer("handled_at", { mode: "timestamp" }),
  },
  (t) => [index("redemptions_user_idx").on(t.userId)],
);

export const contactRequests = sqliteTable("contact_requests", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  name: text("name").notNull(),
  email: text("email").notNull(),
  phone: text("phone"),
  interest: text("interest").notNull(),
  message: text("message"),
  handled: integer("handled", { mode: "boolean" }).notNull().default(false),
  createdAt: createdAt(),
});

export type User = typeof users.$inferSelect;
export type CheckIn = typeof checkIns.$inferSelect;
export type Redemption = typeof redemptions.$inferSelect;
export type ContactRequest = typeof contactRequests.$inferSelect;

// Intake van de klant: één per lid, de inhoud is gevalideerd met intakeSchema (src/lib/intake.ts).
export const intakes = sqliteTable("intakes", {
  userId: text("user_id")
    .primaryKey()
    .references(() => users.id, { onDelete: "cascade" }),
  data: text("data", { mode: "json" }).notNull(),
  createdAt: createdAt(),
  updatedAt: integer("updated_at", { mode: "timestamp" })
    .notNull()
    .default(sql`(unixepoch())`),
});

export const PLAN_TYPES = ["training", "voeding"] as const;
export type PlanType = (typeof PLAN_TYPES)[number];

// genereren -> concept (of fout) -> gepubliceerd; oude versies worden "vervangen".
export const PLAN_STATUSES = ["genereren", "fout", "concept", "gepubliceerd", "vervangen"] as const;
export type PlanStatus = (typeof PLAN_STATUSES)[number];

export const plans = sqliteTable(
  "plans",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    type: text("type", { enum: PLAN_TYPES }).notNull(),
    status: text("status", { enum: PLAN_STATUSES }).notNull().default("genereren"),
    // Huidige (door Steyn bewerkte) inhoud en het oorspronkelijke AI-concept.
    content: text("content", { mode: "json" }),
    aiDraft: text("ai_draft", { mode: "json" }),
    source: text("source", { enum: ["ai", "handmatig"] }).notNull().default("ai"),
    instruction: text("instruction"),
    error: text("error"),
    model: text("model"),
    createdAt: createdAt(),
    updatedAt: integer("updated_at", { mode: "timestamp" })
      .notNull()
      .default(sql`(unixepoch())`),
    publishedAt: integer("published_at", { mode: "timestamp" }),
  },
  (t) => [index("plans_user_idx").on(t.userId, t.type, t.status)],
);

export type Intake = typeof intakes.$inferSelect;
export type Plan = typeof plans.$inferSelect;
