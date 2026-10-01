import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { DemoLogin } from "@/components/DemoLogin";
import { LoginForm } from "@/components/forms/LoginForm";
import { getCurrentUser, safeNextPath } from "@/lib/auth";
import { DEMO_MODE } from "@/lib/demo";

export const metadata: Metadata = {
  title: "Inloggen",
  robots: { index: false },
};

export default async function LoginPage({ searchParams }: PageProps<"/inloggen">) {
  const { next, demo } = await searchParams;
  const target = safeNextPath(next);
  if (await getCurrentUser()) redirect(target);

  return (
    <AuthShell
      title="Welkom terug"
      intro={<p>Log in voor je afspraken, schema&apos;s, voortgang en check-ins.</p>}
      aside={{
        title: "Blijf in beweging",
        items: ["Plan je volgende training in de agenda", "Bekijk je voortgang en de feedback van Steyn", "Nodig een vriend uit en krijg samen 50% korting"],
      }}
    >
      {DEMO_MODE && (
        <div className="mb-8">
          <DemoLogin missing={demo === "ontbreekt"} />
        </div>
      )}
      <LoginForm next={target} />
    </AuthShell>
  );
}
