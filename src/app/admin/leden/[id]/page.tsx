import { desc, eq } from "drizzle-orm";
import { ArrowLeft, Info } from "lucide-react";
import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { GenerateForms } from "@/components/plans/GenerateForms";
import { IntakePanel } from "@/components/plans/IntakePanel";
import { PLAN_STATUS, PLAN_TYPE_LABEL, isStuck } from "@/components/plans/labels";
import { requireAdmin } from "@/lib/auth";
import { db, intakes, PLAN_TYPES, plans, users } from "@/lib/db";
import { intakeSchema } from "@/lib/intake";
import { aiConfigured } from "@/lib/plans/generate";
import { getOnlinePlan } from "@/lib/site";

export const metadata: Metadata = { title: "Lid", robots: { index: false } };
export const maxDuration = 300;

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short", hour: "2-digit", minute: "2-digit" });

export default async function MemberPage({ params }: PageProps<"/admin/leden/[id]">) {
  await requireAdmin();
  const { id } = await params;
  const [member] = await db.select().from(users).where(eq(users.id, id));
  if (!member) notFound();

  const [[intakeRow], memberPlans] = await Promise.all([
    db.select().from(intakes).where(eq(intakes.userId, id)),
    db.select().from(plans).where(eq(plans.userId, id)).orderBy(desc(plans.createdAt)),
  ]);
  const intake = intakeSchema.safeParse(intakeRow?.data);
  const aiEnabled = aiConfigured();

  return (
    <div className="container-site py-10 lg:py-14">
      <Link href="/admin" className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-ink">
        <ArrowLeft className="size-4" aria-hidden="true" /> Beheer
      </Link>
      <h1 className="display display-lg mt-3">
        {member.firstName} {member.lastName}
      </h1>
      <p className="mt-2 text-muted">
        {member.email}
        {member.phone ? ` · ${member.phone}` : ""} · coaching: <strong className="text-ink">{member.coachingStatus}</strong>
        {member.plan ? ` (${getOnlinePlan(member.plan)?.name})` : ""}
      </p>

      {!aiEnabled && (
        <p className="mt-6 flex gap-2 rounded-xl bg-sand p-4 text-sm">
          <Info className="size-5 shrink-0" aria-hidden="true" />
          AI staat uit: stel ANTHROPIC_API_KEY in om concepten automatisch te laten maken. Je kunt schema&apos;s wel zelf opstellen.
        </p>
      )}

      <div className="mt-10 grid gap-8 lg:grid-cols-[1.2fr_1fr]">
        <div className="grid content-start gap-6">
          {PLAN_TYPES.map((type) => {
            const list = memberPlans.filter((p) => p.type === type);
            const hasOpen = list.some((p) => p.status === "genereren" || p.status === "concept");
            return (
              <section key={type} className="card p-6" aria-labelledby={`type-${type}`}>
                <h2 id={`type-${type}`} className="display text-2xl">
                  {PLAN_TYPE_LABEL[type]}
                </h2>
                {list.length === 0 ? (
                  <p className="mt-3 text-sm text-muted">Nog geen schema.</p>
                ) : (
                  <ul className="mt-4 divide-y divide-line">
                    {list.map((p) => {
                      const status = isStuck(p.status, p.updatedAt) ? { label: "Vastgelopen", tone: "bg-danger/10 text-danger" } : PLAN_STATUS[p.status];
                      return (
                        <li key={p.id} className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                          <span>
                            <Link href={`/admin/schemas/${p.id}`} className="font-semibold underline decoration-rose underline-offset-4">
                              Versie #{p.id}
                            </Link>
                            <span className="ml-2 text-muted">
                              {dateFmt.format(p.createdAt)} · {p.source === "ai" ? "AI-concept" : "handmatig"}
                            </span>
                          </span>
                          <span className={`rounded-full px-3 py-1 text-xs font-semibold ${status.tone}`}>{status.label}</span>
                        </li>
                      );
                    })}
                  </ul>
                )}
                {!hasOpen && (
                  <div className="mt-5 border-t border-line pt-5">
                    <GenerateForms userId={member.id} type={type} aiEnabled={aiEnabled} hasIntake={intake.success} />
                  </div>
                )}
              </section>
            );
          })}
        </div>
        <aside className="card self-start p-6">
          <h2 className="display text-2xl">Intake</h2>
          <div className="mt-4">
            {intake.success ? (
              <IntakePanel intake={intake.data} updatedAt={intakeRow?.updatedAt} />
            ) : (
              <p className="text-sm text-muted">Dit lid heeft nog geen intake ingevuld.</p>
            )}
          </div>
        </aside>
      </div>
    </div>
  );
}
