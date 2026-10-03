"use client";

import { AlertTriangle, Eye, Pencil, Plus, Trash2 } from "lucide-react";
import { useActionState, useEffect, useMemo, useRef, useState, type ReactNode } from "react";
import { savePlanAction } from "@/lib/actions/plans";
import type { FormState } from "@/lib/actions/types";
import { addDays } from "@/lib/agenda";
import type { IntakeData } from "@/lib/intake";
import { findAllergenWarnings } from "@/lib/plans/allergens";
import { daysBetween, defaultRenewOn, relativeDay } from "@/lib/plans/pipeline";
import {
  emptyExercise,
  emptyMeal,
  emptyMealOption,
  emptyTrainingDay,
  type NutritionPlan,
  type PlanContent,
  type TrainingPlan,
} from "@/lib/plans/schema";
import { FormAlert } from "../forms/fields";
import { NutritionPlanView, TrainingPlanView } from "./PlanViews";

// Bewerkscherm voor Steyn. De inhoud wordt als JSON naar savePlanAction gestuurd en
// daar opnieuw gevalideerd met hetzelfde schema als de AI-output.

const replaceAt = <T,>(list: T[], index: number, value: T) => list.map((item, i) => (i === index ? value : item));
const removeAt = <T,>(list: T[], index: number) => list.filter((_, i) => i !== index);
const cleanLines = (lines: string[]) => lines.map((l) => l.trim()).filter(Boolean);

function normalize(plan: PlanContent): PlanContent {
  return "days" in plan
    ? { ...plan, tips: cleanLines(plan.tips), daysPerWeek: plan.days.length }
    : { ...plan, tips: cleanLines(plan.tips), avoid: cleanLines(plan.avoid) };
}

function TextInput({ label, value, onChange, className = "", hideLabel }: { label: string; value: string; onChange: (v: string) => void; className?: string; hideLabel?: boolean }) {
  return (
    <label className={`block ${className}`}>
      <span className={hideLabel ? "sr-only" : "label"}>{label}</span>
      <input className="input min-h-10 py-2 text-sm" value={value} onChange={(e) => onChange(e.target.value)} />
    </label>
  );
}

function NumberInput({ label, value, onChange, suffix }: { label: string; value: number; onChange: (v: number) => void; suffix?: string }) {
  return (
    <label className="block">
      <span className="label">
        {label}
        {suffix && <span className="font-normal text-muted"> ({suffix})</span>}
      </span>
      <input
        type="number"
        inputMode="decimal"
        className="input min-h-10 py-2 text-sm"
        value={Number.isFinite(value) ? value : 0}
        onChange={(e) => onChange(e.target.value === "" ? 0 : Number(e.target.value))}
      />
    </label>
  );
}

function Area({ label, value, onChange, rows = 3, hint }: { label: string; value: string; onChange: (v: string) => void; rows?: number; hint?: string }) {
  return (
    <label className="block">
      <span className="label">{label}</span>
      <textarea className="input min-h-0 py-2 text-sm" rows={rows} value={value} onChange={(e) => onChange(e.target.value)} />
      {hint && <span className="mt-1 block text-xs text-muted">{hint}</span>}
    </label>
  );
}

function Lines({ label, value, onChange }: { label: string; value: string[]; onChange: (v: string[]) => void }) {
  return <Area label={label} value={value.join("\n")} onChange={(v) => onChange(v.split("\n"))} rows={Math.max(3, value.length + 1)} hint="Eén per regel." />;
}

function AddButton({ onClick, children }: { onClick: () => void; children: ReactNode }) {
  return (
    <button type="button" onClick={onClick} className="btn btn-sm btn-outline bg-white">
      <Plus className="size-4" aria-hidden="true" /> {children}
    </button>
  );
}

function RemoveButton({ onClick, label }: { onClick: () => void; label: string }) {
  return (
    <button type="button" onClick={onClick} aria-label={label} title={label} className="grid size-10 shrink-0 place-items-center rounded-lg text-muted transition-colors hover:bg-accent-tint hover:text-accent">
      <Trash2 className="size-4" aria-hidden="true" />
    </button>
  );
}

