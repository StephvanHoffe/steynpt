import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { RenewPasswordForm } from "@/components/forms/RenewPasswordForm";
import { getCurrentUser, passwordExpired, safeNextPath } from "@/lib/auth";
import { PASSWORD_MAX_AGE_DAYS, passwordExpiresAt } from "@/lib/totp";

export const metadata: Metadata = { title: "Nieuw wachtwoord", robots: { index: false } };

const dateFmt = new Intl.DateTimeFormat("nl-NL", { day: "numeric", month: "long", year: "numeric", timeZone: "Europe/Amsterdam" });

/** Om de 8 weken een nieuw wachtwoord; bij een verlopen wachtwoord kom je hier automatisch terecht. */
export default async function RenewPasswordPage({ searchParams }: PageProps<"/wachtwoord-vernieuwen">) {
  const { next } = await searchParams;
  const user = await getCurrentUser();
  if (!user) redirect("/inloggen?next=/wachtwoord-vernieuwen");
  const expired = passwordExpired(user);
  const changed = user.passwordChangedAt ?? user.createdAt;
  const weeks = PASSWORD_MAX_AGE_DAYS / 7;

  return (
    <AuthShell
      title={expired ? "Tijd voor een nieuw wachtwoord" : "Nieuw wachtwoord kiezen"}
      intro={
        <p>
          {expired
            ? `Voor je veiligheid vraagt SteynPT om de ${weeks} weken een nieuw wachtwoord. Je vorige wachtwoord is van ${dateFmt.format(changed)}.`
            : `Je huidige wachtwoord verloopt op ${dateFmt.format(passwordExpiresAt(changed))}. Je kunt het nu al vernieuwen.`}
        </p>
      }
      aside={{
        title: "Een sterk wachtwoord",
        items: ["Minimaal 8 tekens, liever een zin van een paar woorden", "Niet hetzelfde als je vorige wachtwoord", "Gebruik het nergens anders"],
      }}
    >
      <RenewPasswordForm next={safeNextPath(next, user.role === "admin" ? "/admin" : "/account")} />
    </AuthShell>
  );
}
