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

/**
 * Hero ostidagi to'rtta yo'nalish (`id="yonalishlar"` — hero CTA shu yerga).
 *
 * Kompyuterda: kartalar yelpig'ich bo'lib binafsha sahna chetidan
 * chiqadi va sahifa aylantirilganda tekislanadi (app/home.css > .cats,
 * `animation-timeline: view()`). Telefonda: gorizontal suriladigan qator,
 * keyingi karta chetdan ko'rinib turadi — "yana bor" ishorasi.
 *
 * Kursor kelganda: surat sekin kattalashadi, matn ko'tariladi, sariq o'q
 * aylanib chiqadi.
 */
export function CategoryCards() {
  return (
    <nav id="yonalishlar" aria-label="Mahsulot kategoriyalari" className="scroll-mt-24">
      <ul className="cats">
        {CATEGORIES.map((c, i) => (
          <li key={c.href} className="cats-item" style={{ ["--i" as string]: i }}>
            <Link href={c.href} className="cat group">
              <img
                src={`/yonalishlar/${c.photo}-900.webp`}
                srcSet={`/yonalishlar/${c.photo}-480.webp 480w, /yonalishlar/${c.photo}-900.webp 900w`}
                sizes="(max-width: 1024px) 70vw, 300px"
                width={900}
                height={675}
                alt={c.alt}
                // Telefonda 3- va 4-karta ekran tashqarisida (gorizontal qator) — keyin yuklanadi.
                loading={i >= 2 ? "lazy" : undefined}
                decoding="async"
                className={`cat-img ${c.pos}`}
              />
              <span className="cat-shade" aria-hidden="true" />
              <span className="cat-body">
                <span className="cat-title">{c.title}</span>
                <span className="cat-text">{c.text}</span>
              </span>
              <span aria-hidden="true" className="cat-arrow">
                &rarr;
              </span>
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}
