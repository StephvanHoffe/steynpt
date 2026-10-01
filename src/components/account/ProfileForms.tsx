"use client";

import { useActionState } from "react";
import { changePasswordAction, deleteAccountAction, updateProfileAction } from "@/lib/actions/account";
import type { FormState } from "@/lib/actions/types";
import { GOALS } from "@/lib/site";
import { Field, FormAlert, SelectField, SubmitButton } from "../forms/fields";

type Profile = {
  firstName: string;
  lastName: string;
  email: string;
  phone: string | null;
  goal: string | null;
  marketingOptIn: boolean;
};

export function ProfileForm({ profile }: { profile: Profile }) {
  const [state, action] = useActionState<FormState, FormData>(updateProfileAction, {});
  const v = state.values;
  const e = state.fieldErrors ?? {};
  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} success={state.success} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Voornaam" name="firstName" autoComplete="given-name" defaultValue={v?.firstName ?? profile.firstName} error={e.firstName} />
        <Field label="Achternaam" name="lastName" autoComplete="family-name" defaultValue={v?.lastName ?? profile.lastName} error={e.lastName} />
      </div>
      <Field label="E-mailadres" name="email-readonly" defaultValue={profile.email} disabled hint="Wil je je e-mailadres wijzigen? Neem contact op." />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Telefoonnummer" name="phone" type="tel" autoComplete="tel" defaultValue={v?.phone ?? profile.phone ?? ""} error={e.phone} />
        <SelectField label="Je belangrijkste doel" name="goal" options={GOALS} placeholder="Kies je doel" defaultValue={v?.goal ?? profile.goal ?? ""} error={e.goal} />
      </div>
      <label className="flex gap-3 text-sm">
        <input
          type="checkbox"
          name="marketing"
          defaultChecked={v ? v.marketing === "on" : profile.marketingOptIn}
          className="mt-0.5 size-4 shrink-0 accent-rose"
        />
        Houd me op de hoogte van acties, tips en nieuwe sessies.
      </label>
      <SubmitButton className="btn btn-ink w-full sm:w-auto" pendingText="Opslaan…">
        Opslaan
      </SubmitButton>
    </form>
  );
}

export function PasswordForm() {
  const [state, action] = useActionState<FormState, FormData>(changePasswordAction, {});
  const e = state.fieldErrors ?? {};
  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} success={state.success} />
      <Field label="Huidig wachtwoord" name="current" type="password" autoComplete="current-password" error={e.current} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Nieuw wachtwoord" name="password" type="password" autoComplete="new-password" minLength={8} error={e.password} hint="Minimaal 8 tekens" />
        <Field label="Herhaal nieuw wachtwoord" name="confirm" type="password" autoComplete="new-password" error={e.confirm} />
      </div>
      <SubmitButton className="btn btn-ink w-full sm:w-auto" pendingText="Wijzigen…">
        Wachtwoord wijzigen
      </SubmitButton>
    </form>
  );
}

export function DeleteAccountForm() {
  const [state, action] = useActionState<FormState, FormData>(deleteAccountAction, {});
  const e = state.fieldErrors ?? {};
  return (
    <form action={action} className="grid gap-4" noValidate>
      <FormAlert error={state.error} />
      <Field id="delete-password" label="Wachtwoord ter bevestiging" name="password" type="password" autoComplete="current-password" error={e.password} />
      <label className="flex gap-3 text-sm">
        <input type="checkbox" name="confirm" className="mt-0.5 size-4 shrink-0 accent-danger" />
        Ik begrijp dat mijn account, punten, check-ins, intake en schema's definitief worden verwijderd.
      </label>
      {e.confirm && <p className="field-error">{e.confirm}</p>}
      <SubmitButton className="btn btn-sm w-full border-[1.5px] border-danger text-danger hover:bg-danger hover:text-white sm:w-auto" pendingText="Verwijderen…">
        Account verwijderen
      </SubmitButton>
    </form>
  );
}
