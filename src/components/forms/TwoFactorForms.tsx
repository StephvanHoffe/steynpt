"use client";

import { Check, Copy, Download, KeyRound, Smartphone } from "lucide-react";
import Link from "next/link";
import { useActionState, useState } from "react";
import {
  confirmTwoFactorSetupAction,
  finishTwoFactorSetupAction,
  regenerateRecoveryCodesAction,
  resetOwnTwoFactorAction,
  verifyLoginCodeAction,
  type CodesState,
} from "@/lib/actions/two-factor";
import type { FormState } from "@/lib/actions/types";
import { Field, FormAlert, SubmitButton } from "./fields";

const codeInput = {
  inputMode: "numeric" as const,
  autoComplete: "one-time-code",
  pattern: "[0-9 ]*",
  maxLength: 7,
  placeholder: "123 456",
  className: "input text-center font-mono text-2xl tracking-[0.3em]",
};

/** Tweede stap bij het inloggen: code uit de app, of een herstelcode als de telefoon er niet is. */
export function LoginCodeForm() {
  const [state, action] = useActionState<FormState, FormData>(verifyLoginCodeAction, {});
  const [recovery, setRecovery] = useState(false);
  const expired = state.error?.includes("opnieuw in");
  return (
    <form action={action} className="grid gap-5" noValidate>
      <FormAlert error={state.error} />
      {expired ? (
        <Link href="/inloggen" className="btn btn-primary w-full">
          Opnieuw inloggen
        </Link>
      ) : (
        <>
          {recovery ? (
            <Field
              key="recovery"
              label="Herstelcode"
              name="code"
              autoComplete="off"
              autoCapitalize="none"
              spellCheck={false}
              placeholder="abcde-fghij"
              className="input font-mono text-lg tracking-wider"
              required
              autoFocus
              error={state.fieldErrors?.code}
              hint="Elke herstelcode werkt één keer."
            />
          ) : (
            <Field key="code" label="Code uit je authenticator-app" name="code" required autoFocus error={state.fieldErrors?.code} {...codeInput} />
          )}
          <SubmitButton pendingText="Controleren…">Inloggen</SubmitButton>
          <button type="button" onClick={() => setRecovery((r) => !r)} className="text-sm font-semibold text-ink underline decoration-accent underline-offset-4">
            {recovery ? "Toch de code uit de app gebruiken" : "Telefoon niet bij de hand? Gebruik een herstelcode"}
          </button>
          <p className="text-center text-xs text-muted">
            Geen toegang meer tot je app en herstelcodes?{" "}
            <Link href="/contact" className="underline">
              Neem contact op
            </Link>
            , dan zet Steyn de tweestapsverificatie voor je terug.
          </p>
        </>
      )}
    </form>
  );
}

/** Herstelcodes één keer tonen, met kopiëren en downloaden. */
export function RecoveryCodes({ codes }: { codes: string[] }) {
  const [copied, setCopied] = useState(false);
  const text = `Herstelcodes SteynPT (elke code werkt één keer)\n\n${codes.join("\n")}\n`;
  return (
    <div className="rounded-xl border border-line bg-white p-5">
      <p className="flex items-center gap-2 font-semibold">
        <KeyRound className="size-5 text-accent" aria-hidden="true" /> Je herstelcodes
      </p>
      <p className="mt-1 text-sm text-muted">
        Bewaar ze op een veilige plek, bijvoorbeeld in je wachtwoordbeheerder. Ben je je telefoon kwijt, dan log je met een van deze codes in. Je ziet
        ze maar één keer.
      </p>
      <ul className="mt-4 grid grid-cols-2 gap-2 font-mono text-base" aria-label="Herstelcodes">
        {codes.map((c) => (
          <li key={c} className="rounded-md bg-surface px-3 py-2 text-center tracking-wider">
            {c}
          </li>
        ))}
      </ul>
      <div className="mt-4 flex flex-wrap gap-2">
        <button
          type="button"
          className="btn btn-sm btn-outline"
          onClick={async () => {
            try {
              await navigator.clipboard.writeText(text);
              setCopied(true);
              setTimeout(() => setCopied(false), 2000);
            } catch {
              window.prompt("Herstelcodes", codes.join(" "));
            }
          }}
        >
          {copied ? <Check className="size-4" aria-hidden="true" /> : <Copy className="size-4" aria-hidden="true" />} {copied ? "Gekopieerd" : "Kopiëren"}
        </button>
        <a className="btn btn-sm btn-outline" download="steynpt-herstelcodes.txt" href={`data:text/plain;charset=utf-8,${encodeURIComponent(text)}`}>
          <Download className="size-4" aria-hidden="true" /> Downloaden
        </a>
      </div>
    </div>
  );
}

