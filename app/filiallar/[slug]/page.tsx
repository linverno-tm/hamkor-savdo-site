import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { branches, mapsUrl } from "@/data/branches";
import { categories } from "@/data/categories";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { branchCover, branchPhotos } from "@/lib/photos";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { LeadForm } from "@/components/sections/LeadForm";
import { BranchGallery } from "@/components/sections/BranchGallery";
import { Logo } from "@/components/ui/Logo";
import { SplitWords } from "@/components/ui/SplitWords";
import { OpenStatus } from "@/components/ui/OpenStatus";

/**
 * One indexable page per branch.
 *
 * The home page has to sell four cities at once, which means it ranks strongly
 * for none of them. Each of these targets a single city ("Asaka mebel do'koni")
 * with its own title, description, address and Store JSON-LD.
 */

export function generateStaticParams() {
  return branches.map((b) => ({ slug: b.id }));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const branch = branches.find((b) => b.id === slug);
  if (!branch) return {};

  /* Sarlavhaga mo'ljal ham kiradi. Aks holda ikkala Shahrixon filiali
     "Shahrixon filiali — HAMKOR SAVDO" bo'lib chiqadi: Google buni takroriy
     sarlavha deb hisoblaydi va ikkalasidan birini natijalardan tushirib
     yuboradi. Mo'ljal qidiruvda ham foydali — odam do'konni shu bo'yicha
     qidiradi. */
  const title = `${branch.city}: maishiy texnika, mebel va tilla muddatli to'lovga — ${branch.landmark} | HAMKOR SAVDO`;
  const description = `HAMKOR SAVDO ${branch.city} filiali — ${branch.address}. Tilla, texnika va mebel: ${site.facts.installmentMonthsMax} oygacha muddatli to'lov, ${site.freeDeliveryArea}da bepul yetkazib berish va o'rnatish.`;

  return {
    title,
    description,
    alternates: { canonical: absolute(`/filiallar/${branch.id}`) },
    openGraph: { title, description, type: "website", locale: "uz_UZ" },
  };
}

