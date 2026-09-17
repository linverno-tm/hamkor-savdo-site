import type { Metadata } from "next";
import { Outfit, Bebas_Neue } from "next/font/google";
import { site } from "@/data/site";
import { SITE_URL, absolute } from "@/lib/seo";
import { Reveal } from "@/components/ui/Reveal";
import "./globals.css";

/**
 * Corporate fonts are TT Commons and Bebas Neue Pro (both commercial).
 * Outfit stands in for TT Commons — same geometric sans role — and Bebas Neue
 * is the free release of the display face. One variable axis plus one static
 * weight keeps the font payload around 30 KB on a cold load.
 */
const outfit = Outfit({
  variable: "--font-outfit",
  subsets: ["latin"],
  display: "swap",
});

const bebas = Bebas_Neue({
  variable: "--font-bebas",
  subsets: ["latin"],
  weight: "400",
  display: "swap",
});

const description =
  `HAMKOR SAVDO — Andijon viloyatidagi ${site.facts.branchCount} ta filial: tilla, texnika va mebel. ${site.facts.productCount.replace("+", " dan ortiq")} mahsulot, ${site.facts.installmentMonthsMax} oygacha muddatli to'lov, bepul yetkazib berish va o'rnatish.`;

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: "HAMKOR SAVDO — Oilangizga ishonchli hamkor",
  description,
  // `keywords` meta tegini qidiruv tizimlari 2009-yildan beri e'tiborga
  // olmaydi (Google buni ochiq e'lon qilgan) — shuning uchun olib tashlandi.
  alternates: {
    canonical: absolute("/"),
    languages: {
      uz: absolute("/"),
      "uz-Cyrl": absolute("/uz-kr/"),
    },
  },
  openGraph: {
    title: "HAMKOR SAVDO — Oilangizga ishonchli hamkor",
    description,
    locale: "uz_UZ",
    type: "website",
    siteName: site.name,
  },
};

export const viewport = {
  themeColor: "#5a3089",
};

/**
 * Til aniqlash — Kirill (uz-kr) sahifa React'siz, sof HTML bo'lgani uchun
 * (tools/uz-kr-build.mjs, hydration mos kelmasligining oldini olish uchun
 * React skriptlarini olib tashlaydi) bu alohida, juda kichik, Next'ga
 * bog'liq bo'lmagan skript — shuning uchun `data-keep` bilan belgilangan va
 * shu skript ikkala variantda ham saqlanib qoladi.
 *
 * Qoida: ruscha qurilma -> Kirill, boshqa hamma narsa (jumladan ingliz) ->
 * standart Lotin. Odam qaysi variantni ko'rib tursa, shuni "tanlov" sifatida
 * eslab qoladi — keyingi safar ochilganda avtomatik almashtirmaydi, hatto
 * ruscha brauzerda ham (ataylab Lotinni tanlagan bo'lishi mumkin).
 */
const LANG_DETECT_SCRIPT = `(function(){try{
var onCyr = location.pathname.indexOf('/uz-kr') === 0;
var saved = localStorage.getItem('hs_lang');
if (!saved && !onCyr && /^ru\\b/i.test(navigator.language || '')) {
  localStorage.setItem('hs_lang', 'uz-kr');
  location.replace('/uz-kr' + location.pathname + location.search + location.hash);
  return;
}
localStorage.setItem('hs_lang', onCyr ? 'uz-kr' : 'uz');
}catch(e){}})();`;

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="uz" className={`${outfit.variable} ${bebas.variable}`}>
      <head>
        <script data-keep dangerouslySetInnerHTML={{ __html: LANG_DETECT_SCRIPT }} />
      </head>
      <body>
        {/* Reveal animations never gate content: the document ships fully
            visible and <Reveal> opts individual blocks into motion at runtime,
            so no-JS and older browsers simply read the page as-is. */}
        <Reveal />
        {children}
      </body>
    </html>
  );
}
