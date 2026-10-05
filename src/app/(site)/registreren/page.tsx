import type { Metadata } from "next";
import { redirect } from "next/navigation";
import { AuthShell } from "@/components/AuthShell";
import { RegisterForm } from "@/components/forms/RegisterForm";
import { getCurrentUser } from "@/lib/auth";
import { resolveInvitation } from "@/lib/referral";
import { algemeen } from "@/lib/content/registry";
import { getOnlinePlans, getTexts } from "@/lib/content/texts";
import { getOnlinePlan } from "@/lib/site";

export const metadata: Metadata = {
  title: "Account aanmaken",
  description: "Maak je gratis SteynPT-account aan voor online coaching, je schema's, voortgang en afspraken.",
};

export default async function RegisterPage({ searchParams }: PageProps<"/registreren">) {
  if (await getCurrentUser()) redirect("/account");
  const { plan, ref } = await searchParams;
  const [invitation, plans, { vriendenactie }] = await Promise.all([resolveInvitation(ref), getOnlinePlans(), getTexts(algemeen)]);
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
              Omdat {invitation.firstName} je uitnodigde, krijg je <strong className="text-ink">{vriendenactie.friendReward}</strong>.
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
      <RegisterForm
        plan={planId}
        plans={plans.map(({ id, name, price }) => ({ id, name, price }))}
        friendReward={vriendenactie.friendReward}
        referralCode={invitation?.code}
        inviterName={invitation?.firstName}
      />
    </AuthShell>
  );
}
