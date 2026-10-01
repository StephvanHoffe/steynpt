import { Check } from "lucide-react";
import Link from "next/link";
import type { PriceCard as PriceCardType } from "@/lib/site";

export function PriceCard({ card, cta = "Plan een afspraak", href = "/contact" }: { card: PriceCardType; cta?: string; href?: string }) {
  const featured = card.featured;
  return (
    <article
      className={`relative flex flex-col rounded-[1.25rem] border p-7 transition-transform duration-300 hover:-translate-y-1 ${
        featured ? "border-ink bg-ink text-paper" : "border-line bg-white"
      }`}
    >
      {featured && (
        <span className="absolute -top-3 left-7 rounded-full bg-volt px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-ink">
          Meest gekozen
        </span>
      )}
      <p className={`text-xs font-semibold uppercase tracking-[0.14em] ${featured ? "text-volt" : "text-muted"}`}>{card.label}</p>
      <h3 className="display mt-2 text-3xl">{card.name}</h3>
      <p className="mt-6 flex items-baseline gap-1">
        <span className="text-lg font-semibold">€</span>
        <span className="display text-6xl">{card.price}</span>
        <span className={`text-lg font-semibold ${featured ? "text-paper/70" : "text-muted"}`}>,-</span>
        {card.unit && <span className={`ml-1 text-sm ${featured ? "text-paper/70" : "text-muted"}`}>{card.unit}</span>}
      </p>
      <ul className="mt-6 flex-1 space-y-2.5 text-sm">
        {card.features.map((f) => (
          <li key={f} className="flex gap-2.5">
            <Check className={`mt-0.5 size-4 shrink-0 ${featured ? "text-volt" : "text-ink"}`} strokeWidth={2.5} aria-hidden="true" />
            <span className={featured ? "text-paper/85" : "text-ink/80"}>{f}</span>
          </li>
        ))}
      </ul>
      {card.note && <p className={`mt-5 text-xs ${featured ? "text-paper/60" : "text-muted"}`}>{card.note}</p>}
      <Link href={href} className={`btn mt-7 w-full ${featured ? "btn-volt" : "btn-ink"}`}>
        {cta}
      </Link>
    </article>
  );
}
