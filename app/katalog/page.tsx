import Link from "next/link";
import type { Metadata } from "next";
import { content, type CategoryId } from "@/lib/content";
import { absolute } from "@/lib/seo";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ActionBar } from "@/components/layout/ActionBar";
import { LeadForm } from "@/components/sections/LeadForm";
import { CATEGORY_LABEL, ProductCard } from "@/components/sections/Catalog";

const hasProducts = content.products.length > 0;

export const metadata: Metadata = {
  title: "Katalog — HAMKOR SAVDO",
  description: "HAMKOR SAVDO mahsulotlari: tilla, texnika va mebel — narxlari va mavjudligi.",
  alternates: { canonical: absolute("/katalog") },
  // Bo'sh katalog qidiruvda chiqmasin.
  robots: { index: hasProducts, follow: true },
};

const ORDER: CategoryId[] = ["texnika", "tilla", "mebel"];

export default function CatalogPage() {
  const groups = ORDER.map((c) => ({
    id: c,
    items: content.products.filter((p) => p.category === c),
  })).filter((g) => g.items.length > 0);

  return (
    <>
      <Header />
      <main id="asosiy" tabIndex={-1}>
        <section className="mx-auto max-w-7xl px-5 pb-16 pt-32 sm:px-8 sm:pt-40">
          <nav aria-label="Yo'l" className="text-sm text-ink-3">
            <Link href="/" className="tap hover:text-purple">
              Bosh sahifa
            </Link>
            <span className="px-2" aria-hidden="true">
              /
            </span>
            <span className="text-ink-2">Katalog</span>
          </nav>
          <p className="kicker mt-6">Katalog</p>
          <h1 className="display mt-3 text-4xl leading-[1.05] sm:text-5xl">Mahsulotlar</h1>

          {groups.length === 0 ? (
            <p className="mt-6 max-w-xl text-lg leading-relaxed text-ink-2">
              Katalog tayyorlanmoqda. Kerakli mahsulot haqida so&apos;rash uchun ariza qoldiring
              yoki qo&apos;ng&apos;iroq qiling.
            </p>
          ) : (
            <>
              <nav aria-label="Bo'limlar" className="mt-8 flex flex-wrap gap-2">
                {groups.map((g) => (
                  <a
                    key={g.id}
                    href={`#${g.id}`}
                    className="tap numeral rounded-full border-2 border-purple-100 px-5 py-2 text-lg tracking-[0.12em] text-purple hover:border-purple hover:bg-purple hover:text-white"
                  >
                    {CATEGORY_LABEL[g.id]} ({g.items.length})
                  </a>
                ))}
              </nav>
              {groups.map((g) => (
                <div key={g.id} id={g.id} className="scroll-mt-28 pt-12">
                  <h2 className="display text-3xl">{CATEGORY_LABEL[g.id]}</h2>
                  <ul className="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    {g.items.map((p) => (
                      <ProductCard key={p.id} p={p} />
                    ))}
                  </ul>
                </div>
              ))}
            </>
          )}
        </section>
        <LeadForm />
      </main>
      <Footer />
      <ActionBar />
    </>
  );
}
