import { Quote } from "lucide-react";
import { algemeen } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";

export async function Reviews() {
  const { reviews } = (await getTexts(algemeen)).reviews;
  return (
    <div className="grid gap-5 md:grid-cols-2">
      {reviews.map((r, i) => (
        <figure key={i} className="card flex flex-col p-8">
          <Quote className="size-8 text-accent" aria-hidden="true" />
          <blockquote className="mt-5 flex-1 text-xl leading-snug font-medium text-pretty">“{r.quote}”</blockquote>
          <figcaption className="mt-6 flex items-center gap-3 border-t border-line pt-5">
            <span className="grid size-10 place-items-center rounded-full bg-accent-tint text-sm font-bold text-ink">
              {r.name
                .split(" ")
                .filter((p) => /^[A-Z]/.test(p))
                .map((p) => p[0])
                .slice(0, 2)
                .join("")}
            </span>
            <span>
              <span className="block font-semibold">{r.name}</span>
              <span className="block text-sm text-muted">{r.role}</span>
            </span>
          </figcaption>
        </figure>
      ))}
    </div>
  );
}
