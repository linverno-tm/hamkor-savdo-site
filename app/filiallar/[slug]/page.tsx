import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { branches, mapsUrl } from "@/data/branches";
import { categories } from "@/data/categories";
import { site } from "@/data/site";
import { absolute } from "@/lib/seo";
import { branchPhotos } from "@/lib/photos";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { LeadForm } from "@/components/sections/LeadForm";
import { BranchGallery } from "@/components/sections/BranchGallery";
import { Logo } from "@/components/ui/Logo";

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
  const title = `${branch.city}, ${branch.landmark} — HAMKOR SAVDO`;
  const description = `HAMKOR SAVDO ${branch.city} filiali — ${branch.address}. Tilla, texnika va mebel: ${site.facts.installmentMonthsMax} oygacha muddatli to'lov, bepul yetkazib berish va o'rnatish.`;

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

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <Header />
      <main>
        <section className="relative overflow-hidden pt-28 sm:pt-32">
          <Logo
            variant="mark"
            className="pointer-events-none absolute right-12 top-28 hidden h-72 w-auto text-purple-50 lg:block"
          />
          <div className="relative mx-auto max-w-7xl px-5 pb-16 sm:px-8">
            <nav aria-label="Qayerdaman" className="text-sm text-ink-3">
              <Link href="/" className="hover:text-purple">
                Bosh sahifa
              </Link>
              <span className="mx-2" aria-hidden="true">
                /
              </span>
              <Link href="/#filiallar" className="hover:text-purple">
                Filiallar
              </Link>
              <span className="mx-2" aria-hidden="true">
                /
              </span>
              <span className="text-ink-2">{branch.city}</span>
            </nav>

            <p className="kicker mt-8">Filial {branch.index}</p>
            {/* Vizual katta shahar nomi bilan qoladi (brend uslubi), lekin
                <h1> matni ikkala Shahrixon sahifasida bir xil bo'lmasin deb
                mo'ljal ko'rinmas holda qo'shiladi — qidiruv tizimi va ekran
                o'quvchisi uchun ikkalasi endi aniq farqlanadi. */}
            <h1 className="display mt-3 text-5xl sm:text-6xl lg:text-7xl">
              {branch.city}
              <span className="sr-only"> — {branch.landmark}</span>
            </h1>
            <p className="mt-4 max-w-2xl text-xl text-ink-2">{branch.landmark}</p>

            {branch.closed ? (
              <p className="mt-6 max-w-2xl rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                <strong className="font-semibold">Filial vaqtincha yopiq.</strong>
                {branch.closedNote ? ` ${branch.closedNote}` : null}
              </p>
            ) : null}

            <dl className="mt-10 grid max-w-3xl gap-6 sm:grid-cols-3">
              <div>
                <dt className="text-xs font-semibold uppercase tracking-wider text-ink-3">
                  Manzil
                </dt>
                <dd className="mt-2 text-ink-2">{branch.address}</dd>
              </div>
              <div>
                <dt className="text-xs font-semibold uppercase tracking-wider text-ink-3">
                  Telefon
                </dt>
                <dd className="mt-2">
                  <a href={`tel:${phone}`} className="font-semibold text-purple hover:underline">
                    {phoneLabel}
                  </a>
                  {branch.phone ? null : (
                    <span className="mt-1 block text-xs text-ink-3">
                      Bu filial uchun umumiy raqam
                    </span>
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-xs font-semibold uppercase tracking-wider text-ink-3">
                  Ish vaqti
                </dt>
                <dd className="mt-2 text-ink-2">{branch.hours}</dd>
              </div>
              {branch.instagram ? (
                <div>
                  <dt className="text-xs font-semibold uppercase tracking-wider text-ink-3">
                    Instagram
                  </dt>
                  <dd className="mt-2">
                    <a
                      href={branch.instagramUrl!}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="font-semibold text-purple hover:underline"
                    >
                      @{branch.instagram}
                    </a>
                  </dd>
                </div>
              ) : null}
            </dl>

            <div className="mt-10 flex flex-wrap gap-3">
              <a
                href={mapsUrl(branch)}
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-primary"
              >
                Xaritada ochish
              </a>
              <a href={`tel:${phone}`} className="btn btn-outline">
                {phoneLabel}
              </a>
            </div>
          </div>
        </section>

        <section aria-labelledby="branch-cats" className="bg-ground-2 py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="branch-cats" className="display text-3xl sm:text-4xl">
              {branch.city} filialida nimalar bor?
            </h2>
            <div className="mt-10 grid gap-5 lg:grid-cols-3">
              {categories.map((c) => (
                <div key={c.id} className="card p-7">
                  <h3 className="numeral text-4xl tracking-[0.04em] text-purple">{c.name}</h3>
                  <p className="mt-2 font-semibold text-ink">{c.kicker}</p>
                  <p className="mt-3 leading-relaxed text-ink-2">{c.description}</p>
                </div>
              ))}
            </div>

            <div className="on-purple mt-6 rounded-[28px] bg-purple p-8 text-white sm:p-10">
              <h3 className="display text-2xl sm:text-3xl">
                {site.facts.installmentMonthsMax} oygacha muddatli to&apos;lov
              </h3>
              <p className="mt-3 max-w-2xl leading-relaxed text-on-purple-2">
                Pasport va plastik kartaning o&apos;zi kifoya. Xaridingizni bepul yetkazib beramiz
                va bepul o&apos;rnatib beramiz.
              </p>
              <Link href="/#muddatli-tolov" className="btn btn-yellow mt-7">
                Qanday ishlaydi?
              </Link>
            </div>
          </div>
        </section>

        <BranchGallery photos={photos} city={branch.city} slug={branch.id} />

        <LeadForm branchId={branch.id} />

        <section aria-labelledby="other-branches" className="bg-ground-2 py-20">
          <div className="mx-auto max-w-7xl px-5 sm:px-8">
            <h2 id="other-branches" className="display text-3xl">
              Boshqa filiallar
            </h2>
            <ul className="mt-8 grid gap-4 sm:grid-cols-3">
              {others.map((b) => (
                <li key={b.id}>
                  <Link
                    href={`/filiallar/${b.id}`}
                    className="card card-hover flex h-full flex-col p-6"
                  >
                    <span className="numeral text-2xl text-purple">{b.city}</span>
                    <span className="mt-1 text-sm text-ink-2">{b.landmark}</span>
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