function TrainingFields({ plan, set }: { plan: TrainingPlan; set: (p: TrainingPlan) => void }) {
  const updateDay = (i: number, patch: Partial<TrainingPlan["days"][number]>) => set({ ...plan, days: replaceAt(plan.days, i, { ...plan.days[i], ...patch }) });
  return (
    <div className="grid gap-6">
      <div className="grid gap-4 sm:grid-cols-[1fr_10rem]">
        <TextInput label="Titel" value={plan.title} onChange={(title) => set({ ...plan, title })} />
        <NumberInput label="Duur" suffix="weken" value={plan.durationWeeks} onChange={(durationWeeks) => set({ ...plan, durationWeeks })} />
      </div>
      <Area label="Toelichting voor de klant" value={plan.summary} onChange={(summary) => set({ ...plan, summary })} rows={4} />

      {plan.days.map((day, i) => (
        <fieldset key={i} className="rounded-xl border border-line bg-white p-4 sm:p-5">
          <legend className="px-1 text-sm font-semibold text-accent">Trainingsdag {i + 1}</legend>
          <div className="grid gap-4">
            <div className="flex items-end gap-2">
              <div className="grid flex-1 gap-4 sm:grid-cols-2">
                <TextInput label="Naam" value={day.name} onChange={(name) => updateDay(i, { name })} />
                <TextInput label="Focus" value={day.focus} onChange={(focus) => updateDay(i, { focus })} />
              </div>
              <RemoveButton label={`Dag ${i + 1} verwijderen`} onClick={() => set({ ...plan, days: removeAt(plan.days, i) })} />
            </div>
            <Area label="Warming-up" value={day.warmup} onChange={(warmup) => updateDay(i, { warmup })} rows={2} />

            <div className="grid gap-2">
              <p className="label mb-0">Oefeningen</p>
              {day.exercises.map((ex, j) => {
                const updateEx = (patch: Partial<typeof ex>) => updateDay(i, { exercises: replaceAt(day.exercises, j, { ...ex, ...patch }) });
                return (
                  <div key={j} className="grid gap-2 rounded-lg bg-paper p-3 sm:grid-cols-[2fr_4rem_6rem_6rem_2.5rem]">
                    <TextInput hideLabel label={`Oefening ${j + 1}`} value={ex.name} onChange={(name) => updateEx({ name })} />
                    <TextInput hideLabel label="Sets" value={ex.sets} onChange={(sets) => updateEx({ sets })} />
                    <TextInput hideLabel label="Herhalingen" value={ex.reps} onChange={(reps) => updateEx({ reps })} />
                    <TextInput hideLabel label="Rust" value={ex.rest} onChange={(rest) => updateEx({ rest })} />
                    <RemoveButton label={`Oefening ${ex.name || j + 1} verwijderen`} onClick={() => updateDay(i, { exercises: removeAt(day.exercises, j) })} />
                    <TextInput hideLabel label="Toelichting" value={ex.notes} onChange={(notes) => updateEx({ notes })} className="sm:col-span-5" />
                  </div>
                );
              })}
              <p className="text-xs text-muted">Per oefening: naam · sets · herhalingen · rust, met daaronder de toelichting.</p>
              <div>
                <AddButton onClick={() => updateDay(i, { exercises: [...day.exercises, emptyExercise()] })}>Oefening</AddButton>
              </div>
            </div>
            <Area label="Cooling-down" value={day.cooldown} onChange={(cooldown) => updateDay(i, { cooldown })} rows={2} />
          </div>
        </fieldset>
      ))}
      <div>
        <AddButton onClick={() => set({ ...plan, days: [...plan.days, emptyTrainingDay(plan.days.length + 1)] })}>Trainingsdag</AddButton>
      </div>
      <Area label="Progressie" value={plan.progression} onChange={(progression) => set({ ...plan, progression })} rows={3} />
      <Lines label="Tips" value={plan.tips} onChange={(tips) => set({ ...plan, tips })} />
    </div>
  );
}

