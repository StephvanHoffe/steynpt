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
    // Vriendenactie: moment waarop Steyn de korting voor de uitnodiger heeft verrekend.
    referralRewardAt: integer("referral_reward_at", { mode: "timestamp" }),
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
    // Dag (YYYY-MM-DD, Amsterdam) waarop de klant toe is aan een nieuw schema.
    renewOn: text("renew_on"),
  },
  (t) => [index("plans_user_idx").on(t.userId, t.type, t.status)],
);

export type Intake = typeof intakes.$inferSelect;
export type Plan = typeof plans.$inferSelect;

// Metingen die Steyn invoert; de klant ziet ze als voortgang in Mijn omgeving.
export const measurements = sqliteTable(
  "measurements",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    measuredAt: integer("measured_at", { mode: "timestamp" }).notNull(),
    weight: real("weight"),
    bodyFat: real("body_fat"),
    muscleMass: real("muscle_mass"),
    waist: real("waist"),
    hip: real("hip"),
    chest: real("chest"),
    arm: real("arm"),
    thigh: real("thigh"),
    note: text("note"),
    createdAt: createdAt(),
  },
  (t) => [index("measurements_user_idx").on(t.userId, t.measuredAt)],
);

// Agenda: wekelijkse beschikbaarheid van Steyn per locatie (tijden in Europe/Amsterdam).
export const availability = sqliteTable("availability", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  weekday: integer("weekday").notNull(), // 1 = maandag ... 7 = zondag
  startTime: text("start_time").notNull(), // "07:00"
  endTime: text("end_time").notNull(), // "12:00"
  location: text("location").notNull(),
});

// Periodes waarin niet geboekt kan worden (vakantie, ziekte, privé).
export const blockedPeriods = sqliteTable("blocked_periods", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  startsAt: integer("starts_at", { mode: "timestamp" }).notNull(),
  endsAt: integer("ends_at", { mode: "timestamp" }).notNull(),
  reason: text("reason"),
});

export const APPOINTMENT_STATUSES = ["gepland", "geannuleerd"] as const;

export const appointments = sqliteTable(
  "appointments",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    userId: text("user_id")
      .notNull()
      .references(() => users.id, { onDelete: "cascade" }),
    type: text("type").notNull(),
    location: text("location").notNull(),
    startsAt: integer("starts_at", { mode: "timestamp" }).notNull(),
    endsAt: integer("ends_at", { mode: "timestamp" }).notNull(),
    status: text("status", { enum: APPOINTMENT_STATUSES }).notNull().default("gepland"),
    note: text("note"),
    cancelledBy: text("cancelled_by", { enum: ["klant", "steyn"] }),
    cancelledAt: integer("cancelled_at", { mode: "timestamp" }),
    createdAt: createdAt(),
  },
  (t) => [index("appointments_start_idx").on(t.startsAt), index("appointments_user_idx").on(t.userId)],
);

// Eenvoudige sleutel/waarde-instellingen, zoals het geheime token van de iCal-feed.
export const settings = sqliteTable("settings", {
  key: text("key").primaryKey(),
  value: text("value").notNull(),
});

export type Measurement = typeof measurements.$inferSelect;
export type Appointment = typeof appointments.$inferSelect;
export type AvailabilityWindow = typeof availability.$inferSelect;
export type BlockedPeriod = typeof blockedPeriods.$inferSelect;
