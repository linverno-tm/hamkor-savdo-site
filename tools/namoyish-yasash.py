"""
Fold the built Next.js site into one self-contained HTML page for client review.

Fidelity is the point: the markup and CSS are the real build output, not a
re-drawing. Only three things change — fonts become data URIs, page navigation
becomes hash routing between the five prerendered documents, and the enquiry
form says it is a preview instead of posting nowhere.
"""
import base64
from io import BytesIO
import os
import re

from PIL import Image

SRC = "D:/CLAUDE folder/portfolio/hamkor-savdo-flagship"
OUT = "D:/CLAUDE folder/portfolio/hamkor-savdo-flagship/preview/hamkor-savdo-preview.html"

PAGES = [
    ("home", ".next/server/app/index.html"),
    ("filiallar", ".next/server/app/filiallar.html"),
    ("shahrixon-ozodbek", ".next/server/app/filiallar/shahrixon-ozodbek.html"),
    ("shahrixon-bog", ".next/server/app/filiallar/shahrixon-bog.html"),
    ("asaka-umid", ".next/server/app/filiallar/asaka-umid.html"),
    ("andijon-amir-temur", ".next/server/app/filiallar/andijon-amir-temur.html"),
    ("maxfiylik", ".next/server/app/maxfiylik.html"),
]


def preview_photo(path):
    """Namoyish uchun suratni kichraytirib qayta siqadi.

    Saytdagi fayllarni o''zgarishsiz joylashtirsak, hujjat 10 MB dan oshadi:
    har bir surat kartochkada ham, kattalashtirish oynasida ham takrorlanadi,
    ya''ni o''rtacha ikki yarim marta ichkariga tushadi. Namoyish nusxasi
    bir marta ko''rib chiqish uchun — 800px va biroz pastroq sifat yetarli.
    Saytning o''zidagi fayllarga bu ta''sir qilmaydi.
    """
    with Image.open(path) as im:
        im = im.convert("RGB")
        w = min(800, im.size[0])
        if w != im.size[0]:
            im = im.resize((w, round(w * im.size[1] / im.size[0])), Image.LANCZOS)
        buf = BytesIO()
        im.save(buf, "WEBP", quality=65, method=4)
        return buf.getvalue()

def read(p):
    with open(os.path.join(SRC, p), encoding="utf-8") as f:
        return f.read()


# ---------- CSS, with the real fonts inlined ----------
css_path = [f for f in os.listdir(os.path.join(SRC, ".next/static/chunks")) if f.endswith(".css")][0]
css = read(os.path.join(".next/static/chunks", css_path))

media_dir = os.path.join(SRC, ".next/static/media")
inlined = 0
for name in os.listdir(media_dir):
    if not name.endswith(".woff2"):
        continue
    with open(os.path.join(media_dir, name), "rb") as f:
        b64 = base64.b64encode(f.read()).decode()
    uri = f"data:font/woff2;base64,{b64}"
    for ref in (f"/_next/static/media/{name}", f"../media/{name}", name):
        if ref in css:
            css = css.replace(ref, uri)
            inlined += 1
            break
print("fonts inlined:", inlined)
assert "/_next/static/media/" not in css, "a font URL survived"

# next/font sets --font-outfit / --font-bebas through classes on <html>, and the
# artifact skeleton owns that element. Re-declare whatever those classes set on
# :root, or every font silently falls back.
font_vars = re.findall(r"\.[\w-]*__variable\{([^}]*)\}", css)
assert font_vars, "font variable classes not found"
css += chr(10) + ":root{" + ";".join(v.strip().rstrip(";") for v in font_vars) + "}" + chr(10)
print("font vars promoted to :root:", font_vars)


# ---------- page bodies ----------
def body_of(html):
    m = re.search(r"<body[^>]*>(.*)</body>", html, re.S)
    inner = m.group(1)
    inner = re.sub(r"<script\b.*?</script>", "", inner, flags=re.S)
    inner = re.sub(r"<template\b.*?</template>", "", inner, flags=re.S)
    inner = re.sub(r"<!--.*?-->", "", inner, flags=re.S)
    inner = re.sub(r"<next-route-announcer\b.*?</next-route-announcer>", "", inner, flags=re.S)
    return inner.strip()