function NutritionFields({ plan, set }: { plan: NutritionPlan; set: (p: NutritionPlan) => void }) {
  const t = plan.targets;
  const setTargets = (patch: Partial<typeof t>) => set({ ...plan, targets: { ...t, ...patch } });
  const updateMeal = (i: number, patch: Partial<NutritionPlan["meals"][number]>) => set({ ...plan, meals: replaceAt(plan.meals, i, { ...plan.meals[i], ...patch }) });
  const macroKcal = Math.round(t.protein * 4 + t.carbs * 4 + t.fat * 9);

  return (
    <div className="grid gap-6">
      <TextInput label="Titel" value={plan.title} onChange={(title) => set({ ...plan, title })} />
      <Area label="Toelichting voor de klant" value={plan.summary} onChange={(summary) => set({ ...plan, summary })} rows={4} />

      <fieldset className="rounded-xl border border-line bg-white p-4 sm:p-5">
        <legend className="px-1 text-sm font-semibold text-accent">Richtwaarden per dag</legend>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
          <NumberInput label="Energie" suffix="kcal" value={t.calories} onChange={(calories) => setTargets({ calories })} />
          <NumberInput label="Eiwit" suffix="g" value={t.protein} onChange={(protein) => setTargets({ protein })} />
          <NumberInput label="Koolh." suffix="g" value={t.carbs} onChange={(carbs) => setTargets({ carbs })} />
          <NumberInput label="Vet" suffix="g" value={t.fat} onChange={(fat) => setTargets({ fat })} />
          <TextInput label="Water" value={t.water} onChange={(water) => setTargets({ water })} />
        </div>
        <p className={`mt-3 text-xs ${Math.abs(macroKcal - t.calories) > 100 ? "font-semibold text-danger" : "text-muted"}`}>
          Macro&apos;s samen: {macroKcal} kcal{Math.abs(macroKcal - t.calories) > 100 ? " — wijkt af van de energie-richtwaarde" : ""}
        </p>
      </fieldset>

      <Lines label="Vermijden (o.a. allergieën)" value={plan.avoid} onChange={(avoid) => set({ ...plan, avoid })} />

      {plan.meals.map((meal, i) => (
        <fieldset key={i} className="rounded-xl border border-line bg-white p-4 sm:p-5">
          <legend className="px-1 text-sm font-semibold text-accent">Eetmoment {i + 1}</legend>
          <div className="grid gap-4">
            <div className="flex items-end gap-2">
              <div className="grid flex-1 gap-4 sm:grid-cols-2">
                <TextInput label="Naam" value={meal.name} onChange={(name) => updateMeal(i, { name })} />
                <TextInput label="Tijd" value={meal.time} onChange={(time) => updateMeal(i, { time })} />
              </div>
              <RemoveButton label={`${meal.name || `Eetmoment ${i + 1}`} verwijderen`} onClick={() => set({ ...plan, meals: removeAt(plan.meals, i) })} />
            </div>
            {meal.options.map((option, j) => {
              const updateOption = (patch: Partial<typeof option>) => updateMeal(i, { options: replaceAt(meal.options, j, { ...option, ...patch }) });
              return (
                <div key={j} className="grid gap-3 rounded-lg bg-paper p-3">
                  <div className="flex items-end gap-2">
                    <div className="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-[2fr_7rem_7rem]">
                      <TextInput label={`Optie ${j + 1}`} value={option.title} onChange={(title) => updateOption({ title })} className="col-span-2 sm:col-span-1" />
                      <NumberInput label="kcal" value={option.kcal} onChange={(kcal) => updateOption({ kcal })} />
                      <NumberInput label="Eiwit" suffix="g" value={option.protein} onChange={(protein) => updateOption({ protein })} />
                    </div>
                    <RemoveButton label={`Optie ${option.title || j + 1} verwijderen`} onClick={() => updateMeal(i, { options: removeAt(meal.options, j) })} />
                  </div>
                  <Area label="Ingrediënten en hoeveelheden" value={option.ingredients} onChange={(ingredients) => updateOption({ ingredients })} rows={2} />
                </div>
              );
            })}
            <div>
              <AddButton onClick={() => updateMeal(i, { options: [...meal.options, emptyMealOption()] })}>Optie</AddButton>
            </div>
          </div>
        </fieldset>
      ))}
      <div>
        <AddButton onClick={() => set({ ...plan, meals: [...plan.meals, emptyMeal()] })}>Eetmoment</AddButton>
      </div>
      <Lines label="Tips" value={plan.tips} onChange={(tips) => set({ ...plan, tips })} />
    </div>
  );
}

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", timeZone: "UTC" });
const formatDay = (day: string) => dateFmt.format(new Date(`${day}T12:00:00Z`));

