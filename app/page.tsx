import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { Hero } from "@/components/sections/Hero";
import { About } from "@/components/sections/About";
import { Scale } from "@/components/sections/Scale";
import { Categories } from "@/components/sections/Categories";
import { Why } from "@/components/sections/Why";
import { Installment } from "@/components/sections/Installment";
import { SpecialOrder } from "@/components/sections/SpecialOrder";
import { Branches } from "@/components/sections/Branches";
import { Store } from "@/components/sections/Store";
import { LeadForm } from "@/components/sections/LeadForm";
import { Contact } from "@/components/sections/Contact";
import { FinalCta } from "@/components/sections/FinalCta";

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
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <a
        href="#biz-haqimizda"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-purple focus:px-5 focus:py-3 focus:font-semibold focus:text-white"
      >
        Asosiy mazmunga o&apos;tish
      </a>
      <Header />
      <main>
        <Hero />
        <About />
        <Scale />
        <Categories />
        <Why />
        <Installment />
        <SpecialOrder />
        <Branches />
        <Store />
        <LeadForm />
        <Contact />
        <FinalCta />
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}
