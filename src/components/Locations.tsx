import { ArrowUpRight, MapPin, Smartphone, Trees } from "lucide-react";
import { LOCATIONS, ON_LOCATION } from "@/lib/site";

export function Locations() {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      {LOCATIONS.map((loc) => (
        <a
          key={loc.name}
          href={loc.maps}
          target="_blank"
          rel="noopener noreferrer"
          className="card group flex flex-col p-6 transition-colors hover:border-rose-soft"
        >
          <MapPin className="size-6" aria-hidden="true" />
          <h3 className="display mt-6 text-2xl">{loc.name}</h3>
          <p className="mt-2 text-sm text-muted">
            {loc.street}
            <br />
            {loc.city}
          </p>
          <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold">
            Route <ArrowUpRight className="size-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true" />
          </span>
        </a>
      ))}
      <div className="card flex flex-col p-6">
        <Trees className="size-6" aria-hidden="true" />
        <h3 className="display mt-6 text-2xl">Op locatie</h3>
        <p className="mt-2 text-sm text-muted">{ON_LOCATION}</p>
      </div>
      <div className="flex flex-col rounded-[1.25rem] bg-blush p-6">
        <Smartphone className="size-6 text-rose" aria-hidden="true" />
        <h3 className="display mt-6 text-2xl">Online</h3>
        <p className="mt-2 text-sm text-muted">Met online coaching train je waar en wanneer jij wilt, met Steyn altijd binnen handbereik.</p>
      </div>
    </div>
  );
}
