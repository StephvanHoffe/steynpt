import { Check } from "lucide-react";
import Link from "next/link";
import type { ComponentProps, ReactNode } from "react";

export function SectionHeading({
  eyebrow,
  title,
  intro,
  align = "left",
  className = "",
}: {
  eyebrow?: string;
  title: ReactNode;
  intro?: ReactNode;
  align?: "left" | "center";
  className?: string;
}) {
  const centered = align === "center";
  return (
    <div className={`${centered ? "mx-auto text-center" : ""} max-w-3xl ${className}`}>
      {eyebrow && <p className={`eyebrow text-accent ${centered ? "justify-center" : ""}`}>{eyebrow}</p>}
      <h2 className="display display-lg mt-4">{title}</h2>
      {intro && <p className="lead mt-5 text-muted">{intro}</p>}
    </div>
  );
}

export function ButtonLink({
  variant = "primary",
  size,
  className = "",
  ...props
}: ComponentProps<typeof Link> & {
  variant?: "primary" | "ink" | "outline";
  size?: "sm";
}) {
  return <Link {...props} className={`btn btn-${variant} ${size === "sm" ? "btn-sm" : ""} ${className}`} />;
}

export function CheckList({ items }: { items: string[] }) {
  return (
    <ul className="space-y-3">
      {items.map((item, i) => (
        <li key={i} className="flex gap-3">
          <span className="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-accent-tint text-accent">
            <Check className="size-3" strokeWidth={3} aria-hidden="true" />
          </span>
          <span className="text-ink/85">{item}</span>
        </li>
      ))}
    </ul>
  );
}
