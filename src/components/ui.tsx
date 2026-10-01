import { Check } from "lucide-react";
import Link from "next/link";
import type { ComponentProps, ReactNode } from "react";

export function SectionHeading({
  eyebrow,
  title,
  intro,
  align = "left",
  tone = "light",
  className = "",
}: {
  eyebrow?: string;
  title: ReactNode;
  intro?: ReactNode;
  align?: "left" | "center";
  tone?: "light" | "dark";
  className?: string;
}) {
  const centered = align === "center";
  return (
    <div className={`${centered ? "mx-auto text-center" : ""} max-w-3xl ${className}`}>
      {eyebrow && (
        <p className={`eyebrow ${tone === "dark" ? "text-volt" : "text-muted"} ${centered ? "justify-center" : ""}`}>
          {eyebrow}
        </p>
      )}
      <h2 className="display display-lg mt-4">{title}</h2>
      {intro && <p className={`lead mt-5 ${tone === "dark" ? "text-mist" : "text-muted"}`}>{intro}</p>}
    </div>
  );
}

export function ButtonLink({
  variant = "volt",
  size,
  className = "",
  ...props
}: ComponentProps<typeof Link> & {
  variant?: "volt" | "ink" | "outline" | "outline-light";
  size?: "sm";
}) {
  return <Link {...props} className={`btn btn-${variant} ${size === "sm" ? "btn-sm" : ""} ${className}`} />;
}

export function CheckList({ items, tone = "light" }: { items: string[]; tone?: "light" | "dark" }) {
  return (
    <ul className="space-y-3">
      {items.map((item) => (
        <li key={item} className="flex gap-3">
          <span
            className={`mt-0.5 grid size-5 shrink-0 place-items-center rounded-full ${tone === "dark" ? "bg-volt text-ink" : "bg-ink text-volt"}`}
          >
            <Check className="size-3" strokeWidth={3} aria-hidden="true" />
          </span>
          <span className={tone === "dark" ? "text-paper/85" : "text-ink/85"}>{item}</span>
        </li>
      ))}
    </ul>
  );
}

export function Badge({ children, tone = "volt" }: { children: ReactNode; tone?: "volt" | "ink" | "sand" }) {
  const tones = {
    volt: "bg-volt text-ink",
    ink: "bg-ink text-paper",
    sand: "bg-sand text-ink",
  };
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wider ${tones[tone]}`}>
      {children}
    </span>
  );
}