/** Tweestapsverificatie instellen: QR-code scannen, eerste code invullen, herstelcodes bewaren. */
export function TwoFactorSetupForm({ qr, secret }: { qr: string; secret: string }) {
  const [state, action] = useActionState<CodesState, FormData>(confirmTwoFactorSetupAction, {});
  const [saved, setSaved] = useState(false);

  if (state.codes) {
    return (
      <div className="grid gap-5">
        <FormAlert success="Tweestapsverificatie staat aan. Voortaan vul je bij het inloggen ook de code uit de app in." />
        <RecoveryCodes codes={state.codes} />
        <label className="flex items-start gap-3 text-sm">
          <input type="checkbox" checked={saved} onChange={(e) => setSaved(e.target.checked)} className="mt-0.5 size-4 accent-ink" />
          Ik heb mijn herstelcodes bewaard
        </label>
        <form action={finishTwoFactorSetupAction}>
          <SubmitButton disabled={!saved} className="btn btn-primary w-full disabled:cursor-not-allowed disabled:opacity-50" pendingText="Inloggen…">
            Verder
          </SubmitButton>
        </form>
      </div>
    );
  }

  return (
    <form action={action} className="grid gap-6" noValidate>
      <FormAlert error={state.error} />
      <ol className="grid gap-6">
        <li className="flex gap-4">
          <span className="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">1</span>
          <div>
            <p className="font-semibold">Installeer een authenticator-app</p>
            <p className="mt-1 text-sm text-muted">
              Bijvoorbeeld Google Authenticator, Microsoft Authenticator, de Wachtwoorden-app op je iPhone of 1Password.
            </p>
          </div>
        </li>
        <li className="flex gap-4">
          <span className="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">2</span>
          <div className="min-w-0">
            <p className="font-semibold">Scan deze QR-code met de app</p>
            <img src={qr} alt="QR-code om SteynPT toe te voegen aan je authenticator-app" width={176} height={176} className="mt-3 rounded-lg border border-line bg-white p-2" />
            <details className="mt-3 text-sm">
              <summary className="cursor-pointer font-medium text-ink underline decoration-accent underline-offset-4">
                <Smartphone className="mr-1 inline size-4" aria-hidden="true" />
                Op deze telefoon? Voer de sleutel handmatig in
              </summary>
              <p className="mt-2 text-muted">Kies in de app voor een sleutel invoeren, met als naam SteynPT:</p>
              <code className="mt-2 block break-all rounded-md bg-surface px-3 py-2 font-mono text-sm tracking-wider" data-totp-secret={secret.replace(/\s/g, "")}>
                {secret}
              </code>
            </details>
          </div>
        </li>
        <li className="flex gap-4">
          <span className="grid size-8 shrink-0 place-items-center rounded-full bg-ink text-sm font-semibold text-white">3</span>
          <div className="min-w-0 flex-1">
            <Field label="Vul de 6 cijfers in die de app toont" name="code" required error={state.fieldErrors?.code} {...codeInput} />
          </div>
        </li>
      </ol>
      <SubmitButton pendingText="Controleren…">Tweestapsverificatie aanzetten</SubmitButton>
    </form>
  );
}

/** Profiel: nieuwe herstelcodes maken. */
export function RegenerateCodesForm() {
  const [state, action] = useActionState<CodesState, FormData>(regenerateRecoveryCodesAction, {});
  if (state.codes) {
    return (
      <div className="grid gap-4">
        <FormAlert success={state.success} />
        <RecoveryCodes codes={state.codes} />
      </div>
    );
  }
  return (
    <form action={action} className="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" noValidate>
      <div className="sm:col-span-2">
        <FormAlert error={state.error} />
      </div>
      <Field label="Code uit je app" name="code" id="regen-code" required error={state.fieldErrors?.code} {...codeInput} className="input font-mono tracking-[0.2em]" />
      <SubmitButton className="btn btn-outline" pendingText="…">
        Nieuwe herstelcodes
      </SubmitButton>
    </form>
  );
}

/** Profiel: andere telefoon. Daarna opnieuw inloggen en de app opnieuw koppelen. */
export function ResetOwnTwoFactorForm() {
  const [state, action] = useActionState<FormState, FormData>(resetOwnTwoFactorAction, {});
  return (
    <form action={action} className="grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end" noValidate>
      <div className="sm:col-span-2">
        <FormAlert error={state.error} />
      </div>
      <Field
        label="Code uit je app of een herstelcode"
        name="code"
        id="reset-code"
        required
        autoComplete="one-time-code"
        className="input font-mono tracking-wider"
        error={state.fieldErrors?.code}
      />
      <SubmitButton className="btn btn-outline" pendingText="…">
        Opnieuw koppelen
      </SubmitButton>
    </form>
  );
}
