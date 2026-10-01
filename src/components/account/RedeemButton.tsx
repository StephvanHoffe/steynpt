"use client";

import { useActionState } from "react";
import { redeemRewardAction } from "@/lib/actions/account";
import type { FormState } from "@/lib/actions/types";
import { SubmitButton } from "../forms/fields";

export function RedeemButton({ rewardId, title, cost, affordable }: { rewardId: string; title: string; cost: number; affordable: boolean }) {
  const [state, action] = useActionState<FormState, FormData>(redeemRewardAction, {});
  return (
    <form
      action={action}
      onSubmit={(e) => {
        if (!confirm(`${title} inwisselen voor ${cost} punten?`)) e.preventDefault();
      }}
    >
      <input type="hidden" name="rewardId" value={rewardId} />
      {state.success ? (
        <p role="status" className="text-xs font-semibold text-success">{state.success}</p>
      ) : (
        <>
          <SubmitButton className="btn btn-sm btn-ink w-full" pendingText="…" disabled={!affordable}>
            {affordable ? "Inwisselen" : `Nog niet genoeg punten`}
          </SubmitButton>
          {state.error && <p role="alert" className="field-error">{state.error}</p>}
        </>
      )}
    </form>
  );
}
