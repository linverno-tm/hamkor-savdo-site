import Link from "next/link";
import { content, formatPrice, type CategoryId, type Product } from "@/lib/content";
import { uploadedImage } from "@/lib/images";
import { PhotoPlaceholder } from "@/components/ui/PhotoPlaceholder";

export const CATEGORY_LABEL: Record<CategoryId, string> = {
  texnika: "Texnika",
  tilla: "Tilla",
  mebel: "Mebel",
};

export function ProductCard({ p }: { p: Product }) {
  const img = uploadedImage(p.image);
  return (
    <li className="card flex flex-col overflow-hidden">
      {img ? (
        <img
          src={img.src}
          srcSet={`${img.srcSmall} 480w, ${img.src} 960w`}
          sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
          width={960}
          height={720}
          alt={p.name}
          loading="lazy"
          decoding="async"
          className="block aspect-[4/3] w-full object-cover"
        />
      ) : (
        <PhotoPlaceholder className="aspect-[4/3]" caption="Surat tayyorlanmoqda" />
      )}
      <div className="flex flex-1 flex-col p-5">
        <span className="text-xs font-semibold uppercase tracking-wider text-ink-3">
          {CATEGORY_LABEL[p.category]}
        </span>
        <h3 className="mt-1 font-semibold leading-snug text-ink">{p.name}</h3>
        {p.note ? <p className="mt-2 text-sm leading-relaxed text-ink-2">{p.note}</p> : null}
        <p className="numeral mt-auto pt-4 text-2xl text-purple">{formatPrice(p.price)}</p>
        <p className={`mt-1 text-xs font-semibold ${p.inStock ? "text-emerald-700" : "text-ink-3"}`}>
          {p.inStock ? "Mavjud" : "Buyurtma asosida"}
        </p>
      </div>
    </li>
  );
}

/** Bosh sahifadagi qisqa katalog. Mahsulot kiritilmaguncha ko'rinmaydi. */
export function Catalog() {
  const products = content.products;
  if (products.length === 0) return null;
  const shown = products.slice(0, 8);

  return (
    <section id="katalog" aria-labelledby="catalog-title" className="bg-ground-2 py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <p className="kicker">Katalog</p>
        <h2 id="catalog-title" className="display mt-3 text-4xl sm:text-5xl">
          Mashhur mahsulotlar
        </h2>
        <p className="mt-4 max-w-2xl text-lg leading-relaxed text-ink-2">
          Narxlar ma&apos;lumot uchun. Muddatli to&apos;lov shartlarini qo&apos;ng&apos;iroqda yoki ariza
          orqali aniqlaymiz.
        </p>
        <ul data-reveal className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {shown.map((p) => (
            <ProductCard key={p.id} p={p} />
          ))}
        </ul>
        <div className="mt-8 flex flex-wrap gap-3">
          <Link href="/katalog" className="btn btn-primary">
            Barcha mahsulotlar ({products.length})
          </Link>
          <a href="#ariza" className="btn btn-outline">
            Ariza qoldirish
          </a>
        </div>
      </div>
    </section>
  );
}
