import Link from "next/link";
import type { Metadata } from "next";
import { branches, cityList } from "@/data/branches";
import { site } from "@/data/site";
import { absolute, ldJson } from "@/lib/seo";
import { branchCover, branchPhotos } from "@/lib/photos";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { PhotoPlaceholder } from "@/components/ui/PhotoPlaceholder";
import { BranchRoute } from "@/components/sections/BranchRoute";

/**
 * The branch index at `/filiallar/`.
 *
 * Four branch pages existed but the folder they sit in did not, so the address
 * a visitor reaches by trimming the URL — and the one a crawler tries first —
 * returned 404. This page fills it, and gives the sitemap a hub that links to
 * all four.
 *
 * `BreadcrumbList` is emitted here rather than on the branch pages so the trail
 * search engines show ("Bosh sahifa › Filiallar") points at a page that exists.
 */
export const metadata: Metadata = {
  title: "Filiallar — HAMKOR SAVDO",
  description: `HAMKOR SAVDO filiallari: ${cityList()}. Har bir filialning manzili, telefoni va suratlari.`,
  alternates: { canonical: absolute("/filiallar") },
  openGraph: {
    title: "Filiallar — HAMKOR SAVDO",
    type: "website",
    locale: "uz_UZ",
  },
};

export default function BranchIndexPage() {
  const cards = branches.map((b) => {
    const photos = branchPhotos(b.id, b.city);
    return {
      branch: b,
      cover: branchCover(photos, b.id),
      count: photos.length,
    };
  });

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      {
        "@type": "ListItem",
        position: 1,
        name: "Bosh sahifa",
        item: absolute("/"),
      },
      {
        "@type": "ListItem",
        position: 2,
        name: "Filiallar",
        item: absolute("/filiallar"),
      },
    ],
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: ldJson(jsonLd) }}
      />
      <Header />
      <main id="asosiy" tabIndex={-1} className="fl-page">
        <section className="tp-hero on-purple text-white">
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-grid" />
          </div>
          <div className="wrap relative">
            <nav aria-label="Yo'l" className="tp-crumbs in-1">
              <Link href="/" className="tap hover:text-white">
                Bosh sahifa
              </Link>
              <span className="px-2" aria-hidden="true">
                /
              </span>
              <span className="text-white">Filiallar</span>
            </nav>

            <div className="fl-hero-grid">
              <div>
                <p className="hero-pill in-1 mt-7">
                  <span className="hero-pill-dot" aria-hidden="true" />
                  Filiallar
                </p>
                <h1 className="tp-title">
                  <span className="hero-line">
                    <span className="in-2">
                      {site.facts.branchCount} ta filial — {site.serviceArea}
                    </span>
                  </span>
                </h1>
                <p className="hero-lead in-4">
                  Mahsulotni jonli ko&apos;rib, taqqoslab tanlaysiz. Sizga eng
                  yaqin filialni tanlang — manzili, telefoni va suratlari
                  o&apos;sha sahifada.
                </p>
              </div>
              <BranchRoute />
            </div>
          </div>
        </section>

        <section className="sec">
          <ul data-m="" data-m-stagger className="wrap fl-grid">
            {cards.map(({ branch, cover, count }) => (
              <li key={branch.id} data-branch={branch.id}>
                <Link
                  href={`/filiallar/${branch.id}`}
                  className="store-card group"
                >
                  <span className="store-media">
                    {cover ? (
                      <img
                        src={cover.src}
                        srcSet={`${cover.srcSmall} 480w, ${cover.src} 960w`}
                        sizes="(max-width: 640px) 100vw, 50vw"
                        width={960}
                        height={720}
                        alt={cover.alt}
                        loading="lazy"
                        decoding="async"
                        className="store-img"
                      />
                    ) : (
                      <PhotoPlaceholder
                        className="h-full"
                        caption={branch.landmark}
                      />
                    )}
                  </span>
                  <span className="store-body">
                    <span>
                      <span className="store-city numeral">{branch.city}</span>
                      <span className="store-land">{branch.landmark}</span>
                      <span className="mt-2 block text-sm text-ink-3">
                        {branch.address}
                      </span>
                    </span>
                    <span className="store-more">
                      {count > 0 ? `${count} ta surat` : "Batafsil"} &rarr;
                    </span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      </main>
      <Footer />
      <ActionBar href="/#ariza" />
    </>
  );
}
