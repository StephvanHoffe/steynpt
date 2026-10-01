"use client";

import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { useActionState, type ReactNode } from "react";
import { saveIntakeAction } from "@/lib/actions/intake";
import type { FormState } from "@/lib/actions/types";
import {
  ACTIVITY_LEVELS,
  ALLERGIES,
  DIETS,
  EXPERIENCE,
  LOCATIONS,
  PLAN_WANTS,
  SESSION_MINUTES,
  SEXES,
  type IntakeData,
} from "@/lib/intake";
import { GOALS } from "@/lib/site";
import { Field, FormAlert, SelectField, SubmitButton } from "../forms/fields";

type Option = { id: string; label: string; hint?: string };

function Choices({
  name,
  legend,
  options,
  multiple,
  selected,
  error,
  columns = "sm:grid-cols-3",
}: {
  name: string;
  legend: string;
  options: readonly Option[];
  multiple?: boolean;
  selected: string[];
  error?: string;
  columns?: string;
}) {
  return (
    <fieldset aria-invalid={error ? true : undefined}>
      <legend className="label">{legend}</legend>
      <div className={`grid grid-cols-2 gap-2 ${columns}`}>
        {options.map((o) => (
          <label
            key={o.id}
            className="flex cursor-pointer items-start gap-2.5 rounded-xl border-[1.5px] border-line bg-white px-3.5 py-3 text-sm transition-colors hover:border-rose-soft has-[:checked]:border-rose has-[:checked]:bg-blush has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-rose"
          >
            <input
              type={multiple ? "checkbox" : "radio"}
              name={name}
              value={o.id}
              defaultChecked={selected.includes(o.id)}
              className="mt-0.5 size-4 shrink-0 accent-rose"
            />
            <span>
              <span className="block font-medium">{o.label}</span>
              {o.hint && <span className="mt-0.5 block text-xs text-muted">{o.hint}</span>}
            </span>
          </label>
        ))}
      </div>
      {error && <p className="field-error">{error}</p>}
    </fieldset>
  );
}

function TextArea({ name, label, hint, defaultValue, error, placeholder }: { name: string; label: string; hint?: ReactNode; defaultValue?: string; error?: string; placeholder?: string }) {
  return (
    <div>
      <label htmlFor={`f-${name}`} className="label">
        {label} <span className="font-normal text-muted">(optioneel)</span>
      </label>
      <textarea id={`f-${name}`} name={name} defaultValue={defaultValue} placeholder={placeholder} className="input min-h-20" aria-invalid={error ? true : undefined} />
      {hint && !error && <p className="mt-1.5 text-xs text-muted">{hint}</p>}
      {error && <p className="field-error">{error}</p>}
    </div>
  );
}

function Section({ step, title, intro, children }: { step: number; title: string; intro?: string; children: ReactNode }) {
  return (
    <section className="card p-6 sm:p-8">
      <p className="text-xs font-bold uppercase tracking-[0.14em] text-rose">Stap {step}</p>
      <h2 className="display mt-1 text-2xl">{title}</h2>
      {intro && <p className="mt-1 text-sm text-muted">{intro}</p>}
      <div className="mt-6 grid gap-6">{children}</div>
    </section>
  );
}

const asOptions = (values: readonly (string | number)[], suffix = "") => values.map((v) => ({ id: String(v), label: `${v}${suffix}` }));

