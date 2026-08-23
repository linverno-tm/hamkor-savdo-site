# HAMKOR SAVDO — Digital Flagship

Brand-introduction website for HAMKOR SAVDO — a four-branch retail chain in the Andijan
region of Uzbekistan (tilla · texnika · mebel). Not a catalogue: it introduces
the company, its offer, its four branches and every way to reach it — one story page plus a
page per branch.

Next.js 16 (App Router) · React 19 · TypeScript · Tailwind CSS v4 · **no animation library**

```bash
npm run dev
npm run build
```

## Brand compliance

Everything visual comes from the official guidebook (`Guidebook HAMKOR SAVDO.pdf`):

| | Guidebook | Here |
|---|---|---|
| Corporate colour | `#5a3089` + `#FFFFFF` | `--purple`, `--ground` |
| Secondary key colour | `#dfd01f` (yellow) | `--yellow` — blocks, rules and badges only |
| Primary typeface | TT Commons *(commercial)* | **Outfit** — same geometric-sans role |
| Secondary typeface | Bebas Neue Pro *(commercial)* | **Bebas Neue** — numerals, indices, labels |

The logo in [`components/ui/Logo.tsx`](components/ui/Logo.tsx) is the official artwork traced
to vector from `logo.psd`/`logo.png`, verified at **97.6% pixel agreement** against the source
bitmap. It ships as one path that recolours through `currentColor`, in both the full lockup and
the H monogram alone. The guidebook's prohibitions are respected: no shadow, glow or outline on
the mark, no rotation, no distortion, no recolouring of individual parts, and it is never placed
over a busy background.

Yellow never carries text on white — that pair measures 1.59:1. Every text pair actually used is
at least 4.5:1 (ink on white 18:1, secondary 7.7:1, tertiary 5.6:1, white on purple 9.5:1,
yellow on purple 5.9:1).

## Verified facts vs placeholders

Sources are recorded in [`data/site.ts`](data/site.ts), all checked 2026-08-22:

