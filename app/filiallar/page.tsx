import Link from "next/link";
import type { Metadata } from "next";
import { branches, cityList } from "@/data/branches";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { branchCover, branchPhotos } from "@/lib/photos";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { PhotoPlaceholder } from "@/components/ui/PhotoPlaceholder";

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
  openGraph: { title: "Filiallar — HAMKOR SAVDO", type: "website", locale: "uz_UZ" },
};

export default function BranchIndexPage() {
  const cards = branches.map((b) => {
    const photos = branchPhotos(b.id, b.city);
    return { branch: b, cover: branchCover(photos, b.id), count: photos.length };
  });

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      { "@type": "ListItem", position: 1, name: "Bosh sahifa", item: absolute("/") },
      { "@type": "ListItem", position: 2, name: "Filiallar", item: absolute("/filiallar") },
    ],
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <Header />
      <main id="asosiy" tabIndex={-1} className="mx-auto max-w-7xl px-5 pb-20 pt-32 sm:px-8 sm:pt-40">
        <nav aria-label="Yo'l" className="text-sm text-ink-3">
          <Link href="/" className="tap hover:text-purple">
            Bosh sahifa
          </Link>
          <span className="px-2" aria-hidden="true">
            /
          </span>
          <span className="text-ink-2">Filiallar</span>
        </nav>

        <p className="kicker mt-6">Filiallar</p>
        <h1 className="display mt-3 text-4xl leading-[1.05] sm:text-5xl">
          {site.facts.branchCount} ta filial — {site.serviceArea}
        </h1>
        <p className="mt-5 max-w-2xl text-lg leading-relaxed text-ink-2">
          Mahsulotni jonli ko&apos;rib, taqqoslab tanlaysiz. Sizga eng yaqin filialni tanlang —
          manzili, telefoni va suratlari o&apos;sha sahifada.
        </p>

        <ul className="mt-10 grid gap-5 sm:grid-cols-2">
          {cards.map(({ branch, cover, count }) => (
            <li key={branch.id}>
              <Link
                href={`/filiallar/${branch.id}`}
                className="card card-hover group flex h-full flex-col overflow-hidden"
              >
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
                    className="block aspect-[16/10] w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                  />
                ) : (
                  <PhotoPlaceholder className="aspect-[16/10]" caption={branch.landmark} />
                )}
                <span className="flex flex-1 flex-col p-6">
                  <span className="numeral text-2xl tracking-[0.06em] text-purple">
                    {branch.city}
                  </span>
                  <span className="mt-1 text-sm text-ink-2">{branch.landmark}</span>
                  <span className="mt-3 text-sm text-ink-3">{branch.address}</span>
                  <span className="mt-4 text-sm font-semibold text-purple group-hover:underline">
                    {count > 0 ? `${count} ta surat` : "Batafsil"} &rarr;
                  </span>
                </span>
              </Link>
            </li>
          ))}
        </ul>
      </main>
      <Footer />
      <ActionBar href="/#ariza" />
    </>
  );
}
