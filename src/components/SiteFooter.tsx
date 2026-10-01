import { ArrowUpRight, MapPin } from "lucide-react";
import Link from "next/link";
import { LOCATIONS, SITE } from "@/lib/site";
import { InstagramIcon } from "./icons";
import { Logo } from "./Logo";

const columns = [
  {
    title: "Aanbod",
    links: [
      { href: "/online-coaching", label: "Online coaching" },
      { href: "/personal-training", label: "Personal training" },
      { href: "/personal-training#topsport", label: "Topsport & specifieke doelen" },
      { href: "/ademcoaching", label: "Ademcoaching" },
      { href: "/voedingscoaching", label: "Voedingscoaching" },
      { href: "/small-group-training", label: "Small group training" },
    ],
  },
  {
    title: "SteynPT",
    links: [
      { href: "/over-steyn", label: "Over Steyn" },
      { href: "/tarieven", label: "Tarieven" },
      { href: "/rewards", label: "Rewards & vrienden" },
      { href: "/contact", label: "Gratis kennismaking" },
      { href: "/contact", label: "Contact" },
    ],
  },
  {
    title: "Account",
    links: [
      { href: "/registreren", label: "Account aanmaken" },
      { href: "/inloggen", label: "Inloggen" },
      { href: "/account", label: "Mijn dashboard" },
    ],
  },
];

export function SiteFooter() {
  return (
    <footer className="bg-sand text-ink">
      <div className="zigzag opacity-70" aria-hidden="true" />
      <div className="container-site grid gap-12 py-16 lg:grid-cols-[1fr_2.3fr] lg:py-20">
        <div>
          <Logo className="h-24 w-auto" />
          <p className="mt-6 max-w-sm text-sm leading-relaxed text-muted">
            Personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam. Samen werken we aan een
            sterkere, gezondere jij.
          </p>
          <a
            href={SITE.instagram.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-2 rounded-full border border-ink/15 px-4 py-2 text-sm font-medium transition-colors hover:border-rose hover:text-rose"
          >
            <InstagramIcon className="size-4" /> Volg {SITE.instagram.handle}
          </a>
        </div>

        <div className="grid gap-10 sm:grid-cols-2 md:grid-cols-4">
          {columns.map((col) => (
            <div key={col.title}>
              <h2 className="text-xs font-semibold uppercase tracking-[0.16em] text-muted">{col.title}</h2>
              <ul className="mt-4 space-y-2.5 text-sm">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <Link href={link.href} className="text-ink/85 transition-colors hover:text-rose">
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
          <div>
            <h2 className="text-xs font-semibold uppercase tracking-[0.16em] text-muted">Locaties</h2>
            <ul className="mt-4 space-y-4 text-sm">
              {LOCATIONS.map((loc) => (
                <li key={loc.name}>
                  <a href={loc.maps} target="_blank" rel="noopener noreferrer" className="group block">
                    <span className="flex items-center gap-1.5 font-semibold text-ink">
                      <MapPin className="size-3.5 text-rose" aria-hidden="true" />
                      {loc.name}
                      <ArrowUpRight className="size-3.5 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
                    </span>
                    <span className="mt-1 block text-ink/70">
                      {loc.street}
                      <br />
                      {loc.city}
                    </span>
                  </a>
                </li>
              ))}
              <li className="text-ink/70">Op locatie &amp; online</li>
            </ul>
          </div>
        </div>
      </div>
      <div className="border-t border-ink/10">
        <div className="container-site flex flex-col gap-3 py-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} SteynPT · Personal Training Amsterdam</p>
          <div className="flex gap-5">
            <Link href="/privacy" className="hover:text-ink">
              Privacyverklaring
            </Link>
            <Link href="/rewards#voorwaarden" className="hover:text-ink">
              Voorwaarden Rewards
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
