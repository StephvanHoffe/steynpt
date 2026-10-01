"use client";

import { ArrowRight, Menu, User, X } from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { NAV } from "@/lib/site";
import { Logo } from "./Logo";

export function SiteHeader({ announcement }: { announcement: { text: string; href: string } | null }) {
  const [open, setOpen] = useState(false);
  const pathname = usePathname();

  useEffect(() => {
    if (!open) return;
    document.body.style.overflow = "hidden";
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && setOpen(false);
    window.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", onKey);
    };
  }, [open]);

  return (
    <header className="sticky top-0 z-50 print:hidden">
      {announcement && (
        <Link
          href={announcement.href}
          className="group block bg-ink px-4 py-2 text-center text-[13px] font-medium text-white"
        >
          <span className="mr-2 rounded bg-accent px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-white">Nieuw</span>
          {announcement.text}
          <ArrowRight className="ml-1 inline size-3.5 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
        </Link>
      )}
      <div className="border-b border-line bg-white text-ink">
        <div className="container-site flex h-[72px] items-center justify-between gap-6">
          <Link href="/" className="shrink-0" aria-label="SteynPT home">
            <Logo className="h-11 w-auto" priority />
          </Link>

          <nav aria-label="Hoofdmenu" className="hidden xl:block">
            <ul className="flex items-center gap-1">
              {NAV.filter((item) => item.desktop !== false).map((item) => {
                const active = pathname === item.href;
                return (
                  <li key={item.href}>
                    <Link
                      href={item.href}
                      aria-current={active ? "page" : undefined}
                      className={`relative whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium transition-colors ${
                        active ? "text-ink" : "text-ink/70 hover:text-ink"
                      }`}
                    >
                      {item.label}
                      {item.highlight && <span className="absolute right-1 top-1.5 size-1.5 rounded-full bg-ink" />}
                      {active && <span className="absolute inset-x-3 -bottom-0.5 h-0.5 rounded bg-ink" />}
                    </Link>
                  </li>
                );
              })}
            </ul>
          </nav>

          <div className="flex items-center gap-2">
            <Link
              href="/account"
              className="hidden items-center gap-2 whitespace-nowrap rounded-full px-3 py-2 text-sm font-medium text-ink/80 transition-colors hover:text-ink sm:inline-flex"
              aria-label="Mijn account"
            >
              <User className="size-4" aria-hidden="true" />
              <span className="xl:hidden 2xl:inline">Mijn account</span>
            </Link>
            <Link href="/online-coaching" className="btn btn-primary btn-sm hidden sm:inline-flex">
              Start online coaching
            </Link>
            <button
              type="button"
              onClick={() => setOpen(true)}
              className="grid size-11 place-items-center rounded-full border border-line bg-white xl:hidden"
              aria-expanded={open}
              aria-controls="mobile-menu"
              aria-label="Menu openen"
            >
              <Menu className="size-5" />
            </button>
          </div>
        </div>
      </div>

      {open && (
        <div
          id="mobile-menu"
          role="dialog"
          aria-modal="true"
          aria-label="Menu"
          className="fixed inset-0 z-[60] overflow-y-auto bg-paper text-ink xl:hidden"
          onClick={(e) => (e.target as HTMLElement).closest("a") && setOpen(false)}
        >
          <div className="container-site flex h-[72px] items-center justify-between border-b border-ink/10">
            <Link href="/" aria-label="SteynPT home">
              <Logo className="h-11 w-auto" />
            </Link>
            <button
              type="button"
              onClick={() => setOpen(false)}
              className="grid size-11 place-items-center rounded-full border border-ink/15"
              aria-label="Menu sluiten"
            >
              <X className="size-5" />
            </button>
          </div>
          <nav aria-label="Mobiel menu" className="container-site pb-10 pt-4">
            <ul className="divide-y divide-ink/10">
              {[...NAV, { href: "/contact", label: "Contact" }].map((item) => (
                <li key={item.href}>
                  <Link href={item.href} className="flex items-center justify-between py-4">
                    <span className="display text-2xl">{item.label}</span>
                    {item.highlight ? (
                      <span className="rounded bg-accent px-1.5 py-0.5 text-xs font-semibold text-white">Nieuw</span>
                    ) : (
                      <ArrowRight className="size-5 text-ink/40" aria-hidden="true" />
                    )}
                  </Link>
                </li>
              ))}
            </ul>
            <div className="mt-8 grid gap-3">
              <Link href="/online-coaching" className="btn btn-primary">
                Start online coaching
              </Link>
              <Link href="/account" className="btn btn-outline">
                <User className="size-4" aria-hidden="true" /> Mijn account
              </Link>
            </div>
          </nav>
        </div>
      )}
    </header>
  );
}
