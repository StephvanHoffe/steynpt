"use client";

import { useRef } from "react";

/** Springen naar een datum; verstuurt direct na het kiezen. */
export function DateJump({ day, view, cancelled }: { day: string; view: string; cancelled: boolean }) {
  const form = useRef<HTMLFormElement>(null);
  return (
    <form ref={form} action="/admin/agenda" method="get" className="flex items-center gap-1.5">
      {view !== "week" && <input type="hidden" name="weergave" value={view} />}
      {cancelled && <input type="hidden" name="geannuleerd" value="1" />}
      <label className="sr-only" htmlFor="agenda-datum">
        Ga naar datum
      </label>
      <input
        id="agenda-datum"
        type="date"
        name="datum"
        defaultValue={day}
        required
        onChange={(e) => e.currentTarget.value && form.current?.requestSubmit()}
        className="h-9 rounded-lg border border-line bg-white px-2 text-sm"
      />
      <noscript>
        <button type="submit" className="btn btn-sm btn-outline">
          Ga
        </button>
      </noscript>
    </form>
  );
}
