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

  return (
    <section id="suratlar" aria-labelledby="gallery-title" className="bg-ground py-20 sm:py-24">
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
            <ul className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
              {rest.map((p, i) => (
                <li key={p.src}>
                  <a href={`#${id(i + 1)}`} className="photo group block">
                    <img
                      src={p.src}
                      srcSet={`${p.srcSmall} 480w, ${p.src} 960w`}
                      sizes="(max-width: 640px) 50vw, 33vw"
                      width={960}
                      height={720}
                      alt={p.alt}
                      loading="lazy"
                      decoding="async"
                    />
                    <span className="photo-cap">{p.label}</span>
                  </a>
                </li>
              ))}
            </ul>
          ) : null}
        </div>
      </div>

      {/* Lightboxes. Hidden until their id is the URL fragment. */}
      {photos.map((p, i) => (
        <div key={p.src} id={id(i)} className="lightbox" role="dialog" aria-label={p.alt}>
          <a href="#suratlar" className="lightbox-close" aria-label="Yopish">
            &times;
          </a>
          <a href="#suratlar" className="lightbox-scrim" tabIndex={-1} aria-hidden="true" />
          <figure className="lightbox-figure">
            <img src={p.src} alt={p.alt} width={960} height={720} decoding="async" />
            <figcaption>
              {p.label} — {city}
            </figcaption>
          </figure>
        </div>
      ))}
    </section>
  );
}
