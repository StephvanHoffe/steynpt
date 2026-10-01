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
      { href: "/vriend-uitnodigen", label: "Vriend uitnodigen" },
      { href: "/contact", label: "Gratis kennismaking" },
      { href: "/contact", label: "Contact" },
    ],
  },
  {
    title: "Account",
    links: [
      { href: "/registreren", label: "Account aanmaken" },
      { href: "/inloggen", label: "Inloggen" },
      { href: "/account", label: "Mijn omgeving" },
      { href: "/account/agenda", label: "Afspraak maken" },
    ],
  },
];

export function SiteFooter() {
  return (
    <footer className="bg-ink text-white print:hidden">
      <div className="container-site grid gap-12 py-16 lg:grid-cols-[1fr_2.3fr] lg:py-20">
        <div>
          <Logo variant="light" className="h-20 w-auto" />
          <p className="mt-6 max-w-sm text-sm leading-relaxed text-white/65">
            Personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam.
          </p>
          <a
            href={SITE.instagram.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-2 rounded-lg border border-white/20 px-4 py-2 text-sm font-medium transition-colors hover:border-white"
          >
            <InstagramIcon className="size-4" /> Volg {SITE.instagram.handle}
          </a>
        </div>

        <div className="grid gap-10 sm:grid-cols-2 md:grid-cols-4">
          {columns.map((col) => (
            <div key={col.title}>
              <h2 className="text-xs font-semibold uppercase tracking-[0.14em] text-white/50">{col.title}</h2>
              <ul className="mt-4 space-y-2.5 text-sm">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <Link href={link.href} className="text-white/80 transition-colors hover:text-white">
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
          <div>
            <h2 className="text-xs font-semibold uppercase tracking-[0.14em] text-white/50">Locaties</h2>
            <ul className="mt-4 space-y-4 text-sm">
              {LOCATIONS.map((loc) => (
                <li key={loc.name}>
                  <a href={loc.maps} target="_blank" rel="noopener noreferrer" className="group block">
                    <span className="flex items-center gap-1.5 font-semibold">
                      <MapPin className="size-3.5 text-accent-soft" aria-hidden="true" />
                      {loc.name}
                      <ArrowUpRight className="size-3.5 opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" />
                    </span>
                    <span className="mt-1 block text-white/65">
                      {loc.street}
                      <br />
                      {loc.city}
                    </span>
                  </a>
                </li>
              ))}
              <li className="text-white/65">Op locatie &amp; online</li>
            </ul>
          </div>
        </div>
      </div>
      <div className="border-t border-white/10">
        <div className="container-site flex flex-col gap-3 py-6 text-xs text-white/50 sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} SteynPT · Personal Training Amsterdam</p>
          <div className="flex gap-5">
            <Link href="/privacy" className="hover:text-white">
              Privacyverklaring
            </Link>
            <Link href="/vriend-uitnodigen#voorwaarden" className="hover:text-white">
              Voorwaarden vriendenactie
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
