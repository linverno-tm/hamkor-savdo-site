import Link from "next/link";

/**
 * "Mahsulot kategoriyalari" — to'rtta yo'nalish, har biri o'z sahifasiga.
 * Hero'ning pastida turadi: sahifa ochilishi bilan do'kon nima sotishi ko'rinadi.
 *
 * Katta surat, ustida to'q gradient, nom, qisqa tavsif va o'q. Hover:
 * surat sekin kattalashadi, kartochka 5px ko'tariladi, o'q oldinga siljiydi
 * (globals.css > .cat-card). Skuter surati — Pexels (bepul litsenziya):
 * https://www.pexels.com/photo/15675779/; do'konning o'z surati bo'lsa,
 * public/yonalishlar/skuter-*.webp ni almashtiring.
 */
const CATEGORIES = [
  {
    href: "/texnika",
    title: "Texnika",
    text: "Muzlatgich, kir yuvish mashinasi, konditsioner va boshqa texnikalar.",
    photo: "texnika",
    alt: "HAMKOR SAVDO maishiy texnika bo'limi",
    pos: "object-center",
  },
  {
    href: "/tilla",
    title: "Tilla",
    text: "Uzuk, sirg'a, zanjir va boshqa zargarlik buyumlari.",
    photo: "tilla",
    alt: "HAMKOR SAVDO zargarlik peshtaxtasi",
    // Suratning pastidagi "HAMKOR TILLA BUYUMLARI" yozuvi matn ostida qolmasin.
    pos: "object-[center_25%]",
  },
  {
    href: "/mebel",
    title: "Mebel",
    text: "Yotoqxona, oshxona, yumshoq mebel va boshqa mahsulotlar.",
    photo: "mebel",
    alt: "HAMKOR SAVDO mebel bo'limi — stol va o'rindiqlar",
    pos: "object-center",
  },
  {
    href: "/skuter",
    title: "Skuterlar",
    text: "Ishga, o'qishga va bozorga qulay transport.",
    photo: "skuter",
    alt: "Elektr skuter",
    pos: "object-center",
  },
];

/** Hero ichidagi to'liq kenglikdagi qator (`id="yonalishlar"` — hero CTA shu yerga olib boradi). */
export function CategoryCards() {
  return (
    <nav id="yonalishlar" aria-label="Mahsulot kategoriyalari" className="scroll-mt-24">
      <ul className="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
        {CATEGORIES.map((c) => (
          <li key={c.href}>
            <Link
              href={c.href}
              className="cat-card group relative block aspect-[4/5] overflow-hidden rounded-[24px] bg-ground-2 lg:aspect-[5/6]"
            >
              <img
                src={`/yonalishlar/${c.photo}-900.webp`}
                srcSet={`/yonalishlar/${c.photo}-480.webp 480w, /yonalishlar/${c.photo}-900.webp 900w`}
                sizes="(max-width: 1024px) 50vw, 300px"
                width={900}
                height={675}
                alt={c.alt}
                decoding="async"
                className={`cat-card-img h-full w-full object-cover ${c.pos}`}
              />
              <span className="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4 pt-20 text-white [background:linear-gradient(to_top,rgba(26,17,48,0.92),rgba(26,17,48,0.45)_55%,transparent)] sm:p-6 sm:pt-28">
                <span>
                  <span className="display block text-xl sm:text-3xl">{c.title}</span>
                  <span className="mt-1.5 hidden text-sm leading-snug text-white/80 sm:block">{c.text}</span>
                </span>
                <span
                  aria-hidden="true"
                  className="cat-card-arrow hidden h-11 w-11 shrink-0 items-center justify-center rounded-[12px] bg-yellow text-lg text-ink sm:flex"
                >
                  &rarr;
                </span>
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}
