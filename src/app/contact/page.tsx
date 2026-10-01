import { Clock, MapPin } from "lucide-react";
import type { Metadata } from "next";
import { ContactForm } from "@/components/forms/ContactForm";
import { InstagramIcon } from "@/components/icons";
import { LOCATIONS, ON_LOCATION, SITE, INTERESTS } from "@/lib/site";

export const metadata: Metadata = {
  title: "Contact & gratis kennismaking",
  description:
    "Vraag een gratis kennismaking aan bij SteynPT. We nodigen je graag uit in één van onze studio's in Amsterdam: Gymbase of Workout Amsterdam.",
};

export default async function ContactPage({ searchParams }: PageProps<"/contact">) {
  const { onderwerp } = await searchParams;
  const defaultInterest = INTERESTS.some((i) => i.id === onderwerp) ? (onderwerp as string) : undefined;

  return (
    <section className="hero-soft">
      <div className="container-site grid gap-12 py-16 lg:grid-cols-[1fr_1.1fr] lg:py-24">
        <div className="animate-rise">
          <p className="eyebrow text-rose">Kom direct met Steyn in contact</p>
          <h1 className="display display-xl mt-5">
            Leuk om eens <span className="text-rose">kennis</span> te maken!
          </h1>
          <p className="lead mt-6 max-w-xl text-ink/75">
            We nodigen je graag uit in één van onze studio's voor een gratis kennismaking. We vertellen je meer over onze
            werkwijze, geven je een rondleiding en horen graag meer over jouw verwachtingen en doelen.
          </p>
          <p className="mt-8 flex items-center gap-3 text-ink/85">
            <Clock className="size-5 text-rose" aria-hidden="true" />
            {SITE.responseTime}
          </p>

          <div className="mt-10 grid gap-4 sm:grid-cols-2">
            {LOCATIONS.map((loc) => (
              <a key={loc.name} href={loc.maps} target="_blank" rel="noopener noreferrer" className="card-soft block p-5 transition-colors hover:border-rose/60">
                <MapPin className="size-5 text-rose" aria-hidden="true" />
                <p className="mt-3 font-semibold">{loc.name}</p>
                <p className="mt-1 text-sm text-muted">
                  {loc.street}
                  <br />
                  {loc.city}
                </p>
              </a>
            ))}
          </div>
          <p className="mt-6 max-w-xl text-sm leading-relaxed text-muted">
            <strong className="text-ink">Wist je dat</strong> SteynPT ook trainingen op locatie aanbiedt? {ON_LOCATION}
          </p>
          <a
            href={SITE.instagram.url}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-ink hover:text-rose"
          >
            <InstagramIcon className="size-4" /> Volg {SITE.instagram.handle} op Instagram
          </a>
        </div>

        <div className="rounded-[1.5rem] bg-paper p-6 text-ink sm:p-10">
          <h2 className="display display-sm">Vraag een kennismaking aan</h2>
          <p className="mt-2 text-sm text-muted">Vul je gegevens in, dan nemen we contact met je op om een moment te plannen.</p>
          <div className="relative mt-8">
            <ContactForm defaultInterest={defaultInterest} />
          </div>
        </div>
      </div>
    </section>
  );
}
