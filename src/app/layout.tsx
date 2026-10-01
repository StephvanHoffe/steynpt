import "@fontsource-variable/archivo/wdth.css";
import "@fontsource-variable/inter";
import "./globals.css";
import type { Metadata, Viewport } from "next";
import { DemoBanner } from "@/components/DemoBanner";
import { DEMO_MODE } from "@/lib/demo";
import { SITE } from "@/lib/site";

export const metadata: Metadata = {
  metadataBase: new URL(SITE.url),
  title: {
    default: "SteynPT · Personal training, online coaching & ademcoaching in Amsterdam",
    template: "%s · SteynPT",
  },
  description: SITE.description,
  openGraph: {
    type: "website",
    locale: "nl_NL",
    siteName: "SteynPT",
    images: [{ url: "/images/steyn-glimlach.jpg", width: 900, height: 1350, alt: "Steyn van Leeuwen" }],
  },
  // De demoversie hoort niet in zoekmachines.
  ...(DEMO_MODE && { robots: { index: false, follow: false } }),
};

export const viewport: Viewport = {
  themeColor: "#ffffff",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="nl" data-scroll-behavior="smooth">
      <body className="flex min-h-dvh flex-col">
        <a href="#inhoud" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-accent-tint focus:px-4 focus:py-2 focus:text-ink">
          Naar de inhoud
        </a>
        {DEMO_MODE && <DemoBanner />}
        {children}
      </body>
    </html>
  );
}
