import type { Metadata } from "next";
import { DeleteAccountForm, PasswordForm, ProfileForm } from "@/components/account/ProfileForms";
import { requireUser } from "@/lib/auth";

export const metadata: Metadata = { title: "Mijn profiel" };

export default async function ProfilePage() {
  const user = await requireUser("/account/profiel");
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
        <div className="mt-6">
          <PasswordForm />
        </div>
      </section>
      <section className="mt-6 rounded-xl border border-danger/30 p-6 sm:p-8" aria-labelledby="verwijderen">
        <h2 id="verwijderen" className="display text-2xl">
          Account verwijderen
        </h2>
        <p className="mt-2 text-sm text-muted">Hiermee verwijder je je account en al je gegevens definitief, inclusief je gespaarde punten.</p>
        <div className="mt-6">
          <DeleteAccountForm />
        </div>
      </section>
    </div>
  );
}
