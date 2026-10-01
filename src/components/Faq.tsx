import { ChevronDown } from "lucide-react";

export function Faq({ items, tone = "light" }: { items: { q: string; a: string }[]; tone?: "light" | "dark" }) {
  const dark = tone === "dark";
  return (
    <div className={`divide-y rounded-[1.25rem] border ${dark ? "divide-ink-3 border-ink-3" : "divide-line border-line bg-white"}`}>
      {items.map((item) => (
        <details key={item.q} className="group px-6 py-5 [&_summary::-webkit-details-marker]:hidden">
          <summary className="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold">
            {item.q}
            <ChevronDown className="size-5 shrink-0 transition-transform group-open:rotate-180" aria-hidden="true" />
          </summary>
          <p className={`mt-3 leading-relaxed ${dark ? "text-mist" : "text-muted"}`}>{item.a}</p>
        </details>
      ))}
    </div>
  );
}
