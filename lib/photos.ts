import { readdirSync } from "node:fs";
import { join } from "node:path";
import { content } from "@/lib/content";

/**
 * Branch photography, read from disk at build time.
 *
 * Ikki manba:
 *  - `public/filiallar/<slug>/<tur>-<n>-<kenglik>.webp` — repoda turgan suratlar;
 *  - `public/rasm/filiallar/<slug>/<nom>-<kenglik>.webp` — admin paneldan
 *    yuklangan asl surat (`rasmlar/filiallar/<slug>/`), prebuild'da
 *    `tools/rasmlarni-tayyorlash.mjs` yasaydi.
 *
 * Nom `<tur>-...` bilan boshlanadi, tur: tashqi | zal | jamoa | mijoz.
 * Tartib, muqova, yorliq va alt matnini admin `content.json > photos` da beradi.
 */

export type PhotoKind = "tashqi" | "zal" | "jamoa" | "mijoz";

export interface Photo {
  /** `<slug>` ichidagi asosiy nom, masalan "zal-2" — admin shu bilan tanlaydi. */
  base: string;
  /** 960px variant — the src. */
  src: string;
  /** 480px variant, offered to small screens through srcset. */
  srcSmall: string;
  kind: PhotoKind;
  alt: string;
  /** Rasm ostida ko'rinadigan qisqa izoh. */
  label: string;
}

const CAPTION: Record<PhotoKind, string> = {
  tashqi: "do'kon tashqi ko'rinishi",
  zal: "savdo zali",
  jamoa: "jamoamiz ish jarayonida",
  mijoz: "mijozlarga xizmat ko'rsatish",
};

export const PHOTO_LABEL: Record<PhotoKind, string> = {
  tashqi: "Do'kon tashqarisi",
  zal: "Savdo zali",
  jamoa: "Jamoamiz",
  mijoz: "Mijozlar bilan",
};

const ORDER: PhotoKind[] = ["tashqi", "zal", "jamoa", "mijoz"];

function isKind(v: string): v is PhotoKind {
  return (ORDER as string[]).includes(v);
}

function scan(dir: string, urlBase: string): { base: string; src: string; srcSmall: string }[] {
  let files: string[];
  try {
    files = readdirSync(dir);
  } catch {
    return [];
  }
  return files
    .filter((f) => f.endsWith("-960.webp"))
    .map((f) => {
      const base = f.replace("-960.webp", "");
      return { base, src: `${urlBase}/${base}-960.webp`, srcSmall: `${urlBase}/${base}-480.webp` };
    });
}

/**
 * Bosh sahifa kartochkasidagi surat. Odatda tashqi ko'rinish, lekin admin
 * paneldan boshqasini tanlash mumkin — `content.json` dagi `photos.<slug>.cover`.
 *
 * Asakada tashqi surat boshqa brendlarning peshtaxtasi bilan chiqadi
 * (do'kon Makro binosining 2-qavatida). Egasi baribir shuni tanladi: mijoz
 * uchun binoni tanib olish "bu HAMKOR SAVDO" degan yozuvdan muhimroq.
 * Kartochkadagi yozuv va alt matni qavatni aytib turadi.
 */
export function branchCover(photos: Photo[], slug: string): Photo | null {
  const preferred = content.photos[slug]?.cover;
  if (preferred) {
    const hit = photos.find((p) => p.base === preferred);
    if (hit) return hit;
  }
  return photos.find((p) => p.kind === "tashqi") ?? photos[0] ?? null;
}

export function branchPhotos(slug: string, cityName: string): Photo[] {
  const prefs = content.photos[slug] ?? {};
  const pub = join(process.cwd(), "public");
  const found = [
    ...scan(join(pub, "filiallar", slug), `/filiallar/${slug}`),
    ...scan(join(pub, "rasm", "filiallar", slug), `/rasm/filiallar/${slug}`),
  ];

  const photos = found
    .map(({ base, src, srcSmall }) => {
      const kind = base.split("-")[0];
      if (!isKind(kind)) return null;
      return {
        base,
        src,
        srcSmall,
        kind,
        alt: prefs.alts?.[base] || `HAMKOR SAVDO ${cityName} filiali — ${CAPTION[kind]}`,
        label: prefs.labels?.[base] || PHOTO_LABEL[kind],
      } satisfies Photo;
    })
    .filter((p): p is Photo => p !== null);

  const manual = prefs.order ?? [];
  const rank = (p: Photo) => {
    const i = manual.indexOf(p.base);
    return i === -1 ? Number.MAX_SAFE_INTEGER : i;
  };

  // Admin bergan tartib birinchi; qolganlari: tashqi, zal, jamoa, mijoz.
  return photos.sort(
    (a, b) =>
      rank(a) - rank(b) ||
      ORDER.indexOf(a.kind) - ORDER.indexOf(b.kind) ||
      a.base.localeCompare(b.base, "en", { numeric: true }),
  );
}
