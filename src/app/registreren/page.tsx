import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { RegisterForm } from "@/components/forms/RegisterForm";
import { getCurrentUser } from "@/lib/auth";
import { POINTS, welcomePoints } from "@/lib/loyalty";
import { resolveInvitation } from "@/lib/referral";
import { getOnlinePlan } from "@/lib/site";

export const metadata: Metadata = {
  title: "Account aanmaken",
  description: "Maak je gratis SteynPT-account aan voor online coaching en SteynPT Rewards.",
};

export default async function RegisterPage({ searchParams }: PageProps<"/registreren">) {
  if (await getCurrentUser()) redirect("/account");
  const { plan, ref } = await searchParams;
  const invitation = await resolveInvitation(ref);
  const planId = typeof plan === "string" && getOnlinePlan(plan) ? plan : undefined;

  return (
    <AuthShell
      title="Maak je account aan"
      intro={
        <p>
          Gratis en in twee minuten geregeld. Je ontvangt direct <strong className="text-ink">{welcomePoints()} welkomstpunten</strong>
          {invitation ? (
            <>
              {" "}
              plus <strong className="text-ink">{POINTS.invitedBonus} extra</strong> omdat {invitation.firstName} je uitnodigde
            </>
          ) : null}
          .
        </p>
      }
      aside={{
        title: "Alles voor jouw doel op één plek",
        items: [
          "Je online coaching, schema en feedback van Steyn",
          "Wekelijkse check-ins en je voortgang in één overzicht",
          "Punten sparen en inwisselen voor beloningen",
          "Je persoonlijke link om vrienden uit te nodigen",
          "Steyn neemt binnen 24 uur contact op voor je intake",
        ],
      }}
    >
      <RegisterForm plan={planId} referralCode={invitation?.code} inviterName={invitation?.firstName} />
    </AuthShell>
  );
}
