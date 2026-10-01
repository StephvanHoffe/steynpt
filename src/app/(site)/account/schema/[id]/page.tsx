import { and, eq } from "drizzle-orm";
import { ArrowLeft } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { PrintButton } from "@/components/plans/PrintButton";
import { NutritionPlanView, TrainingPlanView } from "@/components/plans/PlanViews";
import { requireUser } from "@/lib/auth";
import { db, plans } from "@/lib/db";
import { nutritionPlanSchema, trainingPlanSchema } from "@/lib/plans/schema";

export const metadata: Metadata = { title: "Mijn schema" };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", year: "numeric" });

export default async function PlanPage({ params }: PageProps<"/account/schema/[id]">) {
  const { id } = await params;
  const user = await requireUser(`/account/schema/${id}`);
  const planId = Number(id);
  if (!Number.isInteger(planId)) notFound();

  // Klanten zien alleen hun eigen, door Steyn gepubliceerde schema's.
  const [plan] = await db
    .select()
    .from(plans)
    .where(and(eq(plans.id, planId), eq(plans.userId, user.id), eq(plans.status, "gepubliceerd")));
  if (!plan) notFound();

  const training = plan.type === "training" ? trainingPlanSchema.safeParse(plan.content) : null;
  const nutrition = plan.type === "voeding" ? nutritionPlanSchema.safeParse(plan.content) : null;
  if (!training?.success && !nutrition?.success) notFound();

  return (
    <div className="container-site max-w-4xl py-10 lg:py-14">
      <div className="flex flex-wrap items-center justify-between gap-3 print:hidden">
        <Link href="/account" className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
          <ArrowLeft className="size-4" aria-hidden="true" /> Terug naar mijn omgeving
        </Link>
        <PrintButton />
      </div>
      <p className="mt-6 text-sm text-muted">
        {plan.type === "training" ? "Trainingsschema" : "Voedingsschema"} · gecontroleerd door Steyn
        {plan.publishedAt ? ` · ${dateFmt.format(plan.publishedAt)}` : ""}
      </p>
      <div className="mt-3">
        {training?.success && <TrainingPlanView plan={training.data} />}
        {nutrition?.success && <NutritionPlanView plan={nutrition.data} />}
      </div>
      <p className="mt-10 rounded-xl bg-accent-tint p-5 text-sm print:hidden">
        Vragen over je schema of loopt iets niet lekker? Laat het weten in je wekelijkse check-in, dan stuurt Steyn bij.
      </p>
    </div>
  );
}
