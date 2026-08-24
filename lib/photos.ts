import { readdirSync } from "node:fs";
import { join } from "node:path";

/**
 * Branch photography, read from disk at build time.
 *
 * Deliberately not a hand-maintained list: `tools/rasm-tayyorlash.py` writes
 * the files, and this reads whatever it wrote. Adding a photo means dropping it
 * in and rebuilding — no data file to forget to update.
 *
 * Naming contract (enforced by the same script): `<tur>-<n>-<kenglik>.webp`,
 * where tur is tashqi | zal | jamoa | mijoz.
 */

export type PhotoKind = "tashqi" | "zal" | "jamoa" | "mijoz";

export interface Photo {
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

const LABEL: Record<PhotoKind, string> = {
  tashqi: "Do'kon tashqarisi",
  zal: "Savdo zali",
  jamoa: "Jamoamiz",
  mijoz: "Mijozlar bilan",
};

const ORDER: PhotoKind[] = ["tashqi", "zal", "jamoa", "mijoz"];

function isKind(v: string): v is PhotoKind {
  return (ORDER as string[]).includes(v);
}

export function branchPhotos(slug: string, cityName: string): Photo[] {
  const dir = join(process.cwd(), "public", "filiallar", slug);

  let files: string[];
  try {
    files = readdirSync(dir);
  } catch {
    return []; // filial uchun rasm hali yo'q
  }

  const photos = files
    .filter((f) => f.endsWith("-960.webp"))
    .map((f) => {
      const base = f.replace("-960.webp", "");
      const kind = base.split("-")[0];
      if (!isKind(kind)) return null;
      return {
        src: `/filiallar/${slug}/${base}-960.webp`,
        srcSmall: `/filiallar/${slug}/${base}-480.webp`,
        kind,
        alt: `HAMKOR SAVDO ${cityName} filiali — ${CAPTION[kind]}`,
        label: LABEL[kind],
      } satisfies Photo;
    })
    .filter((p): p is Photo => p !== null);

  // Tashqi ko'rinish birinchi, so'ng zal, jamoa va mijozlar.
  return photos.sort(
    (a, b) => ORDER.indexOf(a.kind) - ORDER.indexOf(b.kind) || a.src.localeCompare(b.src),
  );
}
