import { ArrowRight } from "lucide-react";
import Link from "next/link";
import { LogoMark } from "./Logo";

export function CtaBand({
  title = "Klaar om te starten?",
  text = "Maak gratis een account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.",
  primary = { href: "/online-coaching", label: "Start online coaching" },
  secondary = { href: "/contact", label: "Gratis kennismaking" },
}: {
  title?: string;
  text?: string;
  primary?: { href: string; label: string };
  secondary?: { href: string; label: string } | null;
}) {
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
