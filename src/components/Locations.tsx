import { ArrowUpRight, MapPin, Smartphone, Trees } from "lucide-react";
import { algemeen } from "@/lib/content/registry";
import { getTexts } from "@/lib/content/texts";
import { mapsUrl } from "@/lib/site";

export async function Locations() {
  const t = (await getTexts(algemeen)).locatie;
  return (
    <div className="grid gap-4 md:grid-cols-3">
      <a
        href={mapsUrl(t.street, t.city)}
        target="_blank"
        rel="noopener noreferrer"
        className="card group flex flex-col p-6 transition-colors hover:border-ink/40"
      >
        <MapPin className="size-6" aria-hidden="true" />
        <h3 className="display mt-6 text-2xl">{t.name}</h3>
        <p className="mt-2 text-sm text-muted">
          {t.street}
          <br />
          {t.city}
        </p>
        <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold">
          Route <ArrowUpRight className="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true" />
        </span>
      </a>
      <div className="card flex flex-col p-6">
        <Trees className="size-6" aria-hidden="true" />
        <h3 className="display mt-6 text-2xl">{t.onLocationTitle}</h3>
        <p className="mt-2 text-sm text-muted">{t.onLocation}</p>
      </div>
      <div className="flex flex-col rounded-xl bg-accent-tint p-6">
        <Smartphone className="size-6 text-accent" aria-hidden="true" />
        <h3 className="display mt-6 text-2xl">{t.onlineTitle}</h3>
        <p className="mt-2 text-sm text-muted">{t.online}</p>
      </div>
    </div>
  );
}
