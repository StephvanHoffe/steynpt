"use server";

import { and, desc, eq, inArray, ne } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { after } from "next/server";
import { z } from "zod";
import { addDays, isValidDay, zonedParts } from "../agenda";
import { requireAdmin } from "../auth";
import { db, intakes, PLAN_TYPES, plans } from "../db";
import { estimateTargets, intakeSchema } from "../intake";
import { createPlanJob, generatePlan } from "../plans/generate";
import { defaultRenewOn } from "../plans/pipeline";
import { emptyNutritionPlan, emptyTrainingPlan, planSchemaFor } from "../plans/schema";
import { planHref } from "../plans/sections";
import type { FormState } from "./types";

const MAX_PLAN_BYTES = 200_000;

const jobSchema = z.object({
  userId: z.string().min(1),
  type: z.enum(PLAN_TYPES),
  // AI-concept, kopie van het laatste goedgekeurde schema, of leeg beginnen.
  method: z.enum(["ai", "huidig", "leeg"]).default("leeg"),
  instruction: z.string().trim().max(1500).optional(),
  startsOn: z.string().optional(),
});

/** Startdatum uit een formulier: een dag na vandaag, anders null (= gaat in zodra het gepubliceerd is). */
const futureDay = (value: unknown, today: string) => (isValidDay(value) && value > today ? value : null);

const dayFmt = new Intl.DateTimeFormat("nl-NL", { weekday: "long", day: "numeric", month: "long", timeZone: "UTC" });
const formatDay = (day: string) => dayFmt.format(new Date(`${day}T12:00:00Z`));

/** Nieuw schema voor een klant, eventueel met een startdatum in de toekomst. */
export async function createPlanAction(formData: FormData) {
  await requireAdmin();
  const parsed = jobSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, type, method, instruction } = parsed.data;
  const startsOn = futureDay(parsed.data.startsOn, zonedParts(new Date()).day);

  if (method === "ai") {
    const id = await createPlanJob(userId, type, instruction, startsOn);
    after(() => generatePlan(id));
    redirect(planHref(type, id));
  }

  const [[intakeRow], [latest]] = await Promise.all([
    db.select().from(intakes).where(eq(intakes.userId, userId)),
    method === "huidig"
      ? db
          .select({ content: plans.content })
          .from(plans)
          .where(and(eq(plans.userId, userId), eq(plans.type, type), inArray(plans.status, ["gepland", "gepubliceerd"])))
          .orderBy(desc(plans.createdAt))
          .limit(1)
      : [],
  ]);
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const copy = latest ? planSchemaFor(type).safeParse(latest.content) : null;
  const content = copy?.success
    ? copy.data
    : type === "training"
      ? emptyTrainingPlan(intake.success ? intake.data.trainingDays : 3)
      : emptyNutritionPlan(intake.success ? estimateTargets(intake.data) : undefined);

  const id = await db.transaction(async (tx) => {
    await tx
      .update(plans)
      .set({ status: "vervangen", updatedAt: new Date() })
      .where(and(eq(plans.userId, userId), eq(plans.type, type), inArray(plans.status, ["genereren", "concept", "fout"])));
    const [row] = await tx
      .insert(plans)
      .values({ userId, type, status: "concept", source: "handmatig", content, startsOn })
      .returning({ id: plans.id });
    return row.id;
  });
  redirect(planHref(type, id));
}

/** Ingepland schema terugzetten naar concept: de klant krijgt het dan niet op de startdatum. */
export async function unschedulePlanAction(formData: FormData) {
  await requireAdmin();
  const id = Number(formData.get("planId"));
  if (!Number.isInteger(id)) return;
  await db.update(plans).set({ status: "concept", updatedAt: new Date() }).where(and(eq(plans.id, id), eq(plans.status, "gepland")));
  revalidatePath("/admin", "layout");
}

