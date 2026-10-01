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
      <div className="relative overflow-hidden rounded-[2rem] bg-petal px-6 py-14 sm:px-12 lg:px-16 lg:py-20">
        <LogoMark variant="dark" className="pointer-events-none absolute -right-6 -top-10 h-[130%] w-auto opacity-[0.05]" />
        <div className="relative max-w-2xl">
          <h2 className="display display-lg">{title}</h2>
          <p className="lead mt-5 text-ink/75">{text}</p>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link href={primary.href} className="btn btn-primary">
              {primary.label} <ArrowRight className="size-4" aria-hidden="true" />
            </Link>
            {secondary && (
              <Link href={secondary.href} className="btn btn-outline">
                {secondary.label}
              </Link>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}
