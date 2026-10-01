import { eq } from "drizzle-orm";
import { AlertTriangle, ArrowLeft, LoaderCircle } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { AutoRefresh } from "@/components/plans/AutoRefresh";
import { GenerateForms } from "@/components/plans/GenerateForms";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { PlanEditor } from "@/components/plans/PlanEditor";
import { NutritionPlanView, TrainingPlanView } from "@/components/plans/PlanViews";
import { requireAdmin } from "@/lib/auth";
import { db, intakes, plans, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { planSchemaFor, type PlanContent } from "@/lib/plans/schema";

export const metadata: Metadata = { title: "Schema controleren", robots: { index: false } };
export const maxDuration = 300;

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" });

export default async function PlanReviewPage({ params }: PageProps<"/admin/schemas/[id]">) {
  await requireAdmin();
  const { id } = await params;
  const planId = Number(id);
  if (!Number.isInteger(planId)) notFound();

  const [plan] = await db.select().from(plans).where(eq(plans.id, planId));
  if (!plan) notFound();
  const [[member], [intakeRow]] = await Promise.all([
    db.select().from(users).where(eq(users.id, plan.userId)),
    db.select().from(intakes).where(eq(intakes.userId, plan.userId)),
  ]);
  if (!member) notFound();

  const intake = intakeSchema.safeParse(intakeRow?.data);
  const parsed = plan.content ? planSchemaFor(plan.type).safeParse(plan.content) : null;
  const content = parsed?.success ? (parsed.data as PlanContent) : null;
  const stuck = isStuck(plan.status, plan.updatedAt);
  const status = stuck ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[plan.status];
  const intakeChanged = intakeRow && plan.status !== "vervangen" && intakeRow.updatedAt > plan.createdAt;
  const edited = plan.source === "ai" && plan.aiDraft != null && JSON.stringify(plan.aiDraft) !== JSON.stringify(plan.content);
  const editable = (plan.status === "concept" || plan.status === "gepubliceerd") && content;

  return (
    <div className="container-site py-10 lg:py-14">
      {plan.status === "genereren" && !stuck && <AutoRefresh />}
      <Link href={`/admin/leden/${member.id}`} className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> {member.firstName} {member.lastName}
      </Link>
      <div className="mt-3 flex flex-wrap items-center gap-3">
        <h1 className="display display-md">{PLAN_TYPE_LABEL[plan.type]}</h1>
        <span className={`rounded-full px-3 py-1 text-xs font-semibold ${status.tone}`}>{status.label}</span>
        {edited && <span className="rounded-full bg-surface px-3 py-1 text-xs font-semibold">Aangepast door Steyn</span>}
      </div>
      <p className="mt-2 text-sm text-muted">
        Versie #{plan.id} · {plan.source === "ai" ? `AI-concept (${plan.model ?? "AI"})` : "handmatig"} · gemaakt {dateFmt.format(plan.createdAt)}
        {plan.publishedAt ? ` · gepubliceerd ${dateFmt.format(plan.publishedAt)}` : ""}
        {plan.instruction ? ` · instructie: "${plan.instruction}"` : ""}
      </p>

      <div className="mt-8 grid gap-8 xl:grid-cols-[1fr_24rem]">
        <div className="grid content-start gap-6">
          {intakeChanged && (
            <p className="flex gap-2 rounded-xl border border-accent/30 bg-accent-tint p-4 text-sm">
              <AlertTriangle className="size-5 shrink-0 text-accent" aria-hidden="true" />
              De klant heeft de intake gewijzigd nadat dit concept is gemaakt. Controleer extra goed of laat een nieuw concept maken.
            </p>
          )}

          {plan.status === "genereren" && !stuck && (
            <div className="card flex items-center gap-4 p-8">
              <LoaderCircle className="size-8 animate-spin text-accent" aria-hidden="true" />
              <div>
                <p className="font-semibold">De AI maakt het concept…</p>
                <p className="text-sm text-muted">Dit duurt meestal één à twee minuten. De pagina ververst vanzelf.</p>
              </div>
            </div>
          )}

          {(plan.status === "fout" || stuck) && (
            <div className="card p-6">
              <p className="flex items-center gap-2 font-semibold text-danger">
                <AlertTriangle className="size-5" aria-hidden="true" /> Het concept kon niet gemaakt worden
              </p>
              <p className="mt-1 text-sm text-muted">{stuck ? "De generatie is niet afgerond (mogelijk door een herstart van de server)." : plan.error}</p>
              <div className="mt-5">
                <GenerateForms userId={member.id} type={plan.type} aiEnabled={aiConfigured()} hasIntake={intake.success} />
              </div>
            </div>
          )}

          {plan.status === "vervangen" && content && (
            <>
              <p className="rounded-xl bg-surface p-4 text-sm">
                Dit is een oudere versie en is niet meer bewerkbaar.{" "}
                <Link href={`/admin/leden/${member.id}`} className="font-semibold underline">
                  Bekijk de actuele versies
                </Link>
                .
              </p>
              <div className="card p-6">{"days" in content ? <TrainingPlanView plan={content} /> : <NutritionPlanView plan={content} />}</div>
            </>
          )}

          {editable && (
            <>
              <PlanEditor
                planId={plan.id}
                initial={content}
                published={plan.status === "gepubliceerd"}
                allergyContext={intake.success ? { allergies: intake.data.allergies, diet: intake.data.diet } : null}
              />
              <details className="card p-6">
                <summary className="cursor-pointer font-semibold">Nieuw concept laten maken</summary>
                <div className="mt-4">
                  <GenerateForms userId={member.id} type={plan.type} aiEnabled={aiConfigured()} hasIntake={intake.success} regenerate />
                </div>
              </details>
            </>
          )}
        </div>

        <aside className="card self-start p-6 xl:sticky xl:top-28">
          <h2 className="display text-2xl">Intake</h2>
          <div className="mt-4">
            {intake.success ? <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} /> : <p className="text-sm text-muted">Geen intake ingevuld.</p>}
          </div>
        </aside>
      </div>
    </div>
  );
}
