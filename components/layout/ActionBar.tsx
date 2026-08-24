import { site } from "@/data/site";

/**
 * A permanent call/apply bar pinned to the bottom of the screen on phones.
 *
 * Why it exists: the page is long, and someone who becomes interested halfway
 * down previously had to scroll back to the header to find a phone number.
 * Every extra step at that moment loses a customer. The bar keeps the two
 * actions that matter within thumb reach at all times.
 *
 * Phones only — on a laptop the header is always visible, so the same bar
 * would just be clutter. That cut-off lives in `.action-bar` in globals.css,
 * not in a Tailwind `lg:hidden` here: both are single-class selectors, so
 * whichever rule the stylesheet happened to emit last would win.
 *
 * Two rules it has to respect:
 *   - it sits below the mobile menu (z-55) and the photo lightbox (z-70), so
 *     opening either one covers it rather than leaving buttons floating on top;
 *   - `body` carries matching bottom padding on small screens, otherwise the
 *     bar would sit over the last rows of the footer.
 *
 * `#ariza` resolves to whichever form is on the current page: the general one
 * on the home page, the branch's own on a branch page.
 *
 * No JavaScript: two links and a media query.
 */
export function ActionBar() {
  return (
    <div className="action-bar" aria-label="Tezkor amallar">
      <a href={`tel:${site.phone}`} className="btn btn-primary flex-1">
        Qo&apos;ng&apos;iroq
      </a>
      <a href="#ariza" className="btn btn-outline flex-1">
        Ariza qoldirish
      </a>
    </div>
  );
}
