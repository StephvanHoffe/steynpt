"use client";

import { useActionState } from "react";
import { renewPasswordAction } from "@/lib/actions/account";
import type { FormState } from "@/lib/actions/types";
import { Field, FormAlert, SubmitButton } from "./fields";

export function RenewPasswordForm({ next }: { next: string }) {
  const [state, action] = useActionState<FormState, FormData>(renewPasswordAction, {});
  const e = state.fieldErrors ?? {};
  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />
      <input type="hidden" name="next" value={next} />
      <Field label="Huidig wachtwoord" name="current" type="password" autoComplete="current-password" required error={e.current} />
      <Field label="Nieuw wachtwoord" name="password" type="password" autoComplete="new-password" required minLength={8} error={e.password} hint="Minimaal 8 tekens." />
      <Field label="Herhaal nieuw wachtwoord" name="confirm" type="password" autoComplete="new-password" required error={e.confirm} />
      <SubmitButton pendingText="Opslaan…">Wachtwoord opslaan</SubmitButton>
      <p className="text-center text-xs text-muted">Andere apparaten waarop je bent ingelogd, worden daarna uitgelogd.</p>
    </form>
  );
}
