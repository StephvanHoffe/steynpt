import { ClipboardList, Dumbbell, Hourglass, Utensils } from "lucide-react";
import Link from "next/link";
import type { CoachingStatus, Plan, PlanType } from "@/lib/db";
import type { IntakeData } from "@/lib/intake";
import { POINTS } from "@/lib/loyalty";

const TYPES: { type: PlanType; label: string; icon: typeof Dumbbell }[] = [
  { type: "training", label: "Trainingsschema", icon: Dumbbell },
  { type: "voeding", label: "Voedingsschema", icon: Utensils },
];

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "short" });

export function MyPlansCard({
  intake,
  plans,
  coachingStatus,
}: {
  intake: IntakeData | null;
  plans: Pick<Plan, "id" | "type" | "status" | "publishedAt">[];
  coachingStatus: CoachingStatus;
}) {
  return (
    <section className="card p-6 sm:p-8" aria-labelledby="plans-title">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 id="plans-title" className="display flex items-center gap-2 text-3xl">
          <ClipboardList className="size-7" aria-hidden="true" /> Mijn schema&apos;s
        </h2>
        {intake && (
          <Link href="/account/intake" className="text-sm font-semibold underline decoration-rose underline-offset-4">
            Intake bijwerken
          </Link>
        )}
      </div>

      {!intake ? (
        <div className="mt-5 rounded-xl bg-blush p-5">
          <p className="font-semibold">Vul je intake in voor je persoonlijke schema</p>
          <p className="mt-1 text-sm text-ink/80">
            Vertel over je doel, hoe vaak je traint en wat je wel en niet eet (zoals allergieën). Daarmee maken we je trainings-
            en voedingsschema op maat, gecontroleerd door Steyn. Goed voor {POINTS.intake} punten.
          </p>
          <Link href="/account/intake" className="btn btn-primary btn-sm mt-4">
            Intake invullen
          </Link>
        </div>
      ) : (
        <ul className="mt-5 grid gap-3 sm:grid-cols-2">
          {TYPES.map(({ type, label, icon: Icon }) => {
            const published = plans.find((p) => p.type === type && p.status === "gepubliceerd");
            const pending = plans.some((p) => p.type === type && ["genereren", "concept", "fout"].includes(p.status));
            const wanted = intake.wants.includes(type);
            let status: string;
            if (published) status = pending ? "Er komt binnenkort een vernieuwde versie." : `Klaar sinds ${published.publishedAt ? dateFmt.format(published.publishedAt) : "kort"}.`;
            else if (pending) status = "Wordt gemaakt en daarna door Steyn gecontroleerd.";
            else if (!wanted) status = "Niet aangevraagd in je intake.";
            else if (coachingStatus === "geen" || coachingStatus === "gestopt") status = "Start online coaching, dan maken we je schema.";
            else status = "Steyn maakt je schema binnenkort.";

            return (
              <li key={type} className={`flex flex-col rounded-xl border p-4 ${published ? "border-rose bg-blush/50" : "border-line"}`}>
                <p className="flex items-center gap-2 font-semibold">
                  <Icon className="size-4 text-rose" aria-hidden="true" /> {label}
                </p>
                <p className="mt-1 flex-1 text-sm text-muted">
                  {!published && pending && <Hourglass className="mr-1 inline size-3.5" aria-hidden="true" />}
                  {status}
                </p>
                {published && (
                  <Link href={`/account/schema/${published.id}`} className="btn btn-primary btn-sm mt-3 self-start">
                    Bekijk schema
                  </Link>
                )}
                {!published && !wanted && (
                  <Link href="/account/intake" className="mt-3 text-sm font-semibold underline decoration-rose underline-offset-4">
                    Toevoegen
                  </Link>
                )}
              </li>
            );
          })}
        </ul>
      )}
    </section>
  );
}
