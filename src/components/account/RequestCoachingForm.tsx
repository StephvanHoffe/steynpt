"use client";

import { useActionState } from "react";
import { requestCoachingAction } from "@/lib/actions/account";
import type { FormState } from "@/lib/actions/types";
import { ONLINE_PLANS } from "@/lib/site";
import { FormAlert, SubmitButton } from "../forms/fields";

export function RequestCoachingForm({ currentPlan }: { currentPlan?: string | null }) {
  const [state, action] = useActionState<FormState, FormData>(requestCoachingAction, {});
  if (state.success) return <FormAlert success={state.success} />;
  return (
    <form action={action} className="grid gap-3">
      <FormAlert error={state.error} />
      <div className="grid gap-2 sm:grid-cols-3">
        {ONLINE_PLANS.map((plan) => (
          <label
            key={plan.id}
            className="flex cursor-pointer flex-col rounded-xl border-[1.5px] border-line bg-white p-4 transition-colors has-[:checked]:border-ink has-[:checked]:bg-blush"
          >
            <input type="radio" name="plan" value={plan.id} defaultChecked={(currentPlan ?? "online-pro") === plan.id} className="sr-only" />
            <span className="font-semibold">{plan.name}</span>
            <span className="text-sm text-muted">€ {plan.price} p/m</span>
          </label>
        ))}
      </div>
      <SubmitButton className="btn btn-primary w-full sm:w-auto" pendingText="Aanvragen…">
        Start online coaching
      </SubmitButton>
    </form>
  );
}
