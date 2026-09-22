import Link from "next/link";
import { site } from "@/data/site";

/**
 * Muddatli to'lov — alohida, kuchli bo'lim (binafsha fon, bitta sariq CTA).
 * Batafsil shartlar /muddatli-tolov/ sahifasida; bu yerda — asosiysi.
 * `id="muddatli-tolov"` saqlanadi: filial sahifalari `/#muddatli-tolov` ga
 * havola beradi.
 */
export function Installment() {
  const M = site.facts.installmentMonthsMax;
  const points = [
    { title: "Pasport + karta", text: "Kafil ham, ma'lumotnoma ham kerak emas." },
    { title: "Bugun olib ketasiz", text: "Mahsulot hozir sizniki, to'lov oylarga bo'linadi." },
    { title: "Yo'q mahsulot ham", text: "Boshqa joyda ko'rganingizni ham rasmiylashtiramiz." },
  ];

  return (
    <section id="muddatli-tolov" aria-labelledby="installment-title" className="scroll-mt-20 bg-ground py-16 sm:py-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div
          data-reveal
          className="on-purple relative overflow-hidden rounded-[28px] bg-purple px-6 py-12 text-white sm:px-12 sm:py-16 lg:px-16"
        >
          {/* Yengil dekor — ikki xira doira, mazmundan chalg'itmaydi. */}
          <span aria-hidden="true" className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-white/[0.06]" />
          <span aria-hidden="true" className="pointer-events-none absolute -bottom-32 right-40 h-64 w-64 rounded-full bg-yellow/[0.08]" />

          <div className="relative grid items-center gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:gap-16">
            <div>
              <p className="inline-flex items-center gap-2 rounded-full bg-yellow px-4 py-1.5 text-sm font-semibold text-ink">
                {M} oygacha muddatli to&apos;lov
              </p>
              <h2 id="installment-title" className="display mt-5 text-3xl leading-tight sm:text-5xl">
                Kerakli mahsulotni hozir oling, to&apos;lovni qulay amalga oshiring
              </h2>
              <p className="mt-5 max-w-xl text-lg leading-relaxed text-on-purple-2">
                Pasport va plastik karta orqali xaridni rasmiylashtirish imkoniyati.
              </p>
              <Link href="/muddatli-tolov" className="btn btn-yellow btn-lift mt-8">
                Muddatli to&apos;lov haqida
                <span className="nudge" aria-hidden="true">&rarr;</span>
              </Link>
            </div>

            <ul className="grid gap-3">
              {points.map((p) => (
                <li key={p.title} className="rounded-[18px] border border-white/15 bg-white/[0.07] p-5">
                  <p className="font-semibold">{p.title}</p>
                  <p className="mt-1 text-sm leading-relaxed text-on-purple-2">{p.text}</p>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </section>
  );
}
