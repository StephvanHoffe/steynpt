"use client";

import { Check, Copy } from "lucide-react";
import { useActionState, useState } from "react";
import { addAvailabilityAction, addBlockedPeriodAction } from "@/lib/actions/agenda";
import type { FormState } from "@/lib/actions/types";
import { AGENDA_LOCATIONS, WEEKDAYS } from "@/lib/agenda";
import { FormAlert, SubmitButton } from "../forms/fields";

export function AvailabilityForm() {
  const [state, action] = useActionState<FormState, FormData>(addAvailabilityAction, {});
  return (
    <form action={action} className="grid gap-3">
      <FormAlert error={state.error} success={state.success} />
      <div className="grid grid-cols-2 gap-3">
        <label className="block">
          <span className="label">Dag</span>
          <select name="weekday" className="input" defaultValue="1">
            {WEEKDAYS.map((d, i) => (
              <option key={d} value={i + 1}>
                {d}
              </option>
            ))}
          </select>
        </label>
        <label className="block">
          <span className="label">Van</span>
          <input type="time" name="startTime" defaultValue="07:00" step={1800} className="input" required />
        </label>
        <label className="block">
          <span className="label">Tot</span>
          <input type="time" name="endTime" defaultValue="12:00" step={1800} className="input" required />
        </label>
        <label className="block">
          <span className="label">Locatie</span>
          <select name="location" className="input" defaultValue="workout">
            {AGENDA_LOCATIONS.map((l) => (
              <option key={l.id} value={l.id}>
                {l.label}
              </option>
            ))}
          </select>
        </label>
        <SubmitButton className="btn btn-primary col-span-2 justify-self-start" pendingText="…">
          Toevoegen
        </SubmitButton>
      </div>
    </form>
  );
}

export function BlockForm({ today }: { today: string }) {
  const [state, action] = useActionState<FormState, FormData>(addBlockedPeriodAction, {});
  return (
    <form action={action} className="grid gap-3">
      <FormAlert error={state.error} success={state.success} />
      <div className="grid grid-cols-2 gap-3">
        <label className="block">
          <span className="label">Van</span>
          <input type="date" name="fromDay" min={today} defaultValue={today} className="input" required />
        </label>
        <label className="block">
          <span className="label">Tot en met</span>
          <input type="date" name="toDay" min={today} defaultValue={today} className="input" required />
        </label>
        <label className="col-span-2 block">
          <span className="label">Reden (alleen voor jou)</span>
          <input name="reason" className="input" placeholder="Bijv. vakantie" maxLength={200} />
        </label>
        <SubmitButton className="btn btn-primary col-span-2 justify-self-start" pendingText="…">
          Blokkeren
        </SubmitButton>
      </div>
    </form>
  );
}

export function CopyField({ value, label }: { value: string; label: string }) {
  const [copied, setCopied] = useState(false);
  return (
    <div className="flex items-center gap-2 rounded-lg border border-line bg-white p-1.5 pl-3">
      <input readOnly value={value} aria-label={label} className="min-w-0 flex-1 bg-transparent font-mono text-xs outline-none" onFocus={(e) => e.currentTarget.select()} />
      <button
        type="button"
        className="btn btn-sm btn-primary shrink-0"
        onClick={async () => {
          try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
          } catch {
            window.prompt(label, value);
          }
        }}
      >
        {copied ? <Check className="size-4" aria-hidden="true" /> : <Copy className="size-4" aria-hidden="true" />}
        {copied ? "Gekopieerd" : "Kopieer"}
      </button>
    </div>
  );
}
