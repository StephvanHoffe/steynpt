import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { LoginForm } from "@/components/forms/LoginForm";
import { getCurrentUser, safeNextPath } from "@/lib/auth";

export const metadata: Metadata = {
  title: "Inloggen",
  robots: { index: false },
};

export default async function LoginPage({ searchParams }: PageProps<"/inloggen">) {
  const { next } = await searchParams;
  const target = safeNextPath(next);
  if (await getCurrentUser()) redirect(target);

  return (
    <AuthShell
      title="Welkom terug"
      intro={<p>Log in om je coaching, check-ins en punten te bekijken.</p>}
      aside={{
        title: "Blijf in beweging",
        items: ["Check deze week in en verdien punten", "Bekijk de feedback van Steyn", "Nodig een vriend uit en word samen sterker"],
      }}
    >
      <LoginForm next={target} />
    </AuthShell>
  );
}
