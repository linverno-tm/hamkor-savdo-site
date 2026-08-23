import { branches } from "@/data/branches";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { Hero } from "@/components/sections/Hero";
import { About } from "@/components/sections/About";
import { Scale } from "@/components/sections/Scale";
import { Categories } from "@/components/sections/Categories";
import { Why } from "@/components/sections/Why";
import { Installment } from "@/components/sections/Installment";
import { Branches } from "@/components/sections/Branches";
import { Store } from "@/components/sections/Store";
import { LeadForm } from "@/components/sections/LeadForm";
import { Contact } from "@/components/sections/Contact";
import { FinalCta } from "@/components/sections/FinalCta";

export default function Home() {
  /**
   * One Store node per real branch, each carrying the @id of its own page so a
   * search engine treats these and the branch pages as the same entities
   * rather than duplicates. Only the home page publishes the full set.
   */
  const jsonLd = {
    "@context": "https://schema.org",
    "@graph": branches.map((b) => ({
      "@type": "Store",
      "@id": absolute(`/filiallar/${b.id}`),
      url: absolute(`/filiallar/${b.id}`),
      name: `${site.name} — ${b.city}`,
      slogan: site.tagline,
      telephone: b.phone ?? site.phone,
      address: {
        "@type": "PostalAddress",
        streetAddress: b.landmark,
        addressLocality: b.city,
        addressRegion: "Andijon viloyati",
        addressCountry: "UZ",
      },
      sameAs: [site.instagramUrl, site.telegramUrl],
    })),
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
        <Branches />
        <Store />
        <LeadForm />
        <Contact />
        <FinalCta />
      </main>
      <Footer />
    </>
  );
}
