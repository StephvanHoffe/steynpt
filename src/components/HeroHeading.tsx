import type { ReactNode } from "react";

/** H1 met de kleine kop erin: zo staat de zoekterm ("Personal training in Amsterdam Oud-West") in de belangrijkste kop, zonder dat de opmaak verandert. */
export function HeroHeading({
  eyebrow,
  title,
  size = "xl",
  eyebrowClass = "text-accent",
  gap = "mt-5",
}: {
  eyebrow: string;
  title: ReactNode;
  size?: "xl" | "lg";
  eyebrowClass?: string;
  gap?: string;
}) {
  return (
    <h1>
      <span className={`eyebrow ${eyebrowClass}`}>{eyebrow}</span>
      <span className="sr-only">: </span>
      <span className={`display display-${size} ${gap} block`}>{title}</span>
    </h1>
  );
}
