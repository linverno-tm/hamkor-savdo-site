import type { MetadataRoute } from "next";
import { branches } from "@/data/branches";
import { absolute } from "@/lib/seo";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

export default function sitemap(): MetadataRoute.Sitemap {
  return [
    { url: absolute("/"), changeFrequency: "monthly", priority: 1 },
    { url: absolute("/filiallar"), changeFrequency: "monthly", priority: 0.9 },
    ...branches.map((b) => ({
      url: absolute(`/filiallar/${b.id}`),
      changeFrequency: "monthly" as const,
      priority: 0.8,
    })),
    /* Maxfiylik sahifasi kam o'zgaradi va qidiruv uchun muhim emas, lekin
       formadagi havola unga ishora qiladi — indeksda bo'lgani ma'qul. */
    { url: absolute("/maxfiylik"), changeFrequency: "yearly", priority: 0.2 },
  ];
}
