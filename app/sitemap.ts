import type { MetadataRoute } from "next";
import { branches } from "@/data/branches";
import { absolute } from "@/lib/seo";
import { content } from "@/lib/content";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

/**
 * Har bir yozuvga o'zbek-kirill (`/uz-kr/...`) hamkasbini hreflang sifatida
 * qo'shadi. Kirill nusxa `tools/uz-kr-build.mjs` orqali `next build`dan
 * KEYIN paydo bo'ladi — Next o'zi bu haqda bilmaydi, shuning uchun yo'l shu
 * yerda qo'lda takrorlanadi.
 */
function withUzKr(path: string) {
  return {
    languages: {
      uz: absolute(path),
      "uz-Cyrl": absolute(`/uz-kr${path}`),
      // Til aniqlanmasa lotin nusxa — sahifalardagi hreflang bilan bir xil.
      "x-default": absolute(path),
    },
  };
}

export default function sitemap(): MetadataRoute.Sitemap {
  return [
    { url: absolute("/"), changeFrequency: "monthly", priority: 1, alternates: withUzKr("/") },
    {
      url: absolute("/filiallar"),
      changeFrequency: "monthly",
      priority: 0.9,
      alternates: withUzKr("/filiallar"),
    },
    ...branches.map((b) => ({
      url: absolute(`/filiallar/${b.id}`),
      changeFrequency: "monthly" as const,
      priority: 0.8,
      alternates: withUzKr(`/filiallar/${b.id}`),
    })),
    ...(content.products.length > 0
      ? [
          {
            url: absolute("/katalog"),
            changeFrequency: "weekly" as const,
            priority: 0.7,
            alternates: withUzKr("/katalog"),
          },
        ]
      : []),
    /* Maxfiylik sahifasi kam o'zgaradi va qidiruv uchun muhim emas, lekin
       formadagi havola unga ishora qiladi — indeksda bo'lgani ma'qul. */
    {
      url: absolute("/maxfiylik"),
      changeFrequency: "yearly",
      priority: 0.2,
      alternates: withUzKr("/maxfiylik"),
    },
  ];
}