export function IntakeForm({ initial, defaultGoal }: { initial?: IntakeData; defaultGoal?: string | null }) {
  const [state, action] = useActionState<FormState, FormData>(saveIntakeAction, {});
  const e = state.fieldErrors ?? {};
  const v = state.values;
  // Na een fout tonen we wat de klant net invulde, anders de opgeslagen intake.
  const val = (key: keyof IntakeData) => (v ? v[key] : initial?.[key] != null ? String(initial[key]) : undefined);
  const list = (key: "wants" | "allergies") => (v ? (v[key] ? v[key].split(",") : []) : (initial?.[key] ?? (key === "wants" ? ["training", "voeding"] : [])));
  const one = (key: keyof IntakeData) => {
    const x = val(key);
    return x ? [x] : [];
  };

  return (
    <form action={action} className="grid gap-6" noValidate>
      <FormAlert error={state.error} />

      <Section step={1} title="Wat wil je bereiken?">
        <Choices name="wants" legend="Waarvoor wil je een schema?" options={PLAN_WANTS} multiple selected={list("wants")} error={e.wants} columns="sm:grid-cols-2" />
        <SelectField label="Je belangrijkste doel" name="goal" options={GOALS} placeholder="Kies je doel" defaultValue={val("goal") ?? defaultGoal ?? ""} error={e.goal} />
        <TextArea name="goalDetails" label="Vertel iets meer over je doel" defaultValue={val("goalDetails")} error={e.goalDetails} placeholder="Bijv. 'Ik wil in juni een halve marathon lopen' of '5 kilo afvallen en meer energie'" />
      </Section>

      <Section step={2} title="Over jou" intro="Hiermee berekenen we onder andere je energiebehoefte.">
        <Choices name="sex" legend="Geslacht" options={SEXES} selected={one("sex")} error={e.sex} />
        <div className="grid gap-5 sm:grid-cols-3">
          <Field label="Geboortejaar" name="birthYear" inputMode="numeric" placeholder="Bijv. 1995" defaultValue={val("birthYear")} error={e.birthYear} />
          <Field label="Lengte (cm)" name="heightCm" inputMode="numeric" placeholder="Bijv. 178" defaultValue={val("heightCm")} error={e.heightCm} />
          <Field label="Gewicht (kg)" name="weightKg" inputMode="decimal" placeholder="Bijv. 74,5" defaultValue={val("weightKg")} error={e.weightKg} />
        </div>
        <Field label="Streefgewicht (kg)" name="targetWeightKg" inputMode="decimal" placeholder="Optioneel" defaultValue={val("targetWeightKg")} error={e.targetWeightKg} />
        <Choices name="activityLevel" legend="Hoe actief ben je overdag (naast het sporten)?" options={ACTIVITY_LEVELS} selected={one("activityLevel")} error={e.activityLevel} columns="sm:grid-cols-4" />
        <TextArea
          name="medical"
          label="Medische aandachtspunten of medicatie"
          defaultValue={val("medical")}
          error={e.medical}
          hint="Bijv. hoge bloeddruk, diabetes, zwangerschap of medicijngebruik. Steyn houdt hier rekening mee."
        />
      </Section>

      <Section step={3} title="Training">
        <Choices name="experience" legend="Hoeveel trainingservaring heb je?" options={EXPERIENCE} selected={one("experience")} error={e.experience} />
        <Choices name="trainingDays" legend="Hoe vaak per week wil je trainen?" options={asOptions([1, 2, 3, 4, 5, 6, 7], "×")} selected={one("trainingDays")} error={e.trainingDays} columns="grid-cols-4 sm:grid-cols-7" />
        <Choices name="sessionMinutes" legend="Hoe lang mag een training duren?" options={asOptions(SESSION_MINUTES, " min")} selected={one("sessionMinutes")} error={e.sessionMinutes} columns="sm:grid-cols-5" />
        <Choices name="location" legend="Waar train je meestal?" options={LOCATIONS} selected={one("location")} error={e.location} columns="sm:grid-cols-4" />
        <TextArea name="equipment" label="Welk materiaal heb je?" defaultValue={val("equipment")} error={e.equipment} placeholder="Bijv. dumbbells tot 20 kg, weerstandsbanden, een bankje" />
        <Field label="Beoefen je een sport? (optioneel)" name="sport" defaultValue={val("sport")} error={e.sport} placeholder="Bijv. hockey, hardlopen, voetbal" />
        <TextArea name="injuries" label="Blessures of fysieke beperkingen" defaultValue={val("injuries")} error={e.injuries} placeholder="Bijv. last van je onderrug of een oude knieblessure" />
      </Section>

      <Section step={4} title="Voeding">
        <Choices name="diet" legend="Wat is je eetstijl?" options={DIETS} selected={one("diet")} error={e.diet} />
        <Choices name="allergies" legend="Allergieën en intoleranties" options={ALLERGIES} multiple selected={list("allergies")} error={e.allergies} columns="sm:grid-cols-4" />
        <Field label="Andere allergieën of intoleranties (optioneel)" name="allergiesOther" defaultValue={val("allergiesOther")} error={e.allergiesOther} />
        <TextArea name="dislikes" label="Wat lust je niet?" defaultValue={val("dislikes")} error={e.dislikes} placeholder="Bijv. champignons, koriander" />
        <Choices name="mealsPerDay" legend="Hoeveel eetmomenten per dag passen bij jou?" options={asOptions([2, 3, 4, 5, 6])} selected={one("mealsPerDay")} error={e.mealsPerDay} columns="grid-cols-5" />
      </Section>

      <div className="rounded-[1.25rem] bg-blush p-6">
        <label className="flex gap-3 text-sm">
          <input type="checkbox" name="consent" className="mt-0.5 size-4 shrink-0 accent-rose" defaultChecked={v?.consent === "on"} />
          <span>
            Ik geef toestemming om deze gegevens, waaronder gezondheidsgegevens, te gebruiken voor mijn schema. Een eerste
            opzet wordt gemaakt met behulp van AI, zonder mijn naam of contactgegevens; Steyn controleert en past het schema aan
            voordat ik het te zien krijg. Lees de{" "}
            <Link href="/privacy" target="_blank" className="font-semibold underline">
              privacyverklaring
            </Link>
            .
          </span>
        </label>
        {e.consent && <p className="field-error">{e.consent}</p>}
        <SubmitButton className="btn btn-primary mt-5 w-full sm:w-auto" pendingText="Opslaan…">
          Intake opslaan <ArrowRight className="size-4" aria-hidden="true" />
        </SubmitButton>
      </div>
    </form>
  );
}
