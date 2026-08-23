import type { MetadataRoute } from "next";
import { absolute } from "@/lib/seo";

/** Required by `output: "export"` so this is emitted as a plain file. */
export const dynamic = "force-static";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: { userAgent: "*", allow: "/" },
    sitemap: absolute("/sitemap.xml"),
  };
}
