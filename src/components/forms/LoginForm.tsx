"use client";

import Link from "next/link";
import { useActionState } from "react";
import { loginAction } from "@/lib/actions/auth";
import type { FormState } from "@/lib/actions/types";
import { Field, FormAlert, SubmitButton } from "./fields";

export function LoginForm({ next }: { next?: string }) {
  const [state, action] = useActionState<FormState, FormData>(loginAction, {});
  const v = state.values ?? {};
  const e = state.fieldErrors ?? {};
  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />
      {next && <input type="hidden" name="next" value={next} />}
      <Field label="E-mailadres" name="email" type="email" autoComplete="email" required defaultValue={v.email} error={e.email} />
      <Field label="Wachtwoord" name="password" type="password" autoComplete="current-password" required error={e.password} />
      <SubmitButton pendingText="Inloggen…">Inloggen</SubmitButton>
      <p className="text-center text-sm text-muted">
        Nog geen account?{" "}
        <Link href="/registreren" className="font-semibold text-ink underline">
          Maak er gratis een aan
        </Link>
      </p>
      <p className="text-center text-xs text-muted">
        Wachtwoord vergeten?{" "}
        <Link href="/contact" className="underline">
          Neem contact op
        </Link>
        , dan helpen we je verder.
      </p>
    </form>
  );
}
