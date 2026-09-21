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
        <section className="pt-28 sm:pt-32">
          <div className="mx-auto max-w-7xl px-5 pb-12 sm:px-8">
            <nav aria-label="Qayerdaman" className="text-sm text-ink-3">
              <Link href="/" className="hover:text-purple">
                Bosh sahifa
              </Link>
              <span className="mx-2" aria-hidden="true">
                /
              </span>
              <span className="text-ink-2">Aloqa</span>
            </nav>
            <p className="kicker mt-8">Aloqa</p>
            <h1 className="display mt-3 text-4xl sm:text-5xl lg:text-6xl">Biz bilan bog&apos;lanish</h1>
            <p className="mt-6 max-w-2xl text-lg leading-relaxed text-ink-2 sm:text-xl">
              Qo&apos;ng&apos;iroq qiling, eng yaqin filialga keling yoki ariza qoldiring — o&apos;zimiz qo&apos;ng&apos;iroq
              qilamiz.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <a href={`tel:${site.phone}`} className="btn btn-primary">
                {site.phoneDisplay}
              </a>
              <a href="#ariza" className="btn btn-outline">
                Ariza qoldirish
              </a>
            </div>
          </div>
        </section>

        <section aria-labelledby="aloqa-filiallar" className="bg-ground-2 py-16 sm:py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="aloqa-filiallar" className="display text-3xl sm:text-4xl">
              Filiallar manzili
            </h2>
            <ul className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {branches.map((b) => {
                const phone = b.phone ?? site.phone;
                return (
                  <li key={b.id} className="card flex flex-col p-6">
                    <span className="numeral text-2xl text-purple">{b.city}</span>
                    <span className="mt-1 font-semibold text-ink">{b.landmark}</span>
                    <span className="mt-2 text-sm leading-relaxed text-ink-2">{b.address}</span>
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
                    <div className="mt-auto flex flex-wrap gap-2 pt-5">
                      <a href={mapsUrl(b)} target="_blank" rel="noopener noreferrer" className="btn btn-outline !min-h-10 !px-4 text-sm">
                        Xaritada
                      </a>
                      <Link href={`/filiallar/${b.id}`} className="btn btn-outline !min-h-10 !px-4 text-sm">
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
