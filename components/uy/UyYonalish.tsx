import Link from "next/link";
import { VITRINA } from "@/lib/vitrina";

/**
 * Yo'nalishlar — har biri "uy" shaklidagi oyna: ichida filialdagi haqiqiy
 * bo'lim surati, tomi ustidan esa shu bo'limning mahsuloti (3D modelning
 * shaffof tasviri, public/3d) chiqib turadi.
 *
 * To'r bir xil kartalar emas: texnika — eng katta (asosiy savdo), qolganlari
 * pog'onama-pog'ona. 3D'ni aylantirib ko'rish /katalog va yo'nalish
 * sahifalarida (Vitrina3D) — bosh sahifani og'irlashtirmaydi.
 */
const ITEMS = [
  {
    id: "texnika",
    href: "/texnika",
    name: "Texnika",
    line: "Muzlatgich, kir yuvish mashinasi, oshxona texnikasi",
    photo: "texnika",
    alt: "Filialdagi maishiy texnika bo'limi",
    product: "muzlatgich",
    sizes: "(min-width: 1024px) 460px, 92vw",
    prSizes: "(min-width: 1024px) 185px, 37vw",
  },
  {
    id: "mebel",
    href: "/mebel",
    name: "Mebel",
    line: "Yotoqxonadan oshxonagacha",
    photo: "mebel",
    alt: "Filialdagi mebel bo'limi — oshxona stoli va o'rindiqlar",
    product: "divan",
    sizes: "(min-width: 1024px) 380px, 44vw",
    prSizes: "(min-width: 1024px) 175px, 21vw",
  },
  {
    id: "tilla",
    href: "/tilla",
    name: "Tilla",
    line: "Sovchilik, to'y va muhim kunlar uchun",
    photo: "tilla",
    alt: "Filialdagi zargarlik bo'limi peshtaxtasi",
    product: "uzuk",
    sizes: "(min-width: 1024px) 380px, 44vw",
    prSizes: "(min-width: 1024px) 175px, 21vw",
  },
  {
    id: "skuter",
    href: "/skuter",
    name: "Skuter",
    line: "Bugun minib ketasiz",
    photo: "skuter",
    alt: "Skuter",
    product: "skuter",
    sizes: "(min-width: 1024px) 720px, 92vw",
    prSizes: "(min-width: 1024px) 175px, 28vw",
  },
] as const;

export function UyYonalish() {
  const used = new Set<string>(ITEMS.map((i) => i.product));
  const credits = VITRINA.filter((v) => used.has(v.id));

  return (
    <section id="yonalishlar" aria-labelledby="yon-title" className="uy-sec">
      <div className="u-wrap">
        <h2 id="yon-title" className="u-h2">
          Uyga nima kerak bo&apos;lsa
        </h2>

        <ul className="yon" data-m="" data-m-stagger>
          {ITEMS.map((it) => (
            <li key={it.id} className={`yon-i yon-i--${it.id}`}>
              <Link href={it.href} className="yon-a">
                <span className="yon-uy">
                  <img
                    src={`/yonalishlar/${it.photo}-480.webp`}
                    srcSet={`/yonalishlar/${it.photo}-480.webp 480w, /yonalishlar/${it.photo}-640.webp 640w, /yonalishlar/${it.photo}-900.webp 900w`}
                    sizes={it.sizes}
                    width={480}
                    height={360}
                    alt={it.alt}
                    loading="lazy"
                    decoding="async"
                  />
                </span>
                <img
                  className="yon-pr"
                  src={`/3d/${it.product}-320.webp`}
                  srcSet={`/3d/${it.product}-320.webp 320w, /3d/${it.product}.webp 640w`}
                  sizes={it.prSizes}
                  width={640}
                  height={640}
                  alt=""
                  loading="lazy"
                  decoding="async"
                />
                <span className="yon-txt">
                  <span className="yon-name">{it.name}</span>
                  <span className="yon-line">{it.line}</span>
                </span>
              </Link>
            </li>
          ))}
        </ul>

        <div className="yon-foot">
          <Link href="/katalog" className="u-link">
            3D vitrinada aylantirib ko&apos;ring
          </Link>
          <details className="u-credit">
            <summary>3D modellar mualliflari</summary>
            <ul>
              {credits.map((c) => (
                <li key={c.id}>
                  <a href={c.credit.url} target="_blank" rel="noopener noreferrer">
                    {c.credit.title}
                  </a>{" "}
                  — {c.credit.author}, {c.credit.license}
                </li>
              ))}
            </ul>
          </details>
        </div>
      </div>
    </section>
  );
}
