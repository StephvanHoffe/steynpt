"use server";

import { and, eq, inArray, ne } from "drizzle-orm";
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
  instruction: z.string().trim().max(1500).optional(),
  // Handmatig: leeg beginnen of verder met het gepubliceerde schema.
  start: z.enum(["leeg", "huidig"]).default("leeg"),
});

/** Steyn laat (opnieuw) een AI-concept maken, eventueel met een extra instructie. */
export async function generatePlanAction(formData: FormData) {
  await requireAdmin();
  const parsed = jobSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, type, instruction } = parsed.data;

  const id = await createPlanJob(userId, type, instruction);
  after(() => generatePlan(id));
  redirect(planHref(type, id));
}

/** Zelf een schema opstellen: leeg, of als kopie van het huidige schema om op voort te bouwen. */
export async function createManualPlanAction(formData: FormData) {
  await requireAdmin();
  const parsed = jobSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, type, start } = parsed.data;

  const [[intakeRow], [current]] = await Promise.all([
    db.select().from(intakes).where(eq(intakes.userId, userId)),
    start === "huidig"
      ? db.select({ content: plans.content }).from(plans).where(and(eq(plans.userId, userId), eq(plans.type, type), eq(plans.status, "gepubliceerd")))
      : [],
  ]);
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const copy = current ? planSchemaFor(type).safeParse(current.content) : null;
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
      .values({ userId, type, status: "concept", source: "handmatig", content })
      .returning({ id: plans.id });
    return row.id;
  });
  redirect(planHref(type, id));
}

/** Opslaan van Steyns bewerkingen, en optioneel direct publiceren voor de klant. */
export async function savePlanAction(_prev: FormState, formData: FormData): Promise<FormState> {
  await requireAdmin();
  const id = Number(formData.get("planId"));
  const intent = formData.get("intent") === "publiceren" ? "publiceren" : "opslaan";
  const raw = String(formData.get("content") ?? "");
  if (!Number.isInteger(id) || raw.length > MAX_PLAN_BYTES) return { error: "Ongeldig schema." };

  const [plan] = await db.select().from(plans).where(eq(plans.id, id));
  if (!plan || (plan.status !== "concept" && plan.status !== "gepubliceerd")) {
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

  // Wanneer de klant toe is aan een nieuw schema. Bij publiceren of wijzigen moet die datum na vandaag liggen.
  const now = new Date();
  const today = zonedParts(now).day;
  const requested = formData.get("renewOn");
  let renewOn = isValidDay(requested) ? requested : plan.renewOn;
  if (renewOn && renewOn <= today && (intent === "publiceren" || renewOn !== plan.renewOn)) {
    return { error: "Kies bij “Nieuw schema op” een datum na vandaag." };
  }
  if (renewOn && renewOn > addDays(today, 366)) return { error: "Kies voor het volgende schema een datum binnen een jaar." };
  if (!renewOn && intent === "publiceren") renewOn = defaultRenewOn(plan.type, today, "durationWeeks" in content ? content.durationWeeks : null);

  if (intent === "publiceren") {
    await db.transaction(async (tx) => {
      await tx
        .update(plans)
        .set({ status: "vervangen", updatedAt: now })
        .where(and(eq(plans.userId, plan.userId), eq(plans.type, plan.type), eq(plans.status, "gepubliceerd"), ne(plans.id, id)));
      await tx.update(plans).set({ content, renewOn, status: "gepubliceerd", publishedAt: now, updatedAt: now }).where(eq(plans.id, id));
    });
  } else {
    await db.update(plans).set({ content, renewOn, updatedAt: now }).where(eq(plans.id, id));
  }

  revalidatePath("/admin", "layout");
  revalidatePath("/account", "layout");
  return {
    success:
      intent === "publiceren"
        ? "Gepubliceerd. De klant ziet dit schema nu in Mijn omgeving."
        : plan.status === "gepubliceerd"
          ? "Opgeslagen. De klant ziet de wijzigingen direct."
          : "Concept opgeslagen.",
  };
}
