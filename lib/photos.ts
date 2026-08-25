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

/**
 * Per-photo overrides, keyed `<slug>/<base>`.
 *
 * The generic captions above are right for almost every frame, but not for
 * every one. The Asaka branch sits on the second floor of somebody else's
 * building, so its "storefront" photo shows the Makro supermarket sign and no
 * HAMKOR SAVDO branding at all. Labelled "Do'kon tashqarisi" that frame is
 * simply confusing; labelled as the building you walk into, it becomes the most
 * useful photo on the page.
 */
const OVERRIDE: Record<string, { label?: string; alt?: string }> = {
  "asaka-umid/tashqi-1": {
    label: "Kirish — Makro binosi",
    alt: "Makro supermarketi binosi — HAMKOR SAVDO shu binoning 2-qavatida joylashgan",
  },
};

function isKind(v: string): v is PhotoKind {
  return (ORDER as string[]).includes(v);
}

/**
 * Which photo represents a branch on the home page, when the usual rule —
 * "show the storefront" — picks a poor one.
 *
 * Asaka trades on the second floor of a building that carries somebody else's
 * signs, so its storefront frame is the least recognisable of the four. On its
 * own page that frame still earns the lead slot, because it shows the door you
 * walk through. In a row of four cards, though, it is simply the weakest, so
 * the card leads with the branch's own counter instead.
 */
const COVER: Record<string, string> = {
  "asaka-umid": "jamoa-1",
};

export function branchCover(photos: Photo[], slug: string): Photo | null {
  const preferred = COVER[slug];
  if (preferred) {
    const hit = photos.find((p) => p.src.endsWith(`/${preferred}-960.webp`));
    if (hit) return hit;
  }
  return photos.find((p) => p.kind === "tashqi") ?? photos[0] ?? null;
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
      const custom = OVERRIDE[`${slug}/${base}`] ?? {};
      return {
        src: `/filiallar/${slug}/${base}-960.webp`,
        srcSmall: `/filiallar/${slug}/${base}-480.webp`,
        kind,
        alt: custom.alt ?? `HAMKOR SAVDO ${cityName} filiali — ${CAPTION[kind]}`,
        label: custom.label ?? LABEL[kind],
      } satisfies Photo;
    })
    .filter((p): p is Photo => p !== null);

  // Tashqi ko'rinish birinchi, so'ng zal, jamoa va mijozlar.
  return photos.sort(
    (a, b) => ORDER.indexOf(a.kind) - ORDER.indexOf(b.kind) || a.src.localeCompare(b.src),
  );
}
