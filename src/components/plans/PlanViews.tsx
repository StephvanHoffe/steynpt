import { Ban, Clock, Dumbbell, Lightbulb, TrendingUp, Utensils } from "lucide-react";
import type { NutritionPlan, TrainingPlan } from "@/lib/plans/schema";

// Weergave van een schema zoals de klant het ziet. Ook gebruikt als voorbeeld in de editor.

export function TrainingPlanView({ plan }: { plan: TrainingPlan }) {
  return (
    <article className="space-y-6">
      <header>
        <h2 className="display text-3xl sm:text-4xl">{plan.title}</h2>
        <p className="mt-2 flex flex-wrap gap-2 text-sm">
          <span className="rounded-full bg-accent-tint px-3 py-1 font-semibold">{plan.days.length}× per week</span>
          <span className="rounded-full bg-accent-tint px-3 py-1 font-semibold">{plan.durationWeeks} weken</span>
        </p>
        {plan.summary && <p className="mt-4 whitespace-pre-line leading-relaxed text-ink/85">{plan.summary}</p>}
      </header>

      {plan.days.map((day, i) => (
        <section key={i} className="card break-inside-avoid overflow-hidden">
          <div className="border-b border-line bg-surface px-5 py-4">
            <h3 className="flex items-center gap-2 text-lg font-semibold">
              <Dumbbell className="size-5 text-accent" aria-hidden="true" /> {day.name}
            </h3>
            {day.focus && <p className="mt-0.5 text-sm text-muted">{day.focus}</p>}
          </div>
          <div className="space-y-4 p-5">
            {day.warmup && (
              <p className="text-sm">
                <strong>Warming-up:</strong> {day.warmup}
              </p>
            )}
            <div className="relative overflow-x-auto">
              <table className="w-full min-w-[520px] text-left text-sm">
                <thead className="text-xs uppercase tracking-wider text-muted">
                  <tr className="border-b border-line">
                    <th className="py-2 pr-3 font-semibold">Oefening</th>
                    <th className="py-2 pr-3 font-semibold">Sets</th>
                    <th className="py-2 pr-3 font-semibold">Herhalingen</th>
                    <th className="py-2 pr-3 font-semibold">Rust</th>
                    <th className="py-2 font-semibold">Toelichting</th>
                  </tr>
                </thead>
                <tbody>
                  {day.exercises.map((ex, j) => (
                    <tr key={j} className="border-b border-line/70 align-top last:border-0">
                      <td className="py-2.5 pr-3 font-semibold">{ex.name}</td>
                      <td className="py-2.5 pr-3">{ex.sets}</td>
                      <td className="py-2.5 pr-3">{ex.reps}</td>
                      <td className="py-2.5 pr-3 whitespace-nowrap">{ex.rest}</td>
                      <td className="py-2.5 text-muted">{ex.notes}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {day.cooldown && (
              <p className="text-sm">
                <strong>Cooling-down:</strong> {day.cooldown}
              </p>
            )}
          </div>
        </section>
      ))}

      {plan.progression && (
        <section className="card break-inside-avoid p-5">
          <h3 className="flex items-center gap-2 font-semibold">
            <TrendingUp className="size-5 text-accent" aria-hidden="true" /> Progressie
          </h3>
          <p className="mt-2 whitespace-pre-line text-sm leading-relaxed">{plan.progression}</p>
        </section>
      )}
      <TipList tips={plan.tips} />
    </article>
  );
}

export function NutritionPlanView({ plan }: { plan: NutritionPlan }) {
  const targets = [
    { label: "Energie", value: `${Math.round(plan.targets.calories)} kcal` },
    { label: "Eiwit", value: `${Math.round(plan.targets.protein)} g` },
    { label: "Koolhydraten", value: `${Math.round(plan.targets.carbs)} g` },
    { label: "Vet", value: `${Math.round(plan.targets.fat)} g` },
    { label: "Water", value: plan.targets.water },
  ];
  return (
    <article className="space-y-6">
      <header>
        <h2 className="display text-3xl sm:text-4xl">{plan.title}</h2>
        {plan.summary && <p className="mt-4 whitespace-pre-line leading-relaxed text-ink/85">{plan.summary}</p>}
      </header>

      <dl className="grid grid-cols-2 gap-3 sm:grid-cols-5">
        {targets.map((t) => (
          <div key={t.label} className="rounded-xl bg-accent-tint p-4">
            <dt className="text-xs text-muted">{t.label} per dag</dt>
            <dd className="mt-1 font-semibold">{t.value}</dd>
          </div>
        ))}
      </dl>

      {plan.avoid.length > 0 && (
        <section className="flex gap-3 rounded-xl border border-accent/30 bg-white p-5 break-inside-avoid">
          <Ban className="size-5 shrink-0 text-accent" aria-hidden="true" />
          <div>
            <h3 className="font-semibold">Vermijden</h3>
            <ul className="mt-1 list-disc pl-5 text-sm">
              {plan.avoid.map((a, i) => (
                <li key={i}>{a}</li>
              ))}
            </ul>
          </div>
        </section>
      )}

      {plan.meals.map((meal, i) => (
        <section key={i} className="card break-inside-avoid overflow-hidden">
          <div className="flex items-center justify-between gap-3 border-b border-line bg-surface px-5 py-4">
            <h3 className="flex items-center gap-2 text-lg font-semibold">
              <Utensils className="size-5 text-accent" aria-hidden="true" /> {meal.name}
            </h3>
            {meal.time && (
              <span className="flex items-center gap-1.5 text-sm text-muted">
                <Clock className="size-4" aria-hidden="true" /> {meal.time}
              </span>
            )}
          </div>
          <ul className="divide-y divide-line">
            {meal.options.map((option, j) => (
              <li key={j} className="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                <div>
                  <p className="font-semibold">
                    {meal.options.length > 1 && <span className="mr-2 text-xs font-bold uppercase tracking-wider text-accent">Optie {j + 1}</span>}
                    {option.title}
                  </p>
                  <p className="mt-1 text-sm text-muted">{option.ingredients}</p>
                </div>
                <p className="shrink-0 text-sm font-semibold">
                  ± {Math.round(option.kcal)} kcal · {Math.round(option.protein)} g eiwit
                </p>
              </li>
            ))}
          </ul>
        </section>
      ))}
      <TipList tips={plan.tips} />
    </article>
  );
}

function TipList({ tips }: { tips: string[] }) {
  if (!tips.length) return null;
  return (
    <section className="card break-inside-avoid p-5">
      <h3 className="flex items-center gap-2 font-semibold">
        <Lightbulb className="size-5 text-accent" aria-hidden="true" /> Tips
      </h3>
      <ul className="mt-2 list-disc space-y-1 pl-5 text-sm">
        {tips.map((tip, i) => (
          <li key={i}>{tip}</li>
        ))}
      </ul>
    </section>
  );
}
