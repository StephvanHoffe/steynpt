"use client";

import { useActionState } from "react";
import { checkInAction } from "@/lib/actions/account";
import type { FormState } from "@/lib/actions/types";
import { POINTS } from "@/lib/loyalty";
import { FormAlert, SubmitButton } from "../forms/fields";

const scales = [
  { name: "energy", label: "Energie", low: "Leeg", high: "Topfit" },
  { name: "sleep", label: "Slaap", low: "Slecht", high: "Uitstekend" },
  { name: "nutrition", label: "Voeding", low: "Lastig", high: "Volgens plan" },
];

export function CheckInForm({ done }: { done: boolean }) {
  const [state, action] = useActionState<FormState, FormData>(checkInAction, {});
  const v = state.values ?? {};
  const e = state.fieldErrors ?? {};

  if (state.success) return <FormAlert success={state.success} />;
  if (done) return null;

  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />
      {scales.map((scale) => (
        <fieldset key={scale.name}>
          <legend className="label">{scale.label}</legend>
          <div className="grid grid-cols-5 gap-1.5">
            {[1, 2, 3, 4, 5].map((n) => (
              <label
                key={n}
                className="grid h-11 cursor-pointer place-items-center rounded-lg border-[1.5px] border-line bg-white text-sm font-semibold transition-colors hover:border-ink has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-volt has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-volt-deep"
              >
                <input type="radio" name={scale.name} value={n} defaultChecked={v[scale.name] === String(n)} className="sr-only" />
                {n}
              </label>
            ))}
          </div>
          <div className="mt-1 flex justify-between text-xs text-muted">
            <span>{scale.low}</span>
            <span>{scale.high}</span>
          </div>
          {e[scale.name] && <p className="field-error">{e[scale.name]}</p>}
        </fieldset>
      ))}
      <div className="grid gap-5 sm:grid-cols-2">
        <div>
          <label htmlFor="ci-workouts" className="label">
            Trainingen deze week
          </label>
          <input id="ci-workouts" name="workouts" type="number" min={0} max={21} inputMode="numeric" defaultValue={v.workouts ?? "3"} className="input" aria-invalid={e.workouts ? true : undefined} />
          {e.workouts && <p className="field-error">{e.workouts}</p>}
        </div>
        <div>
          <label htmlFor="ci-weight" className="label">
            Gewicht in kg <span className="font-normal text-muted">(optioneel)</span>
          </label>
          <input id="ci-weight" name="weight" inputMode="decimal" placeholder="Bijv. 74,5" defaultValue={v.weight} className="input" aria-invalid={e.weight ? true : undefined} />
          {e.weight && <p className="field-error">{e.weight}</p>}
        </div>
      </div>
      <div>
        <label htmlFor="ci-note" className="label">
          Hoe ging je week? <span className="font-normal text-muted">(optioneel)</span>
        </label>
        <textarea id="ci-note" name="note" className="input min-h-24" defaultValue={v.note} placeholder="Wat ging goed, waar liep je tegenaan?" />
      </div>
      <SubmitButton pendingText="Opslaan…">Check in · +{POINTS.weeklyCheckIn} punten</SubmitButton>
    </form>
  );
}
