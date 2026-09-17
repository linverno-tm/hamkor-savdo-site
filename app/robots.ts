import type { MetadataRoute } from "next";
import { absolute } from "@/lib/seo";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      /* `/rahmat/` — ariza yuborilgandan keyingi sahifa: qidiruvdan unga
         tushgan odam nima uchun rahmat aytilayotganini tushunmaydi.
         `/api/` — forma qabul qiluvchisi, sahifa emas. */
      disallow: ["/rahmat/", "/api/", "/admin/", "/uz-kr/rahmat/"],
    },
    sitemap: absolute("/sitemap.xml"),
  };
}
