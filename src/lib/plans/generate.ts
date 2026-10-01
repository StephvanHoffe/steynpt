import "server-only";
import Anthropic from "@anthropic-ai/sdk";
import { betaZodOutputFormat } from "@anthropic-ai/sdk/helpers/beta/zod";
import { and, count, eq, gt, inArray } from "drizzle-orm";
import { db, intakes, plans, type PlanType, type User } from "../db";
import { DEMO_MODE } from "../demo";
import { intakeSchema } from "../intake";
import { mockNutritionPlan, mockTrainingPlan } from "./mock";
import { buildPlanPrompt } from "./prompt";
import { nutritionPlanSchema, trainingPlanSchema, type PlanContent } from "./schema";

export const AI_MODEL = "claude-opus-5-5";
const MAX_AUTO_PER_DAY = 6;

// In de demoversie nooit de echte API aanroepen, ook niet als er een sleutel staat.
const mockMode = () => process.env.AI_MOCK === "1" || DEMO_MODE;

export function aiConfigured() {
  return mockMode() || Boolean(process.env.ANTHROPIC_API_KEY || process.env.ANTHROPIC_AUTH_TOKEN);
}

class PlanGenerationError extends Error {}

async function callClaude(type: PlanType, prompt: { system: string; user: string }): Promise<PlanContent> {
  const client = new Anthropic();
  const format = type === "training" ? betaZodOutputFormat(trainingPlanSchema) : betaZodOutputFormat(nutritionPlanSchema);

  // Streaming voorkomt HTTP-timeouts bij een groot max_tokens.
  const stream = client.beta.messages.stream({
    model: AI_MODEL,
    max_tokens: 32000,
    // Bij een (onterechte) weigering probeert de API het automatisch op een ander model.
    betas: ["server-side-fallback-2026-07-01"],
    fallbacks: "default",
    thinking: { type: "adaptive" },
    output_config: { effort: "high", format },
    system: prompt.system,
    messages: [{ role: "user", content: prompt.user }],
  });
  const message = await stream.finalMessage();

  if (message.stop_reason === "refusal") {
    throw new PlanGenerationError("De AI heeft dit verzoek geweigerd. Maak het schema handmatig of pas de intake/instructie aan.");
  }
  if (message.stop_reason === "max_tokens") {
    throw new PlanGenerationError("Het antwoord van de AI was te lang en is afgebroken. Probeer het opnieuw.");
  }
  if (!message.parsed_output) {
    throw new PlanGenerationError("Het antwoord van de AI had niet de verwachte vorm. Probeer het opnieuw.");
  }
  return message.parsed_output;
}

function describeError(error: unknown) {
  if (error instanceof PlanGenerationError) return error.message;
  if (error instanceof Anthropic.AuthenticationError) return "De API-sleutel voor de AI is ongeldig. Controleer ANTHROPIC_API_KEY.";
  if (error instanceof Anthropic.RateLimitError) return "De AI is tijdelijk overbelast. Probeer het over een paar minuten opnieuw.";
  if (error instanceof Anthropic.APIConnectionError) return "Geen verbinding met de AI. Probeer het later opnieuw.";
  if (error instanceof Anthropic.APIError) return `De AI gaf een foutmelding (${error.status ?? "onbekend"}). Probeer het opnieuw.`;
  return "Er ging iets mis bij het maken van het concept. Probeer het opnieuw.";
}

/** Maakt het AI-concept voor een plan met status "genereren". Draait na de response (via after()). */
export async function generatePlan(planId: number) {
  const [plan] = await db.select().from(plans).where(eq(plans.id, planId));
  if (!plan || plan.status !== "genereren") return;

  try {
    const [intakeRow] = await db.select().from(intakes).where(eq(intakes.userId, plan.userId));
    const intake = intakeSchema.safeParse(intakeRow?.data);
    if (!intake.success) throw new PlanGenerationError("De klant heeft nog geen (geldige) intake ingevuld.");

    let content: PlanContent;
    if (mockMode()) {
      await new Promise((r) => setTimeout(r, 1200));
      content = plan.type === "training" ? mockTrainingPlan(intake.data) : mockNutritionPlan(intake.data);
    } else {
      content = await callClaude(plan.type, buildPlanPrompt(plan.type, intake.data, plan.instruction));
    }

    // Alleen opslaan als het plan intussen niet vervangen is.
    await db
      .update(plans)
      .set({ status: "concept", content, aiDraft: content, error: null, model: mockMode() ? "testmodus" : AI_MODEL, updatedAt: new Date() })
      .where(and(eq(plans.id, planId), eq(plans.status, "genereren")));
  } catch (error) {
    console.error(`Genereren van plan ${planId} mislukt`, error);
    await db
      .update(plans)
      .set({ status: "fout", error: describeError(error), updatedAt: new Date() })
      .where(and(eq(plans.id, planId), eq(plans.status, "genereren")));
  }
}

/**
 * Zet een nieuw concept klaar (status "genereren") en vervangt openstaande concepten
 * van hetzelfde type. Het gepubliceerde schema blijft zichtbaar tot er een nieuw is gepubliceerd.
 */
export async function createPlanJob(userId: string, type: PlanType, instruction?: string | null) {
  return db.transaction(async (tx) => {
    await tx
      .update(plans)
      .set({ status: "vervangen", updatedAt: new Date() })
      .where(and(eq(plans.userId, userId), eq(plans.type, type), inArray(plans.status, ["genereren", "concept", "fout"])));
    const [row] = await tx
      .insert(plans)
      .values({ userId, type, status: "genereren", source: "ai", instruction: instruction?.trim() || null })
      .returning({ id: plans.id });
    return row.id;
  });
}

/** Automatisch genereren na de intake: alleen voor (aspirant-)coachingklanten en met een daglimiet. */
export async function mayAutoGenerate(user: Pick<User, "id" | "coachingStatus">) {
  if (!aiConfigured()) return false;
  if (user.coachingStatus !== "aangevraagd" && user.coachingStatus !== "actief") return false;
  const since = new Date(Date.now() - 24 * 60 * 60 * 1000);
  const [{ n }] = await db
    .select({ n: count() })
    .from(plans)
    .where(and(eq(plans.userId, user.id), eq(plans.source, "ai"), gt(plans.createdAt, since)));
  return n < MAX_AUTO_PER_DAY;
}
