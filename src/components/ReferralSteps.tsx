import { algemeen } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

/** De drie stappen van de vriendenactie, gedeeld door meerdere pagina's. */
export async function ReferralSteps() {
  const { steps } = (await getTexts(algemeen)).vriendenactie;
  return (
    <ol className="grid gap-3">
      {steps.map((step, i) => (
        <li key={i} className="card flex gap-5 p-6">
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
