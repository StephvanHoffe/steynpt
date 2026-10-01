import { REFERRAL } from "@/lib/referral-program";

/** De drie stappen van de vriendenactie, gedeeld door meerdere pagina's. */
export function ReferralSteps() {
  const steps = [
    { title: "Deel je persoonlijke link", text: "Je vindt hem in Mijn omgeving en deelt hem met één tik via WhatsApp of e-mail." },
    { title: "Je vriend meldt zich aan", text: `Via jouw link krijgt je vriend ${REFERRAL.friendReward}.` },
    { title: "Je vriend start met online coaching", text: `Jij krijgt dan ${REFERRAL.referrerReward}. Steyn verrekent het met je volgende factuur.` },
  ];
  return (
    <ol className="grid gap-3">
      {steps.map((step, i) => (
        <li key={step.title} className="card flex gap-5 p-6">
          <span className="grid size-10 shrink-0 place-items-center rounded-lg bg-ink text-sm font-semibold text-white">{i + 1}</span>
          <span>
            <span className="block font-semibold">{step.title}</span>
            <span className="mt-1 block text-muted">{step.text}</span>
          </span>
        </li>
      ))}
    </ol>
  );
}
