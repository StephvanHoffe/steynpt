import type { MetadataRoute } from "next";
import { DEMO_MODE } from "@/lib/demo";
import { SITE } from "@/lib/site";

export default function robots(): MetadataRoute.Robots {
  if (DEMO_MODE) return { rules: { userAgent: "*", disallow: "/" } };
  return {
    rules: { userAgent: "*", allow: "/", disallow: ["/account", "/admin", "/r/"] },
    sitemap: `${SITE.url}/sitemap.xml`,
  };
}
