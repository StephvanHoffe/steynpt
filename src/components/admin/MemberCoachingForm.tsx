"use client";

import { useActionState } from "react";
import { updateMemberAction } from "@/lib/actions/admin";
import type { FormState } from "@/lib/actions/types";
import type { CoachingStatus } from "@/lib/db/schema";
import { FormAlert, SubmitButton } from "../forms/fields";
import { COACHING_LABEL } from "./labels";

const STATUSES = Object.keys(COACHING_LABEL) as CoachingStatus[];

/** Coachingstatus en bericht voor het dashboard van de klant. */
export function MemberCoachingForm({ userId, status, note }: { userId: string; status: CoachingStatus; note: string | null }) {
  const [state, action] = useActionState<FormState, FormData>(updateMemberAction, {});
  return (
    <form action={action} className="grid gap-4">
      <input type="hidden" name="userId" value={userId} />
      <FormAlert error={state.error} success={state.success} />
      <label className="block">
        <span className="label">Coachingstatus</span>
        <select name="coachingStatus" defaultValue={status} className="input">
          {STATUSES.map((s) => (
            <option key={s} value={s}>
              {COACHING_LABEL[s]}
            </option>
          ))}
        </select>
        <span className="mt-1 block text-xs text-muted">Zet op Actief zodra de klant betaald start. Kwam de klant via een vriend, dan verschijnt de vriendenkorting in je overzicht.</span>
      </label>
      <label className="block">
        <span className="label">Bericht in het dashboard van de klant</span>
        <textarea name="coachNote" defaultValue={note ?? ""} className="input min-h-28" placeholder="Bijv. feedback op de laatste check-in" maxLength={2000} />
      </label>
      <SubmitButton className="btn btn-primary justify-self-start" pendingText="Opslaan…">
        Opslaan
      </SubmitButton>
    </form>
  );
}
