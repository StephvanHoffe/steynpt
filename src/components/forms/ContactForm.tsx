"use client";

import { Send } from "lucide-react";
import { useActionState } from "react";
import { contactAction } from "@/lib/actions/contact";
import type { FormState } from "@/lib/actions/types";
import { INTERESTS } from "@/lib/site";
import { Field, FormAlert, SelectField, SubmitButton } from "./fields";

export function ContactForm({ defaultInterest }: { defaultInterest?: string }) {
  const [state, action] = useActionState<FormState, FormData>(contactAction, {});
  const v = state.values ?? {};
  const e = state.fieldErrors ?? {};

  if (state.success) {
    return (
      <div className="space-y-4">
        <FormAlert success={state.success} />
        <p className="text-sm text-muted">
          Benieuwd naar online coaching? Maak alvast je gratis account aan, dan sta je direct klaar.
        </p>
      </div>
    );
  }

  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />
      <Field label="Volledige naam" name="name" autoComplete="name" required defaultValue={v.name} error={e.name} />
      <div className="grid gap-5 sm:grid-cols-2">
        <Field label="E-mailadres" name="email" type="email" autoComplete="email" required defaultValue={v.email} error={e.email} />
        <Field label="Telefoonnummer" name="phone" type="tel" autoComplete="tel" defaultValue={v.phone} error={e.phone} hint="Optioneel, dan bellen we je" />
      </div>
      <SelectField
        label="Ik wil een gratis kennismaking voor"
        name="interest"
        options={INTERESTS}
        placeholder="Maak een keuze"
        defaultValue={v.interest ?? defaultInterest ?? ""}
        error={e.interest}
        required
      />
      <div>
        <label htmlFor="f-message" className="label">
          Bericht <span className="font-normal text-muted">(optioneel)</span>
        </label>
        <textarea id="f-message" name="message" className="input" defaultValue={v.message} placeholder="Vertel kort over je doel of vraag" />
      </div>
      <div aria-hidden="true" className="absolute left-[-9999px]">
        <label>
          Website <input type="text" name="website" tabIndex={-1} autoComplete="off" />
        </label>
      </div>
      <SubmitButton pendingText="Versturen…">
        Verstuur aanvraag <Send className="size-4" aria-hidden="true" />
      </SubmitButton>
      <p className="text-xs text-muted">
        We gebruiken je gegevens alleen om contact met je op te nemen. Zie onze{" "}
        <a href="/privacy" className="underline">
          privacyverklaring
        </a>
        .
      </p>
    </form>
  );
}
