"use client";

import { useActionState } from "react";
import { bookAppointmentAction } from "@/lib/actions/agenda";
import type { FormState } from "@/lib/actions/types";
import { FormAlert, SubmitButton } from "../forms/fields";

export function BookingForm({ type, location, slots }: { type: string; location: string; slots: { value: string; label: string }[] }) {
  const [state, action] = useActionState<FormState, FormData>(bookAppointmentAction, {});
  return (
    <form action={action} className="grid gap-5">
      <input type="hidden" name="type" value={type} />
      <input type="hidden" name="location" value={location} />
      <FormAlert error={state.error} />
      <fieldset>
        <legend className="label">Kies een tijd</legend>
        <div className="grid grid-cols-3 gap-2 sm:grid-cols-5">
          {slots.map((slot) => (
            <label
              key={slot.value}
              className="grid h-11 cursor-pointer place-items-center rounded-lg border border-line bg-white text-sm font-semibold tabular-nums transition-colors hover:border-ink has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent"
            >
              <input type="radio" name="start" value={slot.value} className="sr-only" required />
              {slot.label}
            </label>
          ))}
        </div>
      </fieldset>
      <label className="block">
        <span className="label">
          Opmerking voor Steyn <span className="font-normal text-muted">(optioneel)</span>
        </span>
        <textarea name="note" maxLength={500} className="input min-h-20" placeholder="Bijv. waar je aan wilt werken of het adres bij een training op locatie" />
      </label>
      <SubmitButton className="btn btn-primary justify-self-start" pendingText="Bezig met boeken…">
        Afspraak bevestigen
      </SubmitButton>
    </form>
  );
}
