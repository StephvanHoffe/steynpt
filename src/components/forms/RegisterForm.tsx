"use client";

import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { useActionState } from "react";
import { registerAction } from "@/lib/actions/auth";
import type { FormState } from "@/lib/actions/types";
import { GOALS, type OnlinePlan } from "@/lib/site";
import { Field, FormAlert, SelectField, SubmitButton } from "./fields";

export function RegisterForm({
  plan,
  plans,
  friendReward,
  referralCode,
  inviterName,
}: {
  plan?: string;
  plans: Pick<OnlinePlan, "id" | "name" | "price">[];
  friendReward: string;
  referralCode?: string;
  inviterName?: string;
}) {
  const [state, action] = useActionState<FormState, FormData>(registerAction, {});
  const v = state.values ?? {};
  const e = state.fieldErrors ?? {};
  const selectedPlan = v.plan ?? plan ?? "";

  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />

      <fieldset>
        <legend className="label">Kies je online coaching pakket</legend>
        <div className="grid gap-2.5 sm:grid-cols-2">
          {[...plans.map((p) => ({ id: p.id, title: p.name, sub: `€ ${p.price} per maand` })), { id: "", title: "Nog niet", sub: "Eerst rondkijken" }].map((option) => (
            <label
              key={option.id || "geen"}
              className="flex cursor-pointer items-center gap-3 rounded-xl border-[1.5px] border-line bg-white p-4 transition-colors has-[:checked]:border-ink has-[:checked]:bg-surface has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent"
            >
              <input type="radio" name="plan" value={option.id} defaultChecked={selectedPlan === option.id} className="size-4 accent-ink" />
              <span>
                <span className="block font-semibold">{option.title}</span>
                <span className="block text-sm text-muted">{option.sub}</span>
              </span>
            </label>
          ))}
        </div>
      </fieldset>

      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="Voornaam" name="firstName" autoComplete="given-name" required defaultValue={v.firstName} error={e.firstName} />
        <Field label="Achternaam" name="lastName" autoComplete="family-name" required defaultValue={v.lastName} error={e.lastName} />
      </div>
      <Field label="E-mailadres" name="email" type="email" autoComplete="email" required defaultValue={v.email} error={e.email} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field
          label="Telefoonnummer"
          name="phone"
          type="tel"
          autoComplete="tel"
          defaultValue={v.phone}
          error={e.phone}
          hint="Optioneel, handig voor het plannen van je intake"
        />
        <SelectField label="Je belangrijkste doel" name="goal" options={GOALS} placeholder="Kies je doel" defaultValue={v.goal ?? ""} error={e.goal} required />
      </div>
      <Field
        label="Wachtwoord"
        name="password"
        type="password"
        autoComplete="new-password"
        minLength={8}
        required
        error={e.password}
        hint="Minimaal 8 tekens"
      />
      <Field
        label="Uitnodigingscode"
        name="referralCode"
        defaultValue={v.referralCode ?? referralCode}
        error={e.referralCode}
        placeholder="Bijv. LISA-7K2Q"
        autoCapitalize="characters"
        hint={inviterName ? `Uitgenodigd door ${inviterName}: je krijgt ${friendReward}` : "Optioneel, van een vriend die al traint bij SteynPT"}
      />

      <div className="space-y-3 rounded-xl bg-surface p-4 text-sm">
        <label className="flex gap-3">
          <input type="checkbox" name="terms" defaultChecked={v.terms === "on"} className="mt-0.5 size-4 shrink-0 accent-ink" required />
          <span>
            Ik ga akkoord met de{" "}
            <Link href="/privacy" target="_blank" className="font-semibold underline">
              privacyverklaring
            </Link>{" "}
            en geef toestemming om mijn check-in gegevens (zoals gewicht) te gebruiken voor mijn coaching.
          </span>
        </label>
        {e.terms && <p className="field-error">{e.terms}</p>}
        <label className="flex gap-3">
          <input type="checkbox" name="marketing" defaultChecked={v.marketing === "on"} className="mt-0.5 size-4 shrink-0 accent-ink" />
          <span>Houd me op de hoogte van acties, tips en nieuwe sessies (optioneel).</span>
        </label>
      </div>

      <SubmitButton pendingText="Account aanmaken…">
        Account aanmaken <ArrowRight className="size-4" aria-hidden="true" />
      </SubmitButton>
      <p className="text-center text-sm text-muted">
        Heb je al een account?{" "}
        <Link href="/inloggen" className="font-semibold text-ink underline">
          Log in
        </Link>
      </p>
    </form>
  );
}
