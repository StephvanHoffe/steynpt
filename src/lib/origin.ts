import "server-only";
import { headers } from "next/headers";
import { SITE } from "./site";

/** Adres van de site: NEXT_PUBLIC_SITE_URL, of anders het domein van het verzoek (bijvoorbeeld een demo). */
export async function siteOrigin() {
  if (process.env.NEXT_PUBLIC_SITE_URL) return SITE.url;
  const h = await headers();
  const host = h.get("x-forwarded-host") ?? h.get("host");
  if (!host) return SITE.url;
  const proto = h.get("x-forwarded-proto") ?? (host.startsWith("localhost") ? "http" : "https");
  return `${proto}://${host}`;
}