/** Opslaan van Steyns bewerkingen, en optioneel direct publiceren voor de klant. */
export async function savePlanAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const id = Number(formData.get("planId"));
  const intent = formData.get("intent") === "publiceren" ? "publiceren" : "opslaan";
  const raw = String(formData.get("content") ?? "");
  if (!Number.isInteger(id) || raw.length > MAX_PLAN_BYTES) return { error: "Ongeldig schema." };

  const [plan] = await db.select().from(plans).where(eq(plans.id, id));
  if (!plan || (plan.status !== "concept" && plan.status !== "gepland" && plan.status !== "gepubliceerd")) {
    return { error: "Dit schema kan niet (meer) bewerkt worden. Ververs de pagina." };
  }

  let json: unknown;
  try {
    json = JSON.parse(raw);
  } catch {
    return { error: "Het schema kon niet gelezen worden." };
  }
  const parsed = planSchemaFor(plan.type).safeParse(json);
  if (!parsed.success) return { error: "Het schema is niet compleet. Controleer of alle getallen zijn ingevuld." };
  const content = parsed.data;
  if ("days" in content) content.daysPerWeek = content.days.length;

  const now = new Date();
  const today = zonedParts(now).day;
  const live = plan.status === "gepubliceerd";
  // De startdatum ligt vast zodra de klant het schema ziet; daarvoor kan hij nog schuiven.
  const startsOn = live ? plan.startsOn : futureDay(formData.get("startsOn"), today);
  const startDay = live ? (plan.startsOn ?? (plan.publishedAt ? zonedParts(plan.publishedAt).day : today)) : (startsOn ?? today);
  const schedule = !live && startsOn !== null && (intent === "publiceren" || plan.status === "gepland");
  const publishNow = !live && startsOn === null && (intent === "publiceren" || plan.status === "gepland");

  // Wanneer de klant toe is aan een nieuw schema: na de start (en na vandaag) als je hem kiest of publiceert.
  const requested = formData.get("renewOn");
  let renewOn = isValidDay(requested) ? requested : plan.renewOn;
  const minRenew = startDay > today ? startDay : today;
  if (renewOn && renewOn <= minRenew && (intent === "publiceren" || renewOn !== plan.renewOn)) {
    return { error: startDay > today ? "Kies bij “Nieuw schema op” een datum na de startdatum." : "Kies bij “Nieuw schema op” een datum na vandaag." };
  }
  if (renewOn && renewOn > addDays(startDay, 366)) return { error: "Kies voor het volgende schema een datum binnen een jaar na de start." };
  if (!renewOn && (schedule || publishNow)) renewOn = defaultRenewOn(plan.type, startDay, "durationWeeks" in content ? content.durationWeeks : null);

  const others = and(eq(plans.userId, plan.userId), eq(plans.type, plan.type), ne(plans.id, id));
  if (schedule) {
    // Er is steeds één ingepland schema; het huidige blijft zichtbaar tot de startdatum.
    await db.transaction(async (tx) => {
      await tx.update(plans).set({ status: "vervangen", updatedAt: now }).where(and(others, eq(plans.status, "gepland")));
      await tx.update(plans).set({ content, renewOn, startsOn, status: "gepland", publishedAt: null, updatedAt: now }).where(eq(plans.id, id));
    });
  } else if (publishNow || (live && intent === "publiceren")) {
    // Een ingepland volgend schema blijft staan en neemt het op zijn startdatum over.
    await db.transaction(async (tx) => {
      await tx.update(plans).set({ status: "vervangen", updatedAt: now }).where(and(others, eq(plans.status, "gepubliceerd")));
      await tx
        .update(plans)
        .set({ content, renewOn, startsOn: live ? plan.startsOn : today, status: "gepubliceerd", publishedAt: now, updatedAt: now })
        .where(eq(plans.id, id));
    });
  } else {
    await db.update(plans).set({ content, renewOn, startsOn, updatedAt: now }).where(eq(plans.id, id));
  }

  revalidatePath("/admin", "layout");
  revalidatePath("/account", "layout");
  let success: string;
  if (schedule) success = `${intent === "publiceren" ? "Ingepland" : "Opgeslagen"}. De klant ziet dit schema vanaf ${formatDay(startsOn!)} in Mijn omgeving.`;
  else if (publishNow || (live && intent === "publiceren")) success = "Gepubliceerd. De klant ziet dit schema nu in Mijn omgeving.";
  else if (live) success = "Opgeslagen. De klant ziet de wijzigingen direct.";
  else success = "Concept opgeslagen.";
  return { success };
}
