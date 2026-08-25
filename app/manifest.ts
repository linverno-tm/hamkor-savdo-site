import type { MetadataRoute } from "next";
import { site } from "@/data/site";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

/**
 * Web app manifest.
 *
 * Not because this should behave like an app, but because "add to home screen"
 * is a normal thing for a shop's customers to do — and without a manifest the
 * saved icon comes out blank with the bare domain under it.
 */
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: `${site.name} — ${site.tagline}`,
    short_name: site.name,
    description: `Tilla, texnika va mebel. ${site.facts.installmentMonthsMax} oygacha muddatli to'lov.`,
    lang: "uz",
    start_url: "/",
    display: "standalone",
    background_color: "#ffffff",
    theme_color: "#5a3089",
    icons: [
      { src: "/icon.svg", sizes: "any", type: "image/svg+xml", purpose: "any" },
      { src: "/apple-icon.png", sizes: "180x180", type: "image/png" },
    ],
  };
}