ATTRS = ("id", "for", "aria-labelledby", "aria-controls", "aria-describedby")


def namespace(html, prefix):
    """Keep ids unique across the five documents sharing one page."""
    for a in ATTRS:
        html = re.sub(rf'{a}="([^"]+)"', lambda m, a=a: f'{a}="{prefix}--{m.group(1)}"', html)
    # Same-document anchors only: '/#foo' still carries its slash here.
    html = re.sub(r'href="#([^"/][^"]*)"', rf'href="#{prefix}--\1"', html)
    return html


def relink(html):
    html = re.sub(r'href="/#([^"]+)"', r'href="#\1"', html)
    html = re.sub(r'href="/filiallar/([^"]+)"', r'href="#/filiallar/\1"', html)
    html = re.sub(r'href="/"', 'href="#/"', html)
    return html


parts = []
for slug, path in PAGES:
    inner = body_of(read(path))
    inner = namespace(inner, slug)
    inner = relink(inner)
    parts.append(f'<div class="rt-page" data-page="{slug}" hidden>\n{inner}\n</div>')
    print(f"  {slug}: {len(inner)//1024} KB")

pages_html = "\n".join(parts)

# ---------- suratlar, hujjat ichiga joylanadi ----------
#
# Namoyish nusxasi — bitta fayl, orqasida server yo'q. Shuning uchun har bir
# surat hujjatning ichida sayohat qilishi kerak. Ikki qaror faylni
# haddan tashqari shishirib yubormaydi:
#
#   * `srcset` olib tashlanadi. Aks holda har bir suratning 480 va 960
#     variantlari ham ichkariga tushadi va sahifa butun galereyaning
#     ikkinchi nusxasini behuda ko'tarib yuradi — namoyishga bir marta,
#     kompyuterdan qaraladi.
#   * faqat haqiqatda ishlatilgan fayllar o'qiladi, public/ da yotgan
#     ortiqcha surat sahifani og'irlashtirmaydi.
pages_html = re.sub(r'\s+srcset="[^"]*"', '', pages_html)

photo_refs = sorted(set(re.findall(r'/(?:filiallar|bosh|yonalishlar)/[^"\'&; ]+?\.webp', pages_html)))
photo_bytes = 0
for ref in photo_refs:
    disk = os.path.join(SRC, 'public', ref.lstrip('/'))
    if not os.path.isfile(disk):
        print('  ! rasm topilmadi:', ref)
        continue
    raw = preview_photo(disk)
    photo_bytes += len(raw)
    pages_html = pages_html.replace(
        ref, 'data:image/webp;base64,' + base64.b64encode(raw).decode())

