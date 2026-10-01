"use client";

import { useActionState, useState } from "react";
import { createAppointmentAction } from "@/lib/actions/agenda";
import type { FormState } from "@/lib/actions/types";
import { AGENDA_LOCATIONS, APPOINTMENT_TYPES } from "@/lib/agenda";
import { FormAlert, SubmitButton } from "../forms/fields";

type Member = { id: string; name: string; note: string };
type Defaults = { userId?: string; type?: string; location?: string; day: string; time: string; note?: string; replaces?: number };

/** Afspraak inplannen of verplaatsen door Steyn. */
export function AppointmentForm({ members, defaults, minDay, lockedMember }: { members: Member[]; defaults: Defaults; minDay: string; lockedMember?: Member }) {
  const [state, action] = useActionState<FormState, FormData>(createAppointmentAction, {});
  const v = state.values ?? {};
  const e = state.fieldErrors ?? {};
  const [typeId, setTypeId] = useState(v.type ?? defaults.type ?? APPOINTMENT_TYPES[0].id);
  const type = APPOINTMENT_TYPES.find((t) => t.id === typeId) ?? APPOINTMENT_TYPES[0];
  const locations = AGENDA_LOCATIONS.filter((l) => (type.locations as readonly string[]).includes(l.id));
  const [locationId, setLocationId] = useState(v.location ?? defaults.location ?? type.locations[0]);
  const location = locations.some((l) => l.id === locationId) ? locationId : locations[0].id;
  const err = (name: string) => e[name] && <span className="mt-1 block text-sm text-danger">{e[name]}</span>;

  return (
    <form action={action} className="grid gap-5">
      <FormAlert error={state.error} />
      {defaults.replaces && <input type="hidden" name="replaces" value={defaults.replaces} />}

      <label className="block">
        <span className="label">Klant</span>
        {lockedMember ? (
          <>
            <input type="hidden" name="userId" value={lockedMember.id} />
            <span className="input flex items-center bg-surface">{lockedMember.name}</span>
          </>
        ) : (
          <select name="userId" className="input" defaultValue={v.userId ?? defaults.userId ?? ""} required aria-invalid={e.userId ? true : undefined}>
            <option value="" disabled>
              Kies een klant
            </option>
            {members.map((m) => (
              <option key={m.id} value={m.id}>
                {m.name}
                {m.note ? ` · ${m.note}` : ""}
              </option>
            ))}
          </select>
        )}
        {err("userId")}
      </label>

      <fieldset>
        <legend className="label">Soort afspraak</legend>
        <div className="grid gap-2 sm:grid-cols-2">
          {APPOINTMENT_TYPES.map((t) => (
            <label
              key={t.id}
              className="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-line bg-white px-3 py-2.5 text-sm has-[:checked]:border-ink has-[:checked]:ring-1 has-[:checked]:ring-ink"
            >
              <span className="flex items-center gap-2.5">
                <input type="radio" name="type" value={t.id} checked={typeId === t.id} onChange={() => setTypeId(t.id)} className="accent-ink" />
                <span className="font-medium">{t.label}</span>
              </span>
              <span className="text-xs text-muted">{t.minutes} min</span>
            </label>
          ))}
        </div>
        {err("type")}
      </fieldset>

      <div className="grid gap-4 sm:grid-cols-3">
        <label className="block">
          <span className="label">Locatie</span>
          <select name="location" className="input" value={location} onChange={(ev) => setLocationId(ev.target.value)}>
            {locations.map((l) => (
              <option key={l.id} value={l.id}>
                {l.label}
              </option>
            ))}
          </select>
          {err("location")}
        </label>
        <label className="block">
          <span className="label">Datum</span>
          <input type="date" name="day" min={minDay} defaultValue={v.day ?? defaults.day} className="input" required aria-invalid={e.day ? true : undefined} />
          {err("day")}
        </label>
        <label className="block">
          <span className="label">Begintijd</span>
          <input type="time" name="time" step={900} defaultValue={v.time ?? defaults.time} className="input" required aria-invalid={e.time ? true : undefined} />
          {err("time")}
        </label>
      </div>
      <p className="-mt-2 text-sm text-muted">
        Duurt {type.minutes} minuten. Je kunt ook buiten je vaste beschikbaarheid plannen; dubbel boeken kan niet.
      </p>

      <label className="block">
        <span className="label">
          Opmerking <span className="font-normal text-muted">(de klant ziet deze niet)</span>
        </span>
        <textarea name="note" maxLength={500} defaultValue={v.note ?? defaults.note ?? ""} className="input min-h-20" placeholder="Bijv. focus op techniek of het adres bij een training op locatie" />
      </label>

      <div className="flex flex-wrap items-center gap-3">
        <SubmitButton className="btn btn-primary" pendingText="Bezig…">
          {defaults.replaces ? "Afspraak verplaatsen" : "Afspraak inplannen"}
        </SubmitButton>
      </div>
    </form>
  );
}
