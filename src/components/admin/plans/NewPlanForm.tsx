"use client";

import { CopyPlus, FilePlus2, Sparkles } from "lucide-react";
import { useState } from "react";
import { SubmitButton } from "@/components/forms/fields";
import { createPlanAction } from "@/lib/actions/plans";
import { addDays } from "@/lib/agenda";
import type { PlanType } from "@/lib/db";

type Method = "ai" | "huidig" | "leeg";

const dayFmt = new Intl.DateTimeFormat("nl-NL", { weekday: "long", day: "numeric", month: "long", timeZone: "UTC" });
const formatDay = (day: string) => dayFmt.format(new Date(`${day}T12:00:00Z`));

/** Eén formulier voor een nieuw schema: startdatum en hoe je wilt beginnen. */
export function NewPlanForm({
  userId,
  type,
  today,
  defaultStart,
  currentEndsOn,
  ai,
  hasCurrent,
}: {
  userId: string;
  type: PlanType;
  today: string;
  defaultStart: string;
  /** Datum waarop het huidige schema afloopt (als die in de toekomst ligt). */
  currentEndsOn: string | null;
  /** AI beschikbaar, of de reden waarom niet. */
  ai: { available: true } | { available: false; reason: string };
  hasCurrent: boolean;
}) {
  const [start, setStart] = useState(defaultStart);
  const [method, setMethod] = useState<Method>(ai.available ? "ai" : hasCurrent ? "huidig" : "leeg");
  const later = start > today;

  const options: { id: Method; icon: typeof Sparkles; title: string; text: string; disabled?: string }[] = [
    {
      id: "ai",
      icon: Sparkles,
      title: "Laat de AI een concept maken",
      text: "Op basis van de intake, eventueel met een instructie.",
      disabled: ai.available ? undefined : ai.reason,
    },
    ...(hasCurrent
      ? [{ id: "huidig" as const, icon: CopyPlus, title: "Verder met het huidige schema", text: "Een kopie als concept: pas aan wat er verandert." }]
      : []),
    {
      id: "leeg",
      icon: FilePlus2,
      title: "Zelf een leeg schema opstellen",
      text: type === "training" ? "Met het aantal trainingsdagen uit de intake al klaargezet." : "Met de richtwaarden uit de intake al ingevuld.",
    },
  ];
  const submitLabel = { ai: "Concept laten maken", huidig: "Kopie als concept", leeg: "Leeg schema starten" }[method];
  const quick = [
    { day: today, label: "Vandaag" },
    ...(currentEndsOn && currentEndsOn > today ? [{ day: currentEndsOn, label: "Als het huidige schema afloopt" }] : []),
    { day: addDays(today, 7), label: "Over een week" },
  ];

  return (
    <form action={createPlanAction} className="card grid gap-7 p-5 sm:p-6">
      <input type="hidden" name="userId" value={userId} />
      <input type="hidden" name="type" value={type} />

      <fieldset>
        <legend className="text-lg font-semibold">1. Wanneer start het schema?</legend>
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <label className="sr-only" htmlFor="startsOn">
            Startdatum
          </label>
          <input
            id="startsOn"
            type="date"
            name="startsOn"
            required
            min={today}
            max={addDays(today, 366)}
            value={start}
            onChange={(e) => e.target.value && setStart(e.target.value)}
            className="input h-10 w-auto min-h-0 py-1"
          />
          {quick.map((q) => (
            <button
              key={q.label}
              type="button"
              onClick={() => setStart(q.day)}
              aria-pressed={start === q.day}
              className={`rounded-full border px-3 py-1.5 text-sm font-medium ${start === q.day ? "border-ink bg-ink text-white" : "border-line bg-white hover:border-ink"}`}
            >
              {q.label}
            </button>
          ))}
        </div>
        <p className="mt-2 text-sm text-muted" aria-live="polite">
          {later ? (
            <>
              De klant ziet het schema vanaf <strong className="font-semibold text-ink">{formatDay(start)}</strong> in Mijn omgeving.
              {hasCurrent && " Tot die dag blijft het huidige schema zichtbaar."}
            </>
          ) : (
            "Het schema staat voor de klant klaar zodra je het hebt gecontroleerd en gepubliceerd."
          )}
        </p>
      </fieldset>

      <fieldset>
        <legend className="text-lg font-semibold">2. Hoe wil je beginnen?</legend>
        <div className="mt-3 grid gap-2">
          {options.map((o) => (
            <label
              key={o.id}
              className={`flex gap-3 rounded-xl border p-4 ${o.disabled ? "cursor-not-allowed border-line bg-surface/60" : method === o.id ? "cursor-pointer border-ink bg-white ring-1 ring-ink" : "cursor-pointer border-line bg-white hover:border-ink/40"}`}
            >
              <input type="radio" name="method" value={o.id} checked={method === o.id} disabled={!!o.disabled} onChange={() => setMethod(o.id)} className="mt-1 accent-ink" />
              <span className="min-w-0">
                <span className="flex items-center gap-2 font-semibold">
                  <o.icon className="size-4 text-accent" aria-hidden="true" /> {o.title}
                </span>
                <span className="mt-0.5 block text-sm text-muted">{o.disabled ?? o.text}</span>
              </span>
            </label>
          ))}
        </div>
        {method === "ai" && (
          <label className="mt-4 block">
            <span className="label">Instructie voor de AI (optioneel)</span>
            <textarea
              name="instruction"
              rows={3}
              maxLength={1500}
              className="input min-h-0 py-2 text-sm"
              placeholder={type === "training" ? "Bijv. 'volgende fase: meer kracht, 4 dagen, geen squats vanwege de knie'" : "Bijv. 'calorieën 100 kcal omlaag, meer warme lunches'"}
            />
          </label>
        )}
      </fieldset>

      <div className="flex flex-wrap items-center gap-3 border-t border-line pt-5">
        <SubmitButton className="btn btn-primary" pendingText="Bezig…">
          {submitLabel}
        </SubmitButton>
        <p className="text-sm text-muted">Je controleert het concept altijd eerst; de klant ziet niets tot je het goedkeurt.</p>
      </div>
    </form>
  );
}
