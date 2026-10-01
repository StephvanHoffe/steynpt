"use client";

import { useActionState } from "react";
import { addMeasurementAction } from "@/lib/actions/progress";
import type { FormState } from "@/lib/actions/types";
import { MEASUREMENT_FIELDS } from "@/lib/progress";
import { FormAlert, SubmitButton } from "../forms/fields";

export function MeasurementForm({ userId, today }: { userId: string; today: string }) {
  const [state, action] = useActionState<FormState, FormData>(addMeasurementAction, {});
  const e = state.fieldErrors ?? {};
  const v = state.success ? {} : (state.values ?? {});
  return (
    <form action={action} className="grid gap-4">
      <input type="hidden" name="userId" value={userId} />
      <FormAlert error={state.error} success={state.success} />
      <label className="block max-w-xs">
        <span className="label">Datum</span>
        <input type="date" name="measuredAt" defaultValue={v.measuredAt ?? today} max={today} className="input" aria-invalid={e.measuredAt ? true : undefined} />
        {e.measuredAt && <span className="field-error block">{e.measuredAt}</span>}
      </label>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {MEASUREMENT_FIELDS.map((f) => (
          <label key={f.key} className="block">
            <span className="label">
              {f.label} <span className="font-normal text-muted">({f.unit})</span>
            </span>
            <input name={f.key} inputMode="decimal" defaultValue={v[f.key]} className="input" aria-invalid={e[f.key] ? true : undefined} />
            {e[f.key] && <span className="field-error block">{e[f.key]}</span>}
          </label>
        ))}
      </div>
      <label className="block">
        <span className="label">
          Notitie <span className="font-normal text-muted">(zichtbaar voor de klant)</span>
        </span>
        <textarea name="note" defaultValue={v.note} className="input min-h-16" placeholder="Bijv. gemeten met huidplooimeter, nuchter" />
      </label>
      <SubmitButton className="btn btn-primary justify-self-start" pendingText="Opslaan…">
        Meting opslaan
      </SubmitButton>
    </form>
  );
}