export function PlanEditor({
  planId,
  initial,
  status,
  allergyContext,
  today,
  startsOn: initialStart,
  renewOn: initialRenewOn,
  followDuration,
}: {
  planId: number;
  initial: PlanContent;
  status: "concept" | "gepland" | "gepubliceerd";
  allergyContext: Pick<IntakeData, "allergies" | "diet"> | null;
  today: string;
  /** Dag waarop het schema voor de klant ingaat. */
  startsOn: string;
  /** Dag waarop de klant toe is aan een nieuw schema. */
  renewOn: string;
  /** Nog geen vaste datum: volg de startdatum en de duur van het schema tot Steyn zelf een datum kiest. */
  followDuration: boolean;
}) {
  const live = status === "gepubliceerd";
  const [plan, setPlan] = useState<PlanContent>(initial);
  const [tab, setTab] = useState<"bewerken" | "voorbeeld">("bewerken");
  const [state, action] = useActionState<FormState, FormData>(savePlanAction, {});
  const [start, setStart] = useState(initialStart);
  const [pickedRenewOn, setPickedRenewOn] = useState<string | null>(followDuration ? null : initialRenewOn);
  const renewOn = pickedRenewOn ?? defaultRenewOn("days" in plan ? "training" : "voeding", start, "days" in plan ? plan.durationWeeks : null);
  const later = !live && start > today;
  const [saved, setSaved] = useState(() => `${JSON.stringify(normalize(initial))}|${renewOn}|${start}`);
  const submitted = useRef<string>("");

  const json = useMemo(() => JSON.stringify(normalize(plan)), [plan]);
  const current = `${json}|${renewOn}|${start}`;
  const dirty = current !== saved;
  const warnings = useMemo(() => ("meals" in plan && allergyContext ? findAllergenWarnings(plan, allergyContext) : []), [plan, allergyContext]);
  const minRenew = addDays(later ? start : today, 1);
  const weeksAfterStart = Math.round(daysBetween(start, renewOn) / 7);

  // Een andere startdatum verschuift een zelfgekozen "nieuw schema"-datum mee, zodat de looptijd gelijk blijft.
  const changeStart = (day: string) => {
    if (!day) return;
    if (pickedRenewOn) setPickedRenewOn(addDays(pickedRenewOn, daysBetween(start, day)));
    setStart(day);
  };
  const publishLabel = live
    ? "Opnieuw publiceren"
    : later
      ? status === "gepland"
        ? "Opnieuw inplannen"
        : "Goedkeuren & inplannen"
      : status === "gepland"
        ? "Nu publiceren"
        : "Goedkeuren & publiceren";

  useEffect(() => {
    if (state.success) setSaved(submitted.current);
  }, [state]);

  return (
    <form
      action={action}
      onSubmit={(e) => {
        const intent = ((e.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null)?.value;
        if (intent === "publiceren") {
          const extra = warnings.length ? `\n\nLet op: er ${warnings.length === 1 ? "is 1 waarschuwing" : `zijn ${warnings.length} waarschuwingen`} over allergieën/eetstijl.` : "";
          const when = later ? `Schema inplannen? De klant ziet het vanaf ${formatDay(start)} in Mijn omgeving.` : "Schema publiceren? De klant ziet het daarna direct in Mijn omgeving.";
          if (!confirm(`${when} Volgend schema: ${formatDay(renewOn)}.${extra}`)) {
            e.preventDefault();
            return;
          }
        }
        submitted.current = current;
      }}
      className="grid gap-6"
    >
      <input type="hidden" name="planId" value={planId} />
      <input type="hidden" name="content" value={json} />

      {warnings.length > 0 && (
        <div role="alert" className="rounded-xl border border-danger/30 bg-danger/5 p-4 text-sm">
          <p className="flex items-center gap-2 font-semibold text-danger">
            <AlertTriangle className="size-4" aria-hidden="true" /> Controleer op allergieën en eetstijl
          </p>
          <ul className="mt-2 space-y-1">
            {warnings.map((w, i) => (
              <li key={i}>
                <strong>{w.where}</strong>: &ldquo;{w.term}&rdquo; ({w.reason})
              </li>
            ))}
          </ul>
          <p className="mt-2 text-xs text-muted">Automatische controle op trefwoorden; niet volledig, dus controleer het schema ook zelf.</p>
        </div>
      )}

      <div className="flex gap-1 rounded-full bg-surface p-1 text-sm font-semibold" role="tablist" aria-label="Weergave">
        {(
          [
            ["bewerken", "Bewerken", Pencil],
            ["voorbeeld", "Voorbeeld voor de klant", Eye],
          ] as const
        ).map(([id, label, Icon]) => (
          <button
            key={id}
            type="button"
            role="tab"
            aria-selected={tab === id}
            onClick={() => setTab(id)}
            className={`flex flex-1 items-center justify-center gap-2 rounded-full px-4 py-2 transition-colors ${tab === id ? "bg-white shadow-sm" : "text-muted hover:text-ink"}`}
          >
            <Icon className="size-4" aria-hidden="true" /> {label}
          </button>
        ))}
      </div>

      {tab === "bewerken" ? (
        "days" in plan ? (
          <TrainingFields plan={plan} set={setPlan} />
        ) : (
          <NutritionFields plan={plan} set={setPlan} />
        )
      ) : (
        <div className="rounded-xl border border-line bg-paper p-5 sm:p-8">
          {"days" in plan ? <TrainingPlanView plan={plan} /> : <NutritionPlanView plan={plan} />}
        </div>
      )}

      <div className="sticky bottom-0 z-10 -mx-4 border-t border-line bg-paper/95 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-xl sm:border sm:px-5">
        <FormAlert error={state.error} success={dirty ? undefined : state.success} />
        <div className="mt-3 grid gap-2 text-sm first:mt-0">
          {!live && (
            <label className="flex flex-wrap items-center gap-x-3 gap-y-1">
              <span className="w-32 font-semibold">Start op</span>
              <input
                type="date"
                name="startsOn"
                required
                value={start < today ? today : start}
                min={today}
                max={addDays(today, 366)}
                onChange={(e) => changeStart(e.target.value)}
                className="input h-9 w-auto min-h-0 bg-white py-1 text-sm"
              />
              <span className="text-muted">{later ? `zichtbaar voor de klant vanaf ${formatDay(start)} (${relativeDay(today, start)})` : "direct na publiceren"}</span>
            </label>
          )}
          <label className="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span className="w-32 font-semibold">Nieuw schema op</span>
            <input
              type="date"
              name="renewOn"
              required
              value={renewOn}
              min={live && renewOn === initialRenewOn ? undefined : minRenew}
              max={addDays(later ? start : today, 366)}
              onChange={(e) => setPickedRenewOn(e.target.value || null)}
              className="input h-9 w-auto min-h-0 bg-white py-1 text-sm"
            />
            <span className={renewOn <= today ? "font-semibold text-danger" : "text-muted"}>
              {renewOn <= today ? "verlopen, kies een nieuwe datum" : later ? `${weeksAfterStart} ${weeksAfterStart === 1 ? "week" : "weken"} na de start` : relativeDay(today, renewOn)}
              {followDuration && pickedRenewOn === null && "days" in plan ? " · volgt de duur van het schema" : ""}
            </span>
          </label>
        </div>
        <div className="mt-3 flex flex-wrap items-center gap-3">
          <button type="submit" name="intent" value="opslaan" className="btn btn-outline bg-white" disabled={!dirty}>
            {status === "concept" ? "Concept opslaan" : "Wijzigingen opslaan"}
          </button>
          <button type="submit" name="intent" value="publiceren" className="btn btn-primary">
            {publishLabel}
          </button>
          <span className="text-sm text-muted" aria-live="polite">
            {dirty ? "Niet-opgeslagen wijzigingen" : "Alles opgeslagen"}
          </span>
        </div>
      </div>
    </form>
  );
}
