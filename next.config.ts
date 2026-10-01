import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  serverExternalPackages: ["@libsql/client", "libsql"],
  async redirects() {
    // Oude WordPress-URL's blijven werken.
    return [
      { source: "/over-steynpt", destination: "/over-steyn", permanent: true },
      { source: "/vraag-een-gratis-kennismaking-aan", destination: "/contact", permanent: true },
      { source: "/kennismaking", destination: "/contact", permanent: true },
      { source: "/feed", destination: "/", permanent: true },
    ];
  },
};

export default nextConfig;
