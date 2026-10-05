import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { algemeen } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import { LogoMark } from "./Logo";

/** Zwart blok onderaan een pagina. Zonder teksten: de standaard afsluiter uit het tekstbeheer ("Op elke pagina"). */
export async function CtaBand(props: {
  title?: string;
  text?: string;
  primary?: { href: string; label: string };
  secondary?: { href: string; label: string } | null;
}) {
  const fallback = (await getTexts(algemeen)).afsluiter;
  const {
    title = fallback.title,
    text = fallback.text,
    primary = { href: "/online-coaching", label: fallback.primary },
    secondary = { href: "/contact", label: fallback.secondary },
  } = props;
  return (
    <section className="container-site py-16 lg:py-24">
      <div className="relative overflow-hidden rounded-2xl bg-ink px-6 py-14 text-white sm:px-12 lg:px-16 lg:py-20">
        <LogoMark variant="light" className="pointer-events-none absolute -right-6 -top-10 h-[130%] w-auto opacity-[0.06]" />
        <div className="relative max-w-2xl">
          <h2 className="display display-lg">{title}</h2>
          <p className="lead mt-5 text-white/75">{text}</p>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link href={primary.href} className="btn bg-white text-ink hover:bg-surface">
              {primary.label} <ArrowRight className="size-4" aria-hidden="true" />
            </Link>
            {secondary && (
              <Link href={secondary.href} className="btn btn-on-dark">
                {secondary.label}
              </Link>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
