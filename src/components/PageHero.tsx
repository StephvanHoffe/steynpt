import Image from "next/image";
import type { ReactNode } from "react";
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
    <section className="grain relative overflow-hidden bg-ink text-paper">
      <LogoMark className="pointer-events-none absolute -bottom-24 -left-10 h-[420px] w-auto opacity-[0.04]" />
      <div className={`container-site grid items-center gap-10 py-16 lg:py-24 ${image ? "lg:grid-cols-[1.25fr_1fr]" : ""}`}>
        <div className="animate-rise">
          <p className="eyebrow text-volt">{eyebrow}</p>
          <h1 className="display display-xl mt-5">{title}</h1>
          {intro && <div className="lead mt-6 max-w-2xl text-paper/75">{intro}</div>}
          {children && <div className="mt-8 flex flex-col gap-3 sm:flex-row">{children}</div>}
        </div>
        {image && (
          <div className="relative mx-auto w-full max-w-md lg:max-w-none">
            <div className="absolute -inset-3 -z-0 rotate-2 rounded-[1.75rem] border border-volt/40" aria-hidden="true" />
            <Image
              src={image}
              alt={imageAlt}
              width={900}
              height={1350}
              priority
              sizes="(min-width: 1024px) 40vw, 90vw"
              className="relative aspect-[4/5] w-full rounded-[1.5rem] object-cover"
            />
          </div>
        )}
      </div>
    </section>
  );
}
