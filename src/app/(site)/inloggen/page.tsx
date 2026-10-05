import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { DemoLogin } from "@/components/DemoLogin";
import { LoginForm } from "@/components/forms/LoginForm";
import { getCurrentUser, safeNextPath } from "@/lib/auth";
import { DEMO_MODE } from "@/lib/demo";

const MELDING: Record<string, string> = {
  verlopen: "Je inlogpoging is verlopen. Log opnieuw in met je e-mailadres en wachtwoord.",
  "te-veel-codes": "Te veel onjuiste codes. Log opnieuw in met je wachtwoord.",
  "2fa-opnieuw": "De tweestapsverificatie is uitgezet. Log opnieuw in om je (nieuwe) telefoon te koppelen.",
};

export const metadata: Metadata = {
  title: "Inloggen",
  robots: { index: false },
};

export default async function LoginPage({ searchParams }: PageProps<"/inloggen">) {
  const { next, demo, melding } = await searchParams;
  const target = safeNextPath(next);
  if (await getCurrentUser()) redirect(target);

  return (
    <AuthShell
      title="Welkom terug"
      intro={<p>Log in voor je afspraken, schema&apos;s, voortgang en check-ins.</p>}
      aside={{
        title: "Blijf in beweging",
        items: ["Plan je volgende training in de agenda", "Bekijk je voortgang en de feedback van Steyn", "Nodig een vriend uit en krijg samen korting"],
      }}
    >
      {DEMO_MODE && (
        <div className="mb-8">
          <DemoLogin missing={demo === "ontbreekt"} />
        </div>
      )}
      {MELDING[String(melding)] && <p className="mb-6 rounded-xl border border-line bg-surface p-4 text-sm">{MELDING[String(melding)]}</p>}
      {/* Zonder next kiest het inloggen zelf: beheer voor Steyn, Mijn omgeving voor klanten. */}
      <LoginForm next={typeof next === "string" ? target : undefined} />
    </AuthShell>
  );
}
