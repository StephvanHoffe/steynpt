"use client";

import { useRef } from "react";

export type PickerGroup = { label: string; members: { id: string; name: string; note?: string }[] };

/** Klant kiezen voor een nieuw schema; laadt direct de gegevens van die klant. */
export function MemberPicker({ action, groups, selected }: { action: string; groups: PickerGroup[]; selected?: string }) {
  const form = useRef<HTMLFormElement>(null);
  return (
    <form ref={form} action={action} method="get" className="flex flex-wrap items-end gap-2">
      <label className="block min-w-0 flex-1 basis-64">
        <span className="label">Klant</span>
        <select
          name="lid"
          defaultValue={selected ?? ""}
          required
          onChange={(e) => e.currentTarget.value && form.current?.requestSubmit()}
          className="input"
        >
          <option value="" disabled>
            Kies een klant…
          </option>
          {groups
            .filter((g) => g.members.length > 0)
            .map((g) => (
              <optgroup key={g.label} label={g.label}>
                {g.members.map((m) => (
                  <option key={m.id} value={m.id}>
                    {m.name}
                    {m.note ? ` · ${m.note}` : ""}
                  </option>
                ))}
              </optgroup>
            ))}
        </select>
      </label>
      <noscript>
        <button type="submit" className="btn btn-outline">
          Kies
        </button>
      </noscript>
    </form>
  );
}
