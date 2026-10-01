import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { RegisterForm } from "@/components/forms/RegisterForm";
import { getCurrentUser } from "@/lib/auth";
import { resolveInvitation } from "@/lib/referral";
import { REFERRAL } from "@/lib/referral-program";
import { getOnlinePlan } from "@/lib/site";

export const metadata: Metadata = {
  title: "Account aanmaken",
  description: "Maak je gratis SteynPT-account aan voor online coaching, je schema's, voortgang en afspraken.",
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
          Gratis en in twee minuten geregeld.
          {invitation && (
            <>
              {" "}
              Omdat {invitation.firstName} je uitnodigde, krijg je <strong className="text-ink">{REFERRAL.friendReward}</strong>.
            </>
          )}
        </p>
      }
      aside={{
        title: "Alles voor jouw doel op één plek",
        items: [
          "Je online coaching, schema en feedback van Steyn",
          "Je metingen en voortgang in één overzicht",
          "Zelf afspraken inplannen in de agenda van Steyn",
          "Je persoonlijke link om vrienden uit te nodigen",
          "Steyn neemt binnen 24 uur contact op voor je intake",
        ],
      }}
    >
      <RegisterForm plan={planId} referralCode={invitation?.code} inviterName={invitation?.firstName} />
    </AuthShell>
  );
}