- **Official Telegram channel** [t.me/hamkorsavdouz](https://t.me/hamkorsavdouz) — "Andijon
  viloyati bo'ylab", 7000+ mahsulot, Tilla | Texnikalar | Mebellar, 24 oygacha muddatli to'lov,
  pasport va plastik kifoya, **bepul yetkazish/o'rnatish**, bot `@hamkor_taklifbot`.
- **Instagram bio** `@hamkorsavdo.uz` — "Oilangizga ishonchli hamkor!", 41K followers.
- **Guidebook** — colours, fonts, logo rules, the `hamkorsavdo.uz` domain.
- **Branches** — supplied directly by the business, in [`data/branches.ts`](data/branches.ts).

> The Telegram handle is `@hamkorsavdouz`, per the guidebook and the live channel. An older
> project used `@hksavdo`; that one is not the official channel.

**Not published, so not invented** (`null` in `site.unpublished`): opening hours, warranty
length, founding year, company history. Branch 04 (Andijon) has no direct phone yet, so its
card falls back to the main company number and says so. Fill any of these in and the matching
UI appears on its own.

**Media placeholders:** the three frames in "Haqiqiy do'kon" are visibly labelled
`Surat joyi` stand-ins for the company's own photography — see
`[REAL STORE PHOTO REQUIRED]` in [`components/sections/Store.tsx`](components/sections/Store.tsx).
No stock imagery impersonates the business and there are no invented testimonials; the customer
block links to the real Instagram highlight instead.

## Built for slow connections

This was the governing constraint, and it drove two decisions.

**No animation library.** GSAP + ScrollTrigger + Lenis would have cost roughly 80 KB gzipped.
All motion is CSS instead — transitions, keyframes, a marquee, and `animation-timeline: view()`
parallax where supported — driven by about a kilobyte of JavaScript in
[`components/ui/Reveal.tsx`](components/ui/Reveal.tsx): one IntersectionObserver plus a
number count-up.

**The page works before the JavaScript arrives.** The framework baseline is ~190 KB gz and
cannot be removed while staying on the App Router (measured: stripping *every* client component
saved only 9 KB). So nothing important depends on it:

| | gzipped |
|---|---|
| Fully readable and navigable page (HTML + CSS) | **35 KB** |
| Complete first load including deferred JS | ~203 KB |

The mobile menu is a native `<details>`/`<summary>` disclosure, so it opens, closes, announces
its state and takes keyboard input with zero JavaScript. Every phone number, address, map link
and anchor is a plain link. Counters are server-rendered at their final value and only animate
when JS is present. JavaScript adds Escape-to-close, scroll-reveal and count-up — nothing else.

## Accessibility

Semantic landmarks, one `h1`, skip link, 3px focus rings, decorative indices marked
`aria-hidden`, and no focusable content behind the closed menu (hidden in CSS rather than
trusting the disclosure to do it). `prefers-reduced-motion` leaves every block and number in its
final state and disables the reveal system entirely.

Reveal transitions are armed one reflow *after* the hidden state is applied, so content that has
already painted is never seen fading out before it fades in.

## Structure

```
app/
  page.tsx              home (Store JSON-LD for all four branches)
  filiallar/[slug]/     one prerendered page per branch
  rahmat/               where a no-JS form submission lands
  api/lead/             enquiry -> Telegram
  icon.svg              favicon, generated from the logo monogram
  opengraph-image.tsx   1200x630 link-preview card, built once
  _og-fonts/            TTFs + monogram used only by the OG generator (never shipped)
  sitemap.ts robots.ts globals.css layout.tsx
components/
  layout/       Header (no-JS menu), Footer
  sections/     Hero About Scale Categories Why Installment Branches Store
                LeadForm Contact FinalCta
  ui/           Logo (traced vector), Reveal (the whole motion system), SectionHeading
data/           site branches categories advantages installment navigation
lib/            format (deterministic digit grouping), seo (canonical origin)
```

## Pages and SEO

The home page sells four cities at once, which means it ranks strongly for none of them, so
each branch also gets its own prerendered page at `/filiallar/<slug>` with its own title,
description, canonical and `Store` JSON-LD. The home page publishes the same four stores as an
`@graph`, each carrying the `@id` of its branch page so the two are recognised as one entity.
`sitemap.xml` and `robots.txt` are generated; the branch cards, the footer and the sitemap all
link the pages, so none of them is an orphan.

The link-preview card matters more than usual here, because the business shares its link mainly
through Telegram: `app/opengraph-image.tsx` renders a branded 1200x630 PNG at build time using
the real brand fonts, read from disk so a build never depends on the network. Those TTFs are
build-time only and are never sent to a browser.

Set `NEXT_PUBLIC_SITE_URL` before deploying — canonicals, the sitemap and JSON-LD use it.

## Enquiry form

`components/sections/LeadForm.tsx` posts to `app/api/lead/route.ts`, which forwards the enquiry
to the company Telegram. It is a real `<form>`, so it works with JavaScript disabled or still
downloading: the route answers a normal submission with a 303 to `/rahmat`, and answers a
`fetch` with JSON so the page can report the result inline instead.

Configure in `.env.local` (see `.env.example`):

```
TELEGRAM_BOT_TOKEN=...
TELEGRAM_CHAT_ID=...
```

Until those are set the form fails loudly and points the visitor at the phone number — an
unconfigured form that silently swallows leads is worse than one that admits it. A honeypot
field catches bots and answers them with a fake success so they do not learn to adapt. The
Telegram call sends no `parse_mode`, so visitor text can never be interpreted as markup.

## Verified

- Production build: static, no type errors.
- No horizontal overflow at 375 px or 1280 px; the only over-wide element is the marquee track
  inside its `overflow-hidden` container.
- Mobile menu: opens and closes, exposes six links plus contact actions when open, and exposes
  nothing when closed.
- Reveal contract measured in-browser: hidden `opacity 0 / translateY(22px)` applied without a
  transition, armed at 620 ms, settling to `opacity 1 / none`.
- Counters server-render as `7 000+`, `4`, `24 oy`.
- `/api/lead` exercised for all four paths: no-JS submit redirects to `/rahmat`, JSON submit
  returns a readable error while unconfigured, short phone is rejected, honeypot returns a
  fake success.
- OG image renders as a valid 1200x630 PNG; favicon composition checked against the artwork.
- Colour tokens resolve to the guidebook values (`#5a3089`, `#dfd01f`); contrast pairs measured.

Rendered and inspected in headless Chrome at 1280, 500 and full-page heights, plus the branch
and thank-you pages. Two things that only showed up on screen were fixed as a result: the hero
watermark was cropped by the summary card into four stray shapes (removed), and the same mark
covered a whole 390px screen (now centred, scaled per breakpoint, desktop-only on branch pages).

Two apparent faults turned out to be tooling artefacts, not site bugs, and were confirmed as
such: text clipping in a 390px capture (Chrome on Windows clamps window width, so it lays out
wider than it captures — measured 350px text in a real 390px viewport, no overflow), and an
empty final CTA in a 9200px-tall capture (the observer's bottom margin excludes the last 10% of
a viewport that size; the section renders correctly at any real height).

Still worth one human pass on an actual phone.
