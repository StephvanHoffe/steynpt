"use server";

import { and, eq, ne } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { after } from "next/server";
import { z } from "zod";
import { requireAdmin } from "../auth";
import { db, intakes, PLAN_TYPES, plans } from "../db";
import { estimateTargets, intakeSchema } from "../intake";
import { createPlanJob, generatePlan } from "../plans/generate";
import { emptyNutritionPlan, emptyTrainingPlan, planSchemaFor } from "../plans/schema";
import type { FormState } from "./types";

const MAX_PLAN_BYTES = 200_000;

const jobSchema = z.object({
  userId: z.string().min(1),
  type: z.enum(PLAN_TYPES),
  instruction: z.string().trim().max(1500).optional(),
});

/** Steyn laat (opnieuw) een AI-concept maken, eventueel met een extra instructie. */
export async function generatePlanAction(formData: FormData) {
  await requireAdmin();
  const parsed = jobSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, type, instruction } = parsed.data;

  const id = await createPlanJob(userId, type, instruction);
  after(() => generatePlan(id));
  redirect(`/admin/schemas/${id}`);
}

/** Leeg schema om zelf te vullen (bijv. als de AI niet beschikbaar is). */
export async function createManualPlanAction(formData: FormData) {
  await requireAdmin();
  const parsed = jobSchema.safeParse(Object.fromEntries(formData));
  if (!parsed.success) return;
  const { userId, type } = parsed.data;

  const [intakeRow] = await db.select().from(intakes).where(eq(intakes.userId, userId));
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const content =
    type === "training"
      ? emptyTrainingPlan(intake.success ? intake.data.trainingDays : 3)
      : emptyNutritionPlan(intake.success ? estimateTargets(intake.data) : undefined);

  const id = await db.transaction(async (tx) => {
    await tx
      .update(plans)
      .set({ status: "vervangen", updatedAt: new Date() })
      .where(and(eq(plans.userId, userId), eq(plans.type, type), ne(plans.status, "gepubliceerd"), ne(plans.status, "vervangen")));
    const [row] = await tx
      .insert(plans)
      .values({ userId, type, status: "concept", source: "handmatig", content })
      .returning({ id: plans.id });
    return row.id;
  });
  redirect(`/admin/schemas/${id}`);
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

  const now = new Date();
  if (intent === "publiceren") {
    await db.transaction(async (tx) => {
      await tx
        .update(plans)
        .set({ status: "vervangen", updatedAt: now })
        .where(and(eq(plans.userId, plan.userId), eq(plans.type, plan.type), eq(plans.status, "gepubliceerd"), ne(plans.id, id)));
      await tx.update(plans).set({ content, status: "gepubliceerd", publishedAt: now, updatedAt: now }).where(eq(plans.id, id));
    });
  } else {
    await db.update(plans).set({ content, updatedAt: now }).where(eq(plans.id, id));
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
