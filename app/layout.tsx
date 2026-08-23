import type { Metadata } from "next";
import { Outfit, Bebas_Neue } from "next/font/google";
import { site } from "@/data/site";
import { SITE_URL } from "@/lib/seo";
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
  "HAMKOR SAVDO — Andijon viloyatidagi 4 ta filial: tilla, texnika va mebel. 7000 dan ortiq mahsulot, 24 oygacha muddatli to'lov, bepul yetkazib berish va o'rnatish.";

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: "HAMKOR SAVDO — Oilangizga ishonchli hamkor",
  description,
  keywords: [
    "Hamkor Savdo",
    "muddatli to'lov",
    "Shahrixon",
    "Asaka",
    "Andijon",
    "mebel",
    "texnika",
    "tilla",
  ],
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

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="uz" className={`${outfit.variable} ${bebas.variable}`}>
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
