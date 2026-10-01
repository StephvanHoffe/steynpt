import { Check } from "lucide-react";
import Link from "next/link";
import { ONLINE_PLANS } from "@/lib/site";

export function OnlinePlans({ referral }: { referral?: string | null }) {
  return (
    <div className="grid gap-5 lg:grid-cols-3">
      {ONLINE_PLANS.map((plan) => {
        const featured = plan.featured;
        const href = `/registreren?plan=${plan.id}${referral ? `&ref=${encodeURIComponent(referral)}` : ""}`;
        return (
          <article
            key={plan.id}
            className={`relative flex flex-col rounded-[1.25rem] border p-7 ${featured ? "border-rose bg-petal" : "border-line bg-white"}`}
          >
            {featured && (
              <span className="absolute -top-3 left-7 rounded-full bg-rose px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white">
                Meest gekozen
              </span>
            )}
            <h3 className="display text-4xl">{plan.name}</h3>
            <p className={`mt-2 text-sm ${featured ? "text-ink/80" : "text-muted"}`}>{plan.tagline}</p>
            <p className="mt-6 flex items-baseline gap-1">
              <span className="text-lg font-semibold">€</span>
              <span className="display text-6xl">{plan.price}</span>
              <span className={`ml-1 text-sm ${featured ? "text-ink/80" : "text-muted"}`}>per maand</span>
            </p>
            <ul className="mt-6 flex-1 space-y-2.5 text-sm">
              {plan.features.map((f) => (
                <li key={f} className="flex gap-2.5">
                  <Check className="mt-0.5 size-4 shrink-0 text-rose" strokeWidth={2.5} aria-hidden="true" />
                  <span>{f}</span>
                </li>
              ))}
            </ul>
            <Link href={href} className={`btn mt-7 w-full ${featured ? "btn-primary" : "btn-outline bg-white"}`}>
              Kies {plan.name}
            </Link>
          </article>
        );
      })}
    </div>
  );
}
