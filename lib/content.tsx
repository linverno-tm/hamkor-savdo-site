import raw from "@/data/content.json";

/**
 * Admin panel tahrirlaydigan hamma narsa `data/content.json` da. Panel faylni
 * GitHub'ga yozadi, sayt qayta yig'iladi â€” shuning uchun bu yerdagi turlar
 * `public/admin/_lib/content.php` dagi tekshiruv bilan bir xil bo'lishi kerak.
 */

export type CategoryId = "tilla" | "texnika" | "mebel";

export interface ContentBranch {
  id: string;
  city: string;
  address: string;
  landmark: string;
  phone: string | null;
  phoneDisplay: string | null;
  instagram: string | null;
  hours: string;
  mapQuery: string;
  lat: number;
  lng: number;
  closed: boolean;
  closedNote: string;
}

export interface Promotion {
  id: string;
  title: string;
  body: string;
  /** `rasm/aksiyalar/<nom>` â€” prebuild 480/960 webp yasaydi. Bo'sh bo'lishi mumkin. */
  image: string;
  /** YYYY-MM-DD, Toshkent vaqti bilan. Bo'sh = cheklovsiz. */
  startsAt: string;
  endsAt: string;
}

export interface Product {
  id: string;
  name: string;
  category: CategoryId;
  /** So'mda. null = "narxini so'rang". */
  price: number | null;
  image: string;
  inStock: boolean;
  note: string;
}

export interface PhotoPrefs {
  cover?: string;
  order?: string[];
  labels?: Record<string, string>;
  alts?: Record<string, string>;
}

export interface Content {
  settings: {
    phone: string;
    phoneDisplay: string;
    instagram: string;
    telegram: string;
    telegramCustomers: string;
    telegramBot: string;
    productCount: string;
    installmentMonthsMax: number;
    instagramFollowers: string;
    freeDeliveryArea: string;
    deliveryArea: string;
    warrantyMonths: number | null;
    yearFounded: number | null;
  };
  texts: {
    heroLead: string;
    aboutParagraphs: string[];
    companyStory: string;
    specialOrderLead: string;
    finalCtaLead: string;
  };
  branches: ContentBranch[];
  photos: Record<string, PhotoPrefs>;
  faq: { q: string; a: string }[];
  promotions: Promotion[];
  products: Product[];
}

export const content = raw as unknown as Content;

/** `{oy}`, `{filial}`, `{mahsulot}`, `{bepul_hudud}`, `{yetkazish}` o'rniga haqiqiy qiymat. */
export function fill(text: string): string {
  const s = content.settings;
  const values: Record<string, string> = {
    oy: String(s.installmentMonthsMax),
    filial: String(content.branches.length),
    mahsulot: s.productCount,
    bepul_hudud: s.freeDeliveryArea,
    yetkazish: s.deliveryArea,
  };
  return text.replace(/\{([a-z_]+)\}/g, (m, key: string) => values[key] ?? m);
}

/** Admin matnidagi `**qalin**` â€” boshqa hech qanday belgilash qabul qilinmaydi. */
export function Rich({ text, strongClass = "font-semibold text-ink" }: { text: string; strongClass?: string }) {
  const parts = fill(text).split(/\*\*(.+?)\*\*/g);
  return (
    <>
      {parts.map((p, i) =>
        i % 2 === 1 ? (
          <strong key={i} className={strongClass}>
            {p}
          </strong>
        ) : (
          p
        ),
      )}
    </>
  );
}

/** "12500000" -> "12 500 000 so'm" */
export function formatPrice(price: number | null): string {
  if (price === null) return "Narxini so'rang";
  return `${price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ")} so'm`;
}