print('photos inlined:', len(photo_refs), '(' + str(photo_bytes // 1024) + ' KB on disk)')
assert not re.search(r'src="/(?:filiallar|bosh|yonalishlar)/', pages_html), 'a photo URL survived'

EXTRA_CSS = """
/* --- preview shell: only routing and the form notice live here --- */
.rt-page[hidden]{display:none}
.rt-note{margin-top:1.25rem;border-radius:14px;background:var(--purple-50);
  padding:1rem 1.25rem;color:var(--purple);font-weight:600;line-height:1.5}
.rt-badge{position:fixed;left:50%;bottom:1rem;transform:translateX(-50%);z-index:80;
  display:flex;align-items:center;gap:.5rem;border-radius:999px;background:var(--ink);
  color:#fff;padding:.5rem 1rem;font-size:.8125rem;font-family:var(--font-sans);
  box-shadow:0 10px 30px -12px rgba(0,0,0,.5)}
.rt-badge b{color:var(--yellow);font-weight:700}
.rt-badge button{margin-left:.25rem;color:#fff;opacity:.6;font-size:1rem;line-height:1}
.rt-badge button:hover{opacity:1}
@media print{.rt-badge{display:none}}
"""

JS = r"""
(function () {
  var pages = Array.prototype.slice.call(document.querySelectorAll('.rt-page'));
  function pageEl(name){ return pages.filter(function(p){return p.dataset.page===name})[0]; }

  function show(name, anchor) {
    var target = pageEl(name) || pageEl('home');
    pages.forEach(function (p) { p.hidden = p !== target; });
    // Reveal + counters are per-page, so arm the page we just switched to.
    arm(target);
    if (anchor) {
      var el = target.querySelector('#' + CSS.escape(anchor));
      if (el) { el.scrollIntoView(); return; }
    }
    window.scrollTo(0, 0);
  }

  function route() {
    var h = decodeURIComponent(location.hash || '#/');
    var m = h.match(/^#\/filiallar\/(.+)$/);
    if (m) return show(m[1]);
    if (h === '#/' || h === '#') return show('home');
    var bare = h.slice(1);
    var slug = bare.split('--')[0];
    if (pageEl(slug)) return show(slug, bare);
    show('home', bare);
  }

  /* ---- reveal + count-up: same behaviour as the real site ---- */
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function group(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }

  function countUp(el) {
    var target = Number(el.dataset.count);
    if (!isFinite(target)) return;
    var suffix = el.dataset.countSuffix || '';
    var start = performance.now();
    (function step(now) {
      var t = Math.min(1, (now - start) / 900);
      el.textContent = group(Math.round(target * (1 - Math.pow(1 - t, 3)))) + suffix;
      if (t < 1) requestAnimationFrame(step);
    })(start);
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      e.target.classList.add('is-in');
      e.target.querySelectorAll('[data-count]').forEach(countUp);
      observer.unobserve(e.target);
    });
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });

  function arm(page) {
    if (reduced) return;
    var blocks = Array.prototype.slice.call(page.querySelectorAll('[data-reveal]'));
    var fold = window.innerHeight * 0.9;
    var pending = [];
    blocks.forEach(function (el) {
      if (el.dataset.armed) return;
      el.dataset.armed = '1';
      Array.prototype.forEach.call(el.children, function (c, i) {
        c.style.setProperty('--i', String(i));
      });
      if (el.getBoundingClientRect().top < fold) {
        el.querySelectorAll('[data-count]').forEach(countUp);
      } else {
        el.classList.add('reveal');
        pending.push(el);
      }
    });
    void document.body.offsetHeight;
    pending.forEach(function (el) { el.classList.add('is-armed'); observer.observe(el); });
  }

  /* ---- the menu conveniences the real site adds on top of <details> ---- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a[href^="#"]');
    if (!a) return;
    var open = document.querySelector('details[open]');
    if (open) open.removeAttribute('open');
  });
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var open = document.querySelector('details[open]');
    if (open) open.removeAttribute('open');
  });

  /* ---- the enquiry form has no server here; say so rather than pretend ---- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('form')) return;
    e.preventDefault();
    var live = form.querySelector('[aria-live]');
    if (live) {
      live.innerHTML =
        '<p class="rt-note">Bu \u2014 namoyish nusxasi. Haqiqiy saytda ariza to\u2018g\u2018ridan-to\u2018g\u2018ri ' +
        'HAMKOR SAVDO Telegramiga yuboriladi.</p>';
    }
    form.reset();
  });

  window.addEventListener('hashchange', route);
  route();

  var badge = document.querySelector('.rt-badge');
  if (badge) badge.querySelector('button').addEventListener('click', function () { badge.remove(); });
})();
"""

BADGE = (
    '<div class="rt-badge" role="note">'
    "<span>Namoyish nusxasi \u2014 <b>HAMKOR SAVDO</b> sayti</span>"
    '<button type="button" aria-label="Yopish">\u00d7</button>'
    "</div>"
)

doc = (
    "<title>HAMKOR SAVDO</title>\n"
    f"<style>\n{css}\n{EXTRA_CSS}</style>\n"
    f"{pages_html}\n{BADGE}\n"
    f"<script>{JS}</script>\n"
)

os.makedirs(os.path.dirname(OUT), exist_ok=True)
with open(OUT, "w", encoding="utf-8") as f:
    f.write(doc)
print("\nwritten:", OUT)
print("size:", round(len(doc.encode()) / 1024, 1), "KB")
