import type { Metadata } from "next";
import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { absolute, ldJson } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { Promotions } from "@/components/sections/Promotions";
import { Catalog } from "@/components/sections/Catalog";
import { LeadForm } from "@/components/sections/LeadForm";
import { Faq } from "@/components/sections/Faq";
import { UyHero } from "@/components/uy/UyHero";
import { UyYol } from "@/components/uy/UyYol";
import { UyYonalish } from "@/components/uy/UyYonalish";
import { UyFiliallar } from "@/components/uy/UyFiliallar";
import { UyIshonch } from "@/components/uy/UyIshonch";
import "./uy.css";

/** "8:00–18:00" -> {opens:"08:00", closes:"18:00"} — schema.org soat qatori uchun. */
function parseHours(hours: string) {
  const [opens, closes] = hours.split("–").map((t) => {
    const [h, m = "00"] = t.trim().split(":");
    return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
  });
  return { opens, closes };
}

const ALL_DAYS = [
  "Monday",
  "Tuesday",
  "Wednesday",
  "Thursday",
  "Friday",
  "Saturday",
  "Sunday",
];

/* Lotin/kirill juftligini build skripti qo'yadi; bu yerda faqat ruscha. */
export const metadata: Metadata = {
  alternates: { canonical: absolute("/"), languages: { ru: absolute("/ru") } },
};

export default function Home() {
  /**
   * One Store node per real branch, each carrying the @id of its own page so a
   * search engine treats these and the branch pages as the same entities
   * rather than duplicates. Only the home page publishes the full set.
   */
  const jsonLd = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": `${absolute("/")}#organization`,
        name: site.name,
        url: absolute("/"),
        logo: absolute("/icon.svg"),
        sameAs: [site.instagramUrl, site.telegramUrl],
      },
      {
        "@type": "WebSite",
        "@id": `${absolute("/")}#website`,
        url: absolute("/"),
        name: site.name,
        inLanguage: "uz",
      },
      ...branches.map((b) => ({
        "@type": "Store",
        "@id": absolute(`/filiallar/${b.id}`),
        url: absolute(`/filiallar/${b.id}`),
        name: `${site.name} — ${b.city}, ${b.landmark}`,
        slogan: site.tagline,
        telephone: b.phone ?? site.phone,
        address: {
          "@type": "PostalAddress",
          streetAddress: b.landmark,
          addressLocality: b.city,
          addressRegion: "Andijon viloyati",
          addressCountry: "UZ",
        },
        geo: {
          "@type": "GeoCoordinates",
          latitude: b.lat,
          longitude: b.lng,
        },
        // Egasi faqat ochilish/yopilish soatini berdi, dam olish kuni
        // aytilmadi — shuning uchun bu soat 7 kunga baravar qo'llanadi deb
        // olindi (o'ylab topilgan qiymat emas, berilgan soatning o'zi).
        openingHoursSpecification: {
          "@type": "OpeningHoursSpecification",
          dayOfWeek: ALL_DAYS,
          ...parseHours(b.hours),
        },
        sameAs: [b.instagramUrl ?? site.instagramUrl, site.telegramUrl],
      })),
    ],
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: ldJson(jsonLd) }}
      />
      <Header />
      <main id="asosiy" tabIndex={-1} className="uy-page">
        {/* 01.11 redizayn — g'oya: «Uyingizga hozir — to'lov keyin».
            Tartib (7 bo'lim): va'da (hero + qisqa ariza) → do'kondan uygacha
            va to'lov taqvimi → yo'nalishlar → (aksiya va katalog — admin
            to'ldirsa) → filiallar xaritasi → ishonch → ariza → savollar.
            Skip-link Header ichida (#asosiy). */}
        <UyHero />
        <UyYol />
        <UyYonalish />
        <Promotions />
        <Catalog />
        <UyFiliallar />
        <UyIshonch />
        <LeadForm />
        <Faq />
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}
