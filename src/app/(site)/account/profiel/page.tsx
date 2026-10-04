import type { Metadata } from "next";
import { ShieldCheck } from "lucide-react";
import { DeleteAccountForm, PasswordForm, ProfileForm } from "@/components/account/ProfileForms";
import { RegenerateCodesForm, ResetOwnTwoFactorForm } from "@/components/forms/TwoFactorForms";
import { requireUser } from "@/lib/auth";
import { passwordDaysLeft, passwordExpiresAt } from "@/lib/totp";
import { remainingRecoveryCodes } from "@/lib/two-factor";

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", year: "numeric", timeZone: "Europe/Amsterdam" });

export const metadata: Metadata = { title: "Mijn profiel" };

export default async function ProfilePage() {
  const user = await requireUser("/account/profiel");
  const changed = user.passwordChangedAt ?? user.createdAt;
  const daysLeft = passwordDaysLeft(changed);
  const codesLeft = user.totpEnabledAt ? await remainingRecoveryCodes(user.id) : 0;
  return (
    <div className="container-site max-w-3xl py-10 lg:py-14">
      <h1 className="display display-lg">Mijn profiel</h1>
      <section className="card mt-8 p-6 sm:p-8" aria-labelledby="gegevens">
        <h2 id="gegevens" className="display text-2xl">
          Gegevens
        </h2>
        <div className="mt-6">
          <ProfileForm
            profile={{
              firstName: user.firstName,
              lastName: user.lastName,
              email: user.email,
              phone: user.phone,
              goal: user.goal,
              marketingOptIn: user.marketingOptIn,
            }}
          />
        </div>
      </section>
      <section className="card mt-6 p-6 sm:p-8" aria-labelledby="wachtwoord">
        <h2 id="wachtwoord" className="display text-2xl">
          Wachtwoord
        </h2>
        <p className={`mt-2 text-sm ${daysLeft <= 7 ? "font-semibold text-danger" : "text-muted"}`}>
          Om de 8 weken kies je een nieuw wachtwoord. Je huidige wachtwoord verloopt op {dateFmt.format(passwordExpiresAt(changed))}
          {daysLeft <= 7 ? ` (over ${daysLeft} ${daysLeft === 1 ? "dag" : "dagen"})` : ""}.
        </p>
        <div className="mt-6">
          <PasswordForm />
        </div>
      </section>
      <section className="card mt-6 p-6 sm:p-8" aria-labelledby="tweestaps">
        <h2 id="tweestaps" className="display text-2xl">
          Tweestapsverificatie
        </h2>
        {user.totpEnabledAt ? (
          <>
            <p className="mt-2 flex items-start gap-2 text-sm">
              <ShieldCheck className="size-5 shrink-0 text-success" aria-hidden="true" />
              <span>
                Staat aan sinds {dateFmt.format(user.totpEnabledAt)}. Je hebt nog <strong>{codesLeft}</strong> ongebruikte{" "}
                {codesLeft === 1 ? "herstelcode" : "herstelcodes"}.
              </span>
            </p>
            <div className="mt-6 grid gap-8">
              <div>
                <h3 className="font-semibold">Nieuwe herstelcodes</h3>
                <p className="mb-3 mt-1 text-sm text-muted">Herstelcodes kwijt of bijna op? Maak nieuwe; de oude werken dan niet meer.</p>
                <RegenerateCodesForm />
              </div>
              <div>
                <h3 className="font-semibold">Nieuwe telefoon</h3>
                <p className="mb-3 mt-1 text-sm text-muted">
                  Hiermee ontkoppel je de huidige app en log je uit. Bij het inloggen koppel je daarna je nieuwe telefoon.
                </p>
                <ResetOwnTwoFactorForm />
              </div>
            </div>
          </>
        ) : (
          <p className="mt-2 text-sm text-muted">Niet nodig voor dit voorbeeldaccount in de demo.</p>
        )}
      </section>
      <section className="mt-6 rounded-xl border border-danger/30 p-6 sm:p-8" aria-labelledby="verwijderen">
        <h2 id="verwijderen" className="display text-2xl">
          Account verwijderen
        </h2>
        <p className="mt-2 text-sm text-muted">Hiermee verwijder je je account en al je gegevens definitief, inclusief je afspraken, metingen en schema&apos;s.</p>
        <div className="mt-6">
          <DeleteAccountForm />
        </div>
      </section>
    </div>
  );
}
