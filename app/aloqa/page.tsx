import type { Metadata } from "next";
import Link from "next/link";
import { branches, mapsUrl } from "@/data/branches";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { Contact } from "@/components/sections/Contact";
import { LeadForm } from "@/components/sections/LeadForm";
import { SplitWords } from "@/components/ui/SplitWords";
import { OpenStatus } from "@/components/ui/OpenStatus";

/**
 * Aloqa — alohida sahifa.
 *
 * Bosh sahifada ham "Aloqa" bo'limi bor, lekin u sahifa ichidagi langar edi.
 * Qidiruv natijasidagi sayt bo'limlari (sitelinks) va "hamkor savdo telefon",
 * "hamkor savdo manzil" kabi so'rovlar uchun o'z manzili, sarlavhasi va
 * tavsifi bo'lgan sahifa kerak. Mazmun — hamma telefon, manzil, ish vaqti,
 * xarita va rasmiy kanallar bir joyda.
 */

const title = `Aloqa — telefon va manzillar | HAMKOR SAVDO: Shahrixon, Asaka, Andijon`;
const description = `HAMKOR SAVDO bilan bog'lanish: asosiy telefon ${site.phoneDisplay}, ${branches.length} ta filialning manzili, telefoni, ish vaqti va xaritasi. Telegram va Instagram sahifalari.`;

export const metadata: Metadata = {
  title,
  description,
  alternates: { canonical: absolute("/aloqa") },
  openGraph: { title, description, type: "website", locale: "uz_UZ", siteName: site.name },
};

export default function AloqaPage() {
  const jsonLd = [
    {
      "@context": "https://schema.org",
      "@type": "ContactPage",
      name: title,
      url: absolute("/aloqa"),
      mainEntity: {
        "@type": "Organization",
        "@id": `${absolute("/")}#organization`,
        name: site.name,
        telephone: site.phone,
        sameAs: [site.instagramUrl, site.telegramUrl],
        // Har bir filial — o'z sahifasidagi Store bilan bir xil @id (takror emas, bog'lanish).
        department: branches.map((b) => ({ "@id": absolute(`/filiallar/${b.id}`) })),
      },
    },
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      itemListElement: [
        { "@type": "ListItem", position: 1, name: "Bosh sahifa", item: absolute("/") },
        { "@type": "ListItem", position: 2, name: "Aloqa", item: absolute("/aloqa") },
      ],
    },
  ];

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }} />
      <Header />
      <main id="asosiy" tabIndex={-1}>
        <section className="tp-hero on-purple text-white">
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-grid" />
          </div>
          <div className="wrap relative">
            <nav aria-label="Qayerdaman" className="tp-crumbs in-1">
              <Link href="/" className="hover:text-white">
                Bosh sahifa
              </Link>
              <span className="mx-2" aria-hidden="true">
                /
              </span>
              <span className="text-white">Aloqa</span>
            </nav>
            <p className="hero-pill in-1 mt-7">
              <span className="hero-pill-dot" aria-hidden="true" />
              Aloqa
            </p>
            <h1 className="tp-title">
              <span className="hero-line">
                <span className="in-2">Biz bilan bog&apos;lanish</span>
              </span>
            </h1>
            <p className="hero-lead in-4">
              Qo&apos;ng&apos;iroq qiling, eng yaqin filialga keling yoki ariza qoldiring — o&apos;zimiz qo&apos;ng&apos;iroq
              qilamiz.
            </p>
            <div className="in-5 mt-9 flex flex-wrap gap-3">
              <a href={`tel:${site.phone}`} className="btn btn-yellow btn-glow">
                {site.phoneDisplay}
              </a>
              <a href="#ariza" className="btn btn-outline">
                Ariza qoldirish
              </a>
            </div>
          </div>
        </section>

        <section aria-labelledby="aloqa-filiallar" className="sec">
          <div className="wrap">
            <div data-m>
              <h2 id="aloqa-filiallar" className="sh-title">
                <SplitWords text="Filiallar manzili" />
              </h2>
            </div>
            <ul data-m="" data-m-stagger className="tp-branches">
              {branches.map((b) => {
                const phone = b.phone ?? site.phone;
                return (
                  <li key={b.id} className="branch-item flex flex-col">
                    <div className="branch-top">
                      <span className="branch-idx numeral" aria-hidden="true">
                        {b.index}
                      </span>
                      <div className="min-w-0 flex-1">
                        <p className="branch-city">{b.city}</p>
                        <p className="branch-land">{b.landmark}</p>
                        <OpenStatus hours={b.hours} closed={b.closed} />
                      </div>
                    </div>
                    <p className="branch-addr">{b.address}</p>
                    {b.closed ? (
                      <span className="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
                        Vaqtincha yopiq{b.closedNote ? ` — ${b.closedNote}` : ""}
                      </span>
                    ) : null}
                    <dl className="mt-4 space-y-1 text-sm">
                      <div className="flex gap-2">
                        <dt className="text-ink-3">Telefon:</dt>
                        <dd>
                          <a href={`tel:${phone}`} className="font-semibold text-purple hover:underline">
                            {b.phoneDisplay ?? site.phoneDisplay}
                          </a>
                        </dd>
                      </div>
                      <div className="flex gap-2">
                        <dt className="text-ink-3">Ish vaqti:</dt>
                        <dd className="text-ink-2">{b.hours}</dd>
                      </div>
                    </dl>
                    <div className="branch-actions mt-auto pt-5">
                      <a href={mapsUrl(b)} target="_blank" rel="noopener noreferrer" className="branch-link">
                        Xaritada
                      </a>
                      <Link href={`/filiallar/${b.id}`} className="branch-link">
                        Filial sahifasi
                      </Link>
                    </div>
                  </li>
                );
              })}
            </ul>
          </div>
        </section>

        {/* Telegram, Instagram, bot va hamma telefonlar — bosh sahifadagi bilan bir xil bo'lim. */}
        <Contact />

        <LeadForm page="/aloqa/" />
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}