export default async function BranchPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const branch = branches.find((b) => b.id === slug);
  if (!branch) notFound();

  const phone = branch.phone ?? site.phone;
  const phoneLabel = branch.phoneDisplay ?? site.phoneDisplay;
  const others = branches.filter((b) => b.id !== branch.id);
  const photos = branchPhotos(branch.id, branch.city);
  // Sarlavha yonidagi asosiy surat — admin tanlagan muqova, bo'lmasa tashqi ko'rinish.
  const cover = branchCover(photos, branch.id);

  const [hoursOpens, hoursCloses] = branch.hours.split("–").map((t) => {
    const [h, m = "00"] = t.trim().split(":");
    return `${h.padStart(2, "0")}:${m.padStart(2, "0")}`;
  });

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Store",
    "@id": absolute(`/filiallar/${branch.id}`),
    name: `${site.name} — ${branch.city}, ${branch.landmark}`,
    slogan: site.tagline,
    telephone: phone,
    url: absolute(`/filiallar/${branch.id}`),
    address: {
      "@type": "PostalAddress",
      streetAddress: branch.landmark,
      addressLocality: branch.city,
      addressRegion: "Andijon viloyati",
      addressCountry: "UZ",
    },
    geo: {
      "@type": "GeoCoordinates",
      latitude: branch.lat,
      longitude: branch.lng,
    },
    // Dam olish kuni aytilmagan, shuning uchun berilgan soat 7 kunga
    // baravar qo'llaniladi deb olindi (app/page.tsx dagi bilan bir xil).
    openingHoursSpecification: {
      "@type": "OpeningHoursSpecification",
      dayOfWeek: [
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
      ],
      opens: hoursOpens,
      closes: hoursCloses,
    },
    sameAs: [branch.instagramUrl ?? site.instagramUrl, site.telegramUrl],
  };

  /* Sahifadagi "Bosh sahifa / Filiallar / {shahar}" izidan nusxa. Qidiruv
     natijasida manzil o'rniga shu iz ko'rinadi — u sahifadagi havolalar bilan
     bir xil bo'lishi kerak, aks holda Google uni e'tiborsiz qoldiradi. */
  const breadcrumbLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      { "@type": "ListItem", position: 1, name: "Bosh sahifa", item: absolute("/") },
      { "@type": "ListItem", position: 2, name: "Filiallar", item: absolute("/filiallar") },
      {
        "@type": "ListItem",
        position: 3,
        name: branch.city,
        item: absolute(`/filiallar/${branch.id}`),
      },
    ],
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify([jsonLd, breadcrumbLd]) }}
      />
      <Header />
      <main id="asosiy" tabIndex={-1}>
        {/* Binafsha sahna: chapda katta shahar nomi va ma'lumotlar, o'ngda
            filialning o'z surati (kirishda "darcha" bo'lib ochiladi). */}
        <section className="tp-hero bp-hero on-purple text-white">
          <div className="hero-bg" aria-hidden="true">
            <span className="hero-orb hero-orb-1" />
            <span className="hero-orb hero-orb-2" />
            <span className="hero-grid" />
            {cover ? null : <Logo variant="mark" className="hero-mark" />}
          </div>
          <div className="wrap relative grid items-center gap-10 lg:grid-cols-[1fr_1fr] lg:gap-14">
            <div>
              <nav aria-label="Qayerdaman" className="tp-crumbs in-1">
                <Link href="/" className="hover:text-white">
                  Bosh sahifa
                </Link>
                <span className="mx-2" aria-hidden="true">
                  /
                </span>
                {/* Bosh sahifadagi bo'limga emas, filiallar ro'yxatiga —
                    "yuqoriga" bosgan odam shu sahifaning haqiqiy ota-sahifasiga
                    tushsin (JSON-LD dagi BreadcrumbList ham shunga ishora
                    qiladi). */}
                <Link href="/filiallar" className="hover:text-white">
                  Filiallar
                </Link>
                <span className="mx-2" aria-hidden="true">
                  /
                </span>
                <span className="text-white">{branch.city}</span>
              </nav>

              <p className="hero-pill in-1 mt-7">
                <span className="hero-pill-dot" aria-hidden="true" />
                Filial {branch.index}
              </p>
              {/* Vizual katta shahar nomi bilan qoladi (brend uslubi), lekin
                  <h1> matni ikkala Shahrixon sahifasida bir xil bo'lmasin deb
                  mo'ljal ko'rinmas holda qo'shiladi — qidiruv tizimi va ekran
                  o'quvchisi uchun ikkalasi endi aniq farqlanadi. */}
              <h1 className="bp-city">
                <span className="hero-line">
                  <span className="in-2">
                    {branch.city}
                    <span className="sr-only"> — {branch.landmark}</span>
                  </span>
                </span>
              </h1>
              <p className="hero-lead in-4 !mt-3 !text-xl">{branch.landmark}</p>
              <OpenStatus hours={branch.hours} closed={branch.closed} />

              {branch.closed ? (
                <p className="mt-6 max-w-2xl rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                  <strong className="font-semibold">Filial vaqtincha yopiq.</strong>
                  {branch.closedNote ? ` ${branch.closedNote}` : null}
                </p>
              ) : null}

              <dl className="bp-facts in-5">
                <div>
                  <dt>Manzil</dt>
                  <dd>{branch.address}</dd>
                </div>
                <div>
                  <dt>Telefon</dt>
                  <dd>
                    <a href={`tel:${phone}`} className="font-semibold text-yellow hover:underline">
                      {phoneLabel}
                    </a>
                    {branch.phone ? null : (
                      <span className="mt-1 block text-xs text-on-purple-2">Bu filial uchun umumiy raqam</span>
                    )}
                  </dd>
                </div>
                <div>
                  <dt>Ish vaqti</dt>
                  <dd>{branch.hours}</dd>
                </div>
                {branch.instagram ? (
                  <div>
                    <dt>Instagram</dt>
                    <dd>
                      <a
                        href={branch.instagramUrl!}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-semibold text-yellow hover:underline"
                      >
                        @{branch.instagram}
                      </a>
                    </dd>
                  </div>
                ) : null}
              </dl>

              <div className="in-6 mt-9 flex flex-wrap gap-3">
                <a href={mapsUrl(branch)} target="_blank" rel="noopener noreferrer" className="btn btn-yellow btn-glow">
                  Xaritada ochish
                </a>
                <a href={`tel:${phone}`} className="btn btn-outline">
                  {phoneLabel}
                </a>
              </div>
            </div>

            {cover ? (
              <figure className="tp-photo bp-photo in-7 relative">
                <img
                  src={cover.src}
                  srcSet={`${cover.srcSmall} 480w, ${cover.src} 960w`}
                  sizes="(max-width: 1024px) 100vw, 50vw"
                  width={960}
                  height={720}
                  alt={cover.alt}
                  className="h-full w-full object-cover"
                  fetchPriority="high"
                  decoding="async"
                />
                <figcaption className="bp-cap">{cover.label}</figcaption>
              </figure>
            ) : null}
          </div>
        </section>

        <section aria-labelledby="branch-cats" className="sec">
          <div className="wrap">
            <div data-m>
              <h2 id="branch-cats" className="sh-title">
                <SplitWords text={`${branch.city} filialida nimalar bor?`} />
              </h2>
            </div>
            <div data-m="" data-m-stagger className="bp-cats">
              {categories.map((c) => (
                <div key={c.id} className="tile">
                  <h3 className="bp-cat-name numeral">{c.name}</h3>
                  <div>
                    <p className="tile-label">{c.kicker}</p>
                    <p className="tile-note mt-2 leading-relaxed">{c.description}</p>
                  </div>
                </div>
              ))}
            </div>

            <div data-m="zoom" className="bp-inst on-purple text-white">
              <div>
                <h3 className="bp-inst-title">
                  {site.facts.installmentMonthsMax} oygacha muddatli to&apos;lov
                </h3>
                {/* "Bepul" hech qachon yolg'iz turmasin: u faqat
                    `freeDeliveryArea` ichida amal qiladi. Aks holda boshqa
                    viloyatdagi mijoz bepul yetkazishni kutadi va qo'ng'iroqda
                    boshqacha javob eshitadi. */}
                <p className="mt-3 max-w-2xl leading-relaxed text-on-purple-2">
                  Pasport va plastik kartaning o&apos;zi kifoya. {site.freeDeliveryArea}da
                  xaridingizni bepul yetkazib beramiz va bepul o&apos;rnatib beramiz —{" "}
                  {site.deliveryArea} ham yetkazamiz.
                </p>
              </div>
              <Link href="/#muddatli-tolov" className="btn btn-yellow btn-glow shrink-0">
                Qanday ishlaydi?
              </Link>
            </div>
          </div>
        </section>

        <BranchGallery photos={photos} city={branch.city} slug={branch.id} />

        <LeadForm branchId={branch.id} />

        <section aria-labelledby="other-branches" className="sec">
          <div className="wrap">
            <div data-m>
              <h2 id="other-branches" className="sh-title">
                <SplitWords text="Boshqa filiallar" />
              </h2>
            </div>
            <ul data-m="" data-m-stagger className="bp-others">
              {others.map((b) => (
                <li key={b.id}>
                  <Link href={`/filiallar/${b.id}`} className="branch-item bp-other">
                    <span className="branch-idx numeral" aria-hidden="true">
                      {b.index}
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className="branch-city block">{b.city}</span>
                      <span className="branch-land block">{b.landmark}</span>
                    </span>
                    <span aria-hidden="true" className="bp-other-arrow">
                      &rarr;
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </section>
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}
