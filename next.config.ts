import type { NextConfig } from "next";

/**
 * Static export.
 *
 * The site is hosted on ahost.uz shared hosting, which serves files through
 * Apache and runs PHP — not Node. So the whole site is prerendered to plain
 * HTML and the one server-side piece, the enquiry form, is handled by
 * public/api/lead.php instead of a Next route handler.
 *
 * Local hosting is the right call here: the audience is in Andijan region, and
 * a server inside Uzbekistan is far quicker for them than one in Europe.
 */
const nextConfig: NextConfig = {
  output: "export",
  // Apache serves /filiallar/asaka-umid/ from that folder's index.html.
  trailingSlash: true,
  images: { unoptimized: true },
  poweredByHeader: false,
};

export default nextConfig;
