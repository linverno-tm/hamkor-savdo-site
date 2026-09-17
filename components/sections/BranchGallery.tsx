import type { Photo } from "@/lib/photos";

/**
 * A branch's own photography, laid out editorially rather than as a uniform
 * grid: the storefront runs full width as a wide establishing shot, the
 * interiors follow beneath it. That order matches how someone actually reads
 * the place — outside first, then in.
 *
 * Clicking a photo opens it full screen. The lightbox is pure CSS (`:target`),
 * so it costs no JavaScript and still works while the framework bundle is
 * downloading — the same rule the rest of this site follows.
 *
 * Plain <img> with an explicit srcset rather than next/image: this is a static
 * export on shared hosting, so there is no image optimiser at runtime;
 * `tools/rasm-tayyorlash.py` already wrote the two sizes at build time.
 * width/height are declared so the browser reserves the space before the file
 * arrives — on a slow connection that is the difference between a page that
 * settles and one that jumps while it loads.
 */
type CellKind = "big" | "small" | "wide" | "banner";

/**
 * Which shape each photo after the establishing shot takes.
 *
 * Photos come in threes: one large frame beside two smaller ones stacked in the
 * last column. On a phone the same three become one wide frame with the two
 * small ones side by side under it. Either way the block is a closed rectangle,
 * so the grid never trails a half-empty row.
 *
 * A leftover one or two at the end get their own closed shapes: a single photo
 * runs the full width, a pair splits two-thirds / one-third.
 *
 * The large frame always sits on the left. Alternating sides looks tempting but
 * needs explicit row placement to tile correctly — CSS auto-placement will not
 * back-fill the hole it leaves — and a repeated block reads as deliberate
 * anyway.
 */
function layoutCells(count: number): CellKind[] {
  const cells: CellKind[] = [];
  let placed = 0;

  while (count - placed >= 3) {
    cells.push("big", "small", "small");
    placed += 3;
  }

  if (count - placed === 1) cells.push("banner");
  else if (count - placed === 2) cells.push("wide", "small");

  return cells;
}

/* Tailwind sinf nomlarini shablon orqali yasab bo'lmaydi — u manba matnidan
   to'liq nomlarni qidiradi, shuning uchun to'liq yozilgan.
   Telefonda ustun ikkita, kompyuterda uchta.
   Balandlik katakchada (`cell-*`, globals.css), suratda emas — shuning uchun
   bitta qatordagi ikki surat hech qachon har xil bo'yda bo'lmaydi. */
const CELL_CLASS: Record<CellKind, string> = {
  big: "cell-big col-span-2 lg:row-span-2",
  small: "cell-small",
  wide: "cell-wide lg:col-span-2",
  banner: "cell-banner col-span-2 lg:col-span-3",
};

const CELL_SIZES: Record<CellKind, string> = {
  big: "(max-width: 1024px) 100vw, 66vw",
  small: "(max-width: 640px) 50vw, 33vw",
  wide: "(max-width: 1024px) 50vw, 66vw",
  banner: "100vw",
};

export function BranchGallery({
  photos,
  city,
  slug,
  heading,
}: {
  photos: Photo[];
  city: string;
  slug: string;
  heading?: string;
}) {
  if (photos.length === 0) return null;

  const [lead, ...rest] = photos;
  const id = (i: number) => `foto-${slug}-${i}`;
  const cells = layoutCells(rest.length);

  return (
    <section id="suratlar" aria-labelledby="gallery-title" className="bg-ground py-16 sm:py-20">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <p className="kicker">Haqiqiy do&apos;kon</p>
        <h2 id="gallery-title" className="display mt-3 text-3xl sm:text-4xl">
          {heading ?? `${city} filiali — suratlarda`}
        </h2>
        <p className="mt-3 text-sm text-ink-3">
          Kattalashtirib ko&apos;rish uchun suratni bosing.
        </p>

        <div data-reveal className="mt-10">
          {/* Establishing shot */}
          <a href={`#${id(0)}`} className="photo photo-lead group block">
            <img
              src={lead.src}
              srcSet={`${lead.srcSmall} 480w, ${lead.src} 960w`}
              sizes="(max-width: 1024px) 100vw, 1200px"
              width={960}
              height={720}
              alt={lead.alt}
              loading="eager"
              decoding="async"
            />
            <span className="photo-cap">{lead.label}</span>
          </a>

          {rest.length > 0 ? (
            <ul className="gallery mt-4">
              {rest.map((p, i) => {
                const kind = cells[i];
                return (
                  <li key={p.src} className={CELL_CLASS[kind]}>
                    <a href={`#${id(i + 1)}`} className="photo group block">
                      <img
                        src={p.src}
                        srcSet={`${p.srcSmall} 480w, ${p.src} 960w`}
                        sizes={CELL_SIZES[kind]}
                        width={960}
                        height={720}
                        alt={p.alt}
                        loading="lazy"
                        decoding="async"
                      />
                      <span className="photo-cap">{p.label}</span>
                    </a>
                  </li>
                );
              })}
            </ul>
          ) : null}
        </div>
      </div>

      {/* Lightboxes. Hidden until their id is the URL fragment.
          Oldingi/keyingi tugmalari qo'shni suratning ankoriga to'g'ridan-to'g'ri
          havola — shuning uchun galereya ichida sakrab yurish uchun ham
          JavaScript kerak emas, faqat hash o'zgaradi. */}
      {photos.map((p, i) => {
        const prev = photos.length > 1 ? (i - 1 + photos.length) % photos.length : null;
        const next = photos.length > 1 ? (i + 1) % photos.length : null;
        return (
          <div
            key={p.src}
            id={id(i)}
            className="lightbox"
            role="dialog"
            aria-label={p.alt}
            data-prev-id={prev !== null ? id(prev) : undefined}
            data-next-id={next !== null ? id(next) : undefined}
          >
            <a href="#suratlar" className="lightbox-close" aria-label="Yopish">
              &times;
            </a>
            <a href="#suratlar" className="lightbox-scrim" tabIndex={-1} aria-hidden="true" />
            {prev !== null ? (
              <a href={`#${id(prev)}`} className="lightbox-nav lightbox-prev" aria-label="Oldingi surat">
                &lsaquo;
              </a>
            ) : null}
            {next !== null ? (
              <a href={`#${id(next)}`} className="lightbox-nav lightbox-next" aria-label="Keyingi surat">
                &rsaquo;
              </a>
            ) : null}
            <figure className="lightbox-figure">
              <img src={p.src} alt={p.alt} width={960} height={720} decoding="async" />
              <figcaption>
                {p.label} — {city}
                {photos.length > 1 ? (
                  <span className="lightbox-count">
                    {" "}
                    · {i + 1}/{photos.length}
                  </span>
                ) : null}
              </figcaption>
            </figure>
          </div>
        );
      })}
    </section>
  );
}
