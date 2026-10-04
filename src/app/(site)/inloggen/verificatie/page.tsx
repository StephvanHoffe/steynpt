import { eq } from "drizzle-orm";
import type { Metadata } from "next";
import { redirect } from "next/navigation";
import QRCode from "qrcode";
import { AuthShell } from "@/components/AuthShell";
import { LoginCodeForm, TwoFactorSetupForm } from "@/components/forms/TwoFactorForms";
import { getLoginChallenge } from "@/lib/auth";
import { db, loginChallenges } from "@/lib/db";
import { formatSecret, generateTotpSecret, otpauthUri } from "@/lib/totp";

export const metadata: Metadata = { title: "Inlogcode", robots: { index: false } };

/** Tweede stap van het inloggen: code invullen, of de tweestapsverificatie eerst instellen. */
export default async function VerificationPage() {
  const pending = await getLoginChallenge();
  if (!pending) redirect("/inloggen?melding=verlopen");
  const { challenge, user } = pending;

  // Net ingesteld (zelfde geheim): de herstelcodes staan nog in beeld, dus dezelfde pagina tonen.
  const justSetUp = Boolean(user.totpEnabledAt && challenge.pendingSecret && challenge.pendingSecret === user.totpSecret);
  if (user.totpEnabledAt && !justSetUp) {
    return (
      <AuthShell
        title="Vul je inlogcode in"
        intro={<p>Open je authenticator-app en vul de 6 cijfers bij SteynPT in. De code wisselt elke 30 seconden.</p>}
        aside={{ title: "Extra beveiligd", items: ["Je wachtwoord alleen is niet genoeg om in te loggen", "De code staat alleen op jouw telefoon", "Telefoon kwijt? Gebruik een herstelcode"] }}
      >
        <LoginCodeForm />
      </AuthShell>
    );
  }

  // Nieuw geheim voor deze tussenstap; pas na de eerste juiste code wordt het aan het account gekoppeld.
  let secret = challenge.pendingSecret;
  if (!secret) {
    secret = generateTotpSecret();
    await db.update(loginChallenges).set({ pendingSecret: secret }).where(eq(loginChallenges.id, challenge.id));
  }
  const svg = await QRCode.toString(otpauthUri(secret, user.email), { type: "svg", margin: 1, errorCorrectionLevel: "M", color: { dark: "#111315", light: "#ffffff" } });

  return (
    <AuthShell
      title="Beveilig je account"
      intro={
        <p>
          Bij SteynPT log je in met je wachtwoord én een code uit een app op je telefoon (tweestapsverificatie). Zo blijven je gegevens, schema&apos;s en
          metingen ook veilig als iemand je wachtwoord weet. Het instellen duurt een minuut.
        </p>
      }
      aside={{
        title: "Eenmalig instellen",
        items: ["Werkt met elke authenticator-app", "Daarna bij het inloggen een code van 6 cijfers", "Herstelcodes voor als je je telefoon kwijt bent"],
      }}
    >
      <TwoFactorSetupForm qr={`data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`} secret={formatSecret(secret)} />
    </AuthShell>
  );
}
