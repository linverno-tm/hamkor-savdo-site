import type { MetadataRoute } from "next";
import { branches } from "@/data/branches";
import { absolute } from "@/lib/seo";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

export default function sitemap(): MetadataRoute.Sitemap {
  return [
    { url: absolute("/"), changeFrequency: "monthly", priority: 1 },
    ...branches.map((b) => ({
      url: absolute(`/filiallar/${b.id}`),
      changeFrequency: "monthly" as const,
      priority: 0.8,
    })),
  ];
}
