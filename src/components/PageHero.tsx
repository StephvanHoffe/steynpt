import Image from "next/image";
import type { ReactNode } from "react";
import { HeroHeading } from "./HeroHeading";
import { LogoMark } from "./Logo";

export function PageHero({
  eyebrow,
  title,
  intro,
  image,
  imageAlt = "",
  children,
}: {
  eyebrow: string;
  title: ReactNode;
  intro?: ReactNode;
  image?: string;
  imageAlt?: string;
  children?: ReactNode;
}) {
  return (
    <section className="hero-soft relative overflow-hidden">
      <LogoMark className="pointer-events-none absolute -bottom-24 -left-10 h-[420px] w-auto opacity-[0.03]" />
      <div className={`container-site grid items-center gap-10 py-16 lg:py-24 ${image ? "lg:grid-cols-[1.25fr_1fr]" : ""}`}>
        <div className="animate-rise">
          <HeroHeading eyebrow={eyebrow} title={title} />
          {intro && <div className="lead mt-6 max-w-2xl text-ink/75">{intro}</div>}
          {children && <div className="mt-8 flex flex-col gap-3 sm:flex-row">{children}</div>}
        </div>
        {image && (
          <div className="relative mx-auto w-full max-w-md lg:max-w-none">
            <div className="absolute -inset-3 -z-0 rounded-2xl border border-line" aria-hidden="true" />
            <Image
              src={image}
              alt={imageAlt}
              width={900}
              height={1350}
              priority
              sizes="(min-width: 1024px) 40vw, 90vw"
              className="relative aspect-[4/5] w-full rounded-xl object-cover"
            />
          </div>
        )}
      </div>
    </section>
  );
}
