import type { MetadataRoute } from "next";
import { SITE } from "@/lib/site";

const pages = [
  "",
  "/online-coaching",
  "/personal-training",
  "/ademcoaching",
  "/voedingscoaching",
  "/small-group-training",
  "/tarieven",
  "/vriend-uitnodigen",
  "/over-steyn",
  "/contact",
  "/registreren",
  "/privacy",
];

export default function sitemap(): MetadataRoute.Sitemap {
  return pages.map((path) => ({
    url: `${SITE.url}${path}`,
    changeFrequency: "monthly",
    priority: path === "" ? 1 : path === "/online-coaching" ? 0.9 : 0.7,
  }));
}
