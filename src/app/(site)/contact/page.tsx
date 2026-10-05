import { Clock, Mail, MapPin, Phone, TramFront } from "lucide-react";
import type { Metadata } from "next";
import { ContactForm } from "@/components/forms/ContactForm";
import { Rich } from "@/components/content/Rich";
import { InstagramIcon } from "@/components/icons";
import { algemeen, contact } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import { INTERESTS, mapsUrl, telHref } from "@/lib/site";
import { HeroHeading } from "@/components/HeroHeading";

export async function generateMetadata(): Promise<Metadata> {
  const { seo } = await getTexts(contact);
  return { title: seo.title, description: seo.description };
}

export default async function ContactPage({ searchParams }: PageProps<"/contact">) {
  const { onderwerp } = await searchParams;
  const defaultInterest = INTERESTS.some((i) => i.id === onderwerp) ? (onderwerp as string) : undefined;
  const [t, { locatie }] = await Promise.all([getTexts(contact), getTexts(algemeen)]);

  return (
    <section className="hero-soft">
      <div className="container-site grid gap-12 py-16 lg:grid-cols-[1fr_1.1fr] lg:py-24">
        <div className="animate-rise">
          <HeroHeading eyebrow={t.hero.eyebrow} title={<Rich text={t.hero.title} />} />
          <p className="lead mt-6 max-w-xl text-ink/75">{t.hero.intro}</p>
          <p className="mt-8 flex items-center gap-3 text-ink/85">
            <Clock className="size-5 text-accent" aria-hidden="true" />
            {locatie.responseTime}
          </p>

          <div className="mt-10 grid gap-4 sm:grid-cols-2">
            <a href={mapsUrl(locatie.street, locatie.city)} target="_blank" rel="noopener noreferrer" className="card-soft block p-5 transition-colors hover:border-accent/60">
              <MapPin className="size-5 text-accent" aria-hidden="true" />
              <p className="mt-3 font-semibold">{locatie.name}</p>
              <p className="mt-1 text-sm text-muted">
                {locatie.street}
                <br />
                {locatie.city}
                {locatie.area && (
                  <>
                    <br />
                    {locatie.area}
                  </>
                )}
              </p>
            </a>
            {locatie.directions && (
              <div className="card-soft p-5">
                <TramFront className="size-5 text-accent" aria-hidden="true" />
                <p className="mt-3 font-semibold">Bereikbaarheid</p>
                <p className="mt-1 text-sm text-muted">{locatie.directions}</p>
              </div>
            )}
            {(locatie.phone || locatie.email) && (
              <div className="card-soft p-5 sm:col-span-2">
                <p className="font-semibold">Direct contact</p>
                <ul className="mt-2 space-y-1.5 text-sm text-muted">
                  {locatie.phone && (
                    <li>
                      <a href={`tel:${telHref(locatie.phone)}`} className="inline-flex items-center gap-2 hover:text-ink">
                        <Phone className="size-4 text-accent" aria-hidden="true" /> {locatie.phone}
                      </a>
                    </li>
                  )}
                  {locatie.email && (
                    <li>
                      <a href={`mailto:${locatie.email}`} className="inline-flex items-center gap-2 hover:text-ink">
                        <Mail className="size-4 text-accent" aria-hidden="true" /> {locatie.email}
                      </a>
                    </li>
                  )}
                </ul>
              </div>
            )}
          </div>
          <p className="mt-6 max-w-xl text-sm leading-relaxed text-muted">
            <strong className="text-ink">{t.hero.tipTitle}</strong> {t.hero.tipText} {locatie.onLocation}
          </p>
          <a
            href={locatie.instagramUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-ink hover:text-accent"
          >
            <InstagramIcon className="size-4" /> Volg {locatie.instagramHandle} op Instagram
          </a>
        </div>

        <div className="rounded-xl bg-paper p-6 text-ink sm:p-10">
          <h2 className="display display-sm">
            <Rich text={t.formulier.title} />
          </h2>
          <p className="mt-2 text-sm text-muted">{t.formulier.intro}</p>
          <div className="relative mt-8">
            <ContactForm defaultInterest={defaultInterest} />
          </div>
        </div>
      </div>
    </section>
  );
}
