import { Check } from "lucide-react";
import Link from "next/link";
import { pakketten } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import type { PriceCard as PriceCardType } from "@/lib/site";

export async function PriceCard({ card, cta, href = "/contact" }: { card: PriceCardType; cta: string; href?: string }) {
  const { labels } = await getTexts(pakketten);
  const featured = card.featured;
  return (
    <article
      className={`relative flex flex-col rounded-xl border p-7 transition-transform duration-300 hover:-translate-y-1 ${
        featured ? "border-ink bg-white ring-1 ring-ink shadow-lg shadow-ink/5" : "border-line bg-white"
      }`}
    >
      {featured && (
        <span className="absolute -top-3 left-7 rounded bg-ink px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">
          {labels.featured}
        </span>
      )}
      <p className={`text-xs font-semibold uppercase tracking-[0.14em] ${featured ? "text-accent" : "text-muted"}`}>{card.label}</p>
      <h3 className="display mt-2 text-2xl hyphens-auto">{card.name}</h3>
      <p className="mt-6 flex items-baseline gap-1">
        <span className="text-lg font-semibold">€</span>
        <span className="display text-6xl">{card.price}</span>
        <span className="text-lg font-semibold text-muted">,-</span>
        {card.unit && <span className="ml-1 text-sm text-muted">{card.unit}</span>}
      </p>
      <ul className="mt-6 flex-1 space-y-2.5 text-sm">
        {card.features.map((f, i) => (
          <li key={i} className="flex gap-2.5">
            <Check className="mt-0.5 size-4 shrink-0 text-accent" strokeWidth={2.5} aria-hidden="true" />
            <span className="text-ink/85">{f}</span>
          </li>
        ))}
      </ul>
      {card.note && <p className="mt-5 text-xs text-muted">{card.note}</p>}
      <Link href={href} className={`btn mt-7 w-full ${featured ? "btn-primary" : "btn-outline bg-white"}`}>
        {cta}
      </Link>
    </article>
  );
}
