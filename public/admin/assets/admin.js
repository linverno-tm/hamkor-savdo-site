/* Admin panel — kichik qulayliklar. Hammasi JS'siz ham ishlaydi. */
document.documentElement.classList.add("js");

document.addEventListener("submit", function (ev) {
  var msg = ev.target.getAttribute("data-confirm");
  if (msg && !window.confirm(msg)) {
    ev.preventDefault();
    return;
  }
  // Ikki marta bosilib, bir xil so'rov ikki marta ketmasin.
  var btn = ev.submitter || ev.target.querySelector("button[type=submit]");
  if (btn && !btn.hasAttribute("data-no-lock")) {
    setTimeout(function () { btn.disabled = true; }, 0);
  }
});

// "Orqaga" bilan qaytilganda (bfcache) tugmalar qulflangan holda qolmasin.
window.addEventListener("pageshow", function (ev) {
  if (ev.persisted) {
    document.querySelectorAll("button[disabled]").forEach(function (b) { b.disabled = false; });
  }
});

function hsRenumberPhotos(list) {
  list.querySelectorAll("[data-photo]").forEach(function (p, i) {
    var o = p.querySelector("[data-order]");
    if (o) o.value = String(i + 1);
    var n = p.querySelector("[data-photo-no]");
    if (n) n.textContent = String(i + 1);
  });
}

// Holat tanlanishi bilan saqlanadi (JS'siz "OK" tugmasi ko'rinadi).
document.addEventListener("change", function (ev) {
  var el = ev.target;
  if (el.hasAttribute && el.hasAttribute("data-autosubmit")) {
    el.form.submit();
    return;
  }
  // Rasm: olib tashlanadigani xiralashadi, asosiysi belgilanadi.
  if (el.hasAttribute && el.hasAttribute("data-remove")) {
    el.closest("[data-photo]").classList.toggle("is-removed", el.checked);
    return;
  }
  if (el.name === "muqova") {
    document.querySelectorAll("[data-photo]").forEach(function (p) {
      var r = p.querySelector('input[name="muqova"]');
      p.classList.toggle("is-cover", !!(r && r.checked));
    });
    return;
  }
  // Tanlangan rasmlarni yuklashdan oldin ko'rsatish (data: URL — CSP blob: ga ruxsat bermaydi).
  var pv = el.getAttribute && el.getAttribute("data-preview");
  if (pv && el.files) {
    var box = document.getElementById(pv);
    if (!box) return;
    box.innerHTML = "";
    var files = Array.prototype.slice.call(el.files, 0, 10);
    if (el.files.length > 10) {
      box.insertAdjacentHTML("beforeend", '<p class="flash flash-err">10 tadan ko\'p tanlandi — faqat 10 tasi yuklanadi, qolganini keyin qo\'shing.</p>');
    }
    files.forEach(function (f) {
      var fig = document.createElement("figure");
      var img = document.createElement("img");
      var cap = document.createElement("figcaption");
      cap.textContent = (f.size / 1048576).toFixed(1) + " MB";
      fig.appendChild(img);
      fig.appendChild(cap);
      box.appendChild(fig);
      var rd = new FileReader();
      rd.onload = function () { img.src = rd.result; };
      rd.readAsDataURL(f);
    });
    var zone = document.querySelector('label[for="' + el.id + '"] b');
    if (zone) zone.textContent = files.length ? files.length + " ta rasm tanlandi — boshqasini tanlash uchun bosing" : "Rasm tanlash uchun bosing";
  }
});

// Ro'yxatdagi tez telefon: "Saqlash" faqat raqam o'zgarganda chiqadi.
document.addEventListener("input", function (ev) {
  var el = ev.target;
  if (!el.hasAttribute || !el.hasAttribute("data-dirty-watch")) return;
  var btn = el.form && el.form.querySelector("[data-dirty-show]");
  if (btn) btn.classList.toggle("is-hidden", el.value === el.defaultValue);
});

/* ---------- sahifaning bir qismini yangilash (statistika davrlari) ----------
   `data-swap="ID"` havola: butun sahifa qayta yuklanmaydi, faqat shu ID li
   blok almashadi. Tugma darhol tanlangan ko'rinadi, eski raqamlar xiralashib
   turadi. Sichqoncha tugmaga kelganda sahifa oldindan so'rab qo'yiladi —
   bosilganda ko'pincha tayyor bo'ladi. JS'siz — oddiy havola. */
var hsSwapCache = {};
function hsFetchPage(url) {
  var c = hsSwapCache[url];
  if (c && Date.now() - c.t < 60000) return c.p;
  var p = fetch(url, { credentials: "same-origin" }).then(function (r) {
    if (!r.ok || r.redirected) throw new Error("http");
    return r.text();
  });
  hsSwapCache[url] = { t: Date.now(), p: p };
  p.catch(function () { delete hsSwapCache[url]; });
  return p;
}
function hsSwap(url, id, link, push) {
  var root = document.getElementById(id);
  if (!root) { location.href = url; return; }
  root.classList.add("is-loading");
  if (link) {
    var seg = link.closest(".seg");
    if (seg) seg.querySelectorAll(".pending").forEach(function (x) { x.classList.remove("pending"); });
    link.classList.add("pending");
  }
  hsFetchPage(url).then(function (html) {
    var doc = new DOMParser().parseFromString(html, "text/html");
    var fresh = doc.getElementById(id);
    if (!fresh) throw new Error("no-root");
    root.innerHTML = fresh.innerHTML;
    root.classList.remove("is-loading");
    if (doc.title) document.title = doc.title;
    if (push) history.pushState({ swap: id }, "", url);
    delete hsSwapCache[url];
  }).catch(function () {
    location.href = url;
  });
}
document.addEventListener("click", function (ev) {
  var a = ev.target.closest && ev.target.closest("a[data-swap]");
  if (!a || ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.button !== 0) return;
  ev.preventDefault();
  hsSwap(a.href, a.getAttribute("data-swap"), a, true);
});
["mouseover", "touchstart", "focusin"].forEach(function (type) {
  document.addEventListener(type, function (ev) {
    var a = ev.target.closest && ev.target.closest("a[data-swap]");
    if (a) hsFetchPage(a.href);
  }, { passive: true });
});
window.addEventListener("popstate", function (ev) {
  if (ev.state && ev.state.swap) hsSwap(location.href, ev.state.swap, null, false);
});
if (document.getElementById("stat-body") && history.replaceState) {
  history.replaceState({ swap: "stat-body" }, "", location.href);
}

/* ---------- mavzu (yorug' / qorong'i) ---------- */
function hsIsDark() {
  var t = document.documentElement.getAttribute("data-theme");
  if (t === "dark") return true;
  if (t === "light") return false;
  return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
}
function hsSyncThemeButtons() {
  var dark = hsIsDark();
  document.querySelectorAll("[data-theme-toggle]").forEach(function (b) {
    b.setAttribute("aria-pressed", dark ? "true" : "false");
    b.setAttribute("title", dark ? "Yorug' rejim" : "Qorong'i rejim");
    b.setAttribute("aria-label", dark ? "Yorug' rejimga o'tish" : "Qorong'i rejimga o'tish");
  });
  var meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute("content", dark ? "#16121e" : "#ffffff");
}
function hsSetTheme(next) {
  var root = document.documentElement;
  root.classList.add("theme-anim");
  root.setAttribute("data-theme", next);
  try { localStorage.setItem("hs-theme", next); } catch (e) {}
  hsSyncThemeButtons();
  setTimeout(function () { root.classList.remove("theme-anim"); }, 350);
}
if (window.matchMedia) {
  var mq = window.matchMedia("(prefers-color-scheme: dark)");
  var onChange = function () { hsSyncThemeButtons(); };
  if (mq.addEventListener) mq.addEventListener("change", onChange);
  else if (mq.addListener) mq.addListener(onChange);
}

document.addEventListener("click", function (ev) {
  var t = ev.target.closest ? ev.target : null;
  if (!t) return;

  if (t.closest("[data-theme-toggle]")) {
    ev.preventDefault();
    hsSetTheme(hsIsDark() ? "light" : "dark");
    return;
  }

  var btn = t.closest("[data-add-row]");
  if (btn) {
    ev.preventDefault();
    var tpl = document.getElementById(btn.getAttribute("data-add-row"));
    var target = document.getElementById(btn.getAttribute("data-target"));
    if (!tpl || !target) return;
    var idx = target.children.length + Date.now();
    target.insertAdjacentHTML("beforeend", tpl.innerHTML.replace(/__i__/g, String(idx)));
    var first = target.lastElementChild && target.lastElementChild.querySelector("input[type=text], textarea");
    if (first) first.focus();
    return;
  }

  var eye = t.closest("[data-toggle-password]");
  if (eye) {
    ev.preventDefault();
    var input = document.getElementById(eye.getAttribute("data-toggle-password"));
    if (!input) return;
    var show = input.type === "password";
    input.type = show ? "text" : "password";
    eye.textContent = show ? "Yashirish" : "Ko'rsatish";
    return;
  }

  // Ish vaqti: tez tanlash tugmalari.
  var chip = t.closest("[data-hours]");
  if (chip) {
    var form = chip.closest("form");
    var inp = form && form.querySelector("[data-hours-input]");
    if (inp) {
      inp.value = chip.getAttribute("data-hours");
      form.querySelectorAll("[data-hours]").forEach(function (c) { c.classList.toggle("on", c === chip); });
      inp.focus();
    }
    return;
  }

  // Rasmni oldinga/orqaga surish: DOM'da joyini almashtiradi, tartib raqamlari qayta yoziladi.
  var mv = t.closest("[data-move]");
  if (mv) {
    var item = mv.closest("[data-photo]");
    var list = item && item.parentNode;
    if (!list) return;
    var dir = Number(mv.getAttribute("data-move"));
    var sib = dir < 0 ? item.previousElementSibling : item.nextElementSibling;
    if (!sib) return;
    if (dir < 0) list.insertBefore(item, sib); else list.insertBefore(sib, item);
    hsRenumberPhotos(list);
    item.classList.remove("moved");
    void item.offsetWidth;
    item.classList.add("moved");
    mv.focus();
    return;
  }

  // Ochiq menyu va popover'lar tashqariga bosilganda yopiladi.
  document.querySelectorAll("details.mobile-menu[open], details.inline-details[open]").forEach(function (d) {
    if (!d.contains(t)) d.removeAttribute("open");
  });
});

document.addEventListener("keydown", function (ev) {
  if (ev.key !== "Escape") return;
  document.querySelectorAll("details.mobile-menu[open], details.inline-details[open]").forEach(function (d) {
    d.removeAttribute("open");
    var s = d.querySelector("summary");
    if (s) s.focus();
  });
});

document.addEventListener("DOMContentLoaded", function () {
  hsSyncThemeButtons();
  // Filtrlar kompyuterda doim ochiq, telefonda yig'iq (faol filtr bo'lsa server o'zi ochadi).
  if (window.matchMedia && window.matchMedia("(min-width: 760px)").matches) {
    document.querySelectorAll("details.filter-box").forEach(function (d) { d.setAttribute("open", ""); });
  }
  document.querySelectorAll("[data-dirty-show]").forEach(function (b) { b.classList.add("is-hidden"); });
  // Tanlangan filial tugmasi ko'rinib tursin (telefonda ro'yxat yonga suriladi).
  document.querySelectorAll(".seg > span").forEach(function (s) {
    var seg = s.parentNode;
    if (seg.scrollWidth > seg.clientWidth) seg.scrollLeft = s.getBoundingClientRect().left - seg.getBoundingClientRect().left - 8;
  });
  document.querySelectorAll("[data-photo]").forEach(function (p) {
    var r = p.querySelector('input[name="muqova"]');
    if (r && r.checked) p.classList.add("is-cover");
  });
  // Popover ochilganda birinchi maydonga fokus.
  document.querySelectorAll("details.inline-details").forEach(function (d) {
    d.addEventListener("toggle", function () {
      if (!d.open) return;
      var f = d.querySelector("input:not([type=hidden])");
      if (f) f.focus();
    });
  });
});

// Ijara: dollar kursi sahifa ochiq tursa har 5 daqiqada yangilanadi (server 10 daqiqa keshlaydi).
document.addEventListener("DOMContentLoaded", function () {
  var boxes = document.querySelectorAll("[data-ij-rate]");
  if (!boxes.length || !window.fetch) return;
  setInterval(function () {
    if (document.hidden) return;
    fetch("/ijara/kurs.php", { credentials: "same-origin", headers: { Accept: "application/json" } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (j) {
        if (!j || typeof j.html !== "string") return;
        boxes.forEach(function (b) {
          var body = b.querySelector("[data-ij-rate-body]");
          var at = b.querySelector("[data-ij-rate-at]");
          if (body) body.innerHTML = j.html;
          if (at) at.textContent = j.source || "";
        });
      })
      .catch(function () {});
  }, 5 * 60 * 1000);
});

// Ijara kassasi: davr (Bu oy, O'tgan oy, sanalar) va sahifa raqami almashganda butun
// sahifa qayta yuklanmaydi — faqat data-ij-live bloklar almashadi, joy (scroll) saqlanadi.
// JavaScript ishlamasa, havolalar oddiy havola bo'lib ishlayveradi.
document.addEventListener("DOMContentLoaded", function () {
  if (!document.querySelector("[data-ij-live]") || !window.fetch || !window.DOMParser || !history.pushState) return;
  var busy = null;

  function load(url, push) {
    if (busy) busy.abort && busy.abort();
    var ctl = window.AbortController ? new AbortController() : null;
    busy = ctl;
    document.querySelectorAll("[data-ij-live]").forEach(function (b) { b.classList.add("is-loading"); });
    fetch(url, { credentials: "same-origin", signal: ctl ? ctl.signal : undefined })
      .then(function (r) {
        // Sessiya tugagan bo'lsa (kirish sahifasiga yo'naltirilgan) — oddiy o'tish.
        if (!r.ok || r.redirected) { location.href = url; return null; }
        return r.text();
      })
      .then(function (html) {
        if (html === null || html === undefined) return;
        var doc = new DOMParser().parseFromString(html, "text/html");
        document.querySelectorAll("[data-ij-live]").forEach(function (b) {
          var fresh = doc.querySelector('[data-ij-live="' + b.getAttribute("data-ij-live") + '"]');
          if (fresh) b.replaceWith(document.importNode(fresh, true));
        });
        if (push) history.pushState({ ijLive: 1 }, "", url);
      })
      .catch(function (e) { if (!e || e.name !== "AbortError") location.href = url; })
      .then(function () {
        document.querySelectorAll("[data-ij-live].is-loading").forEach(function (b) { b.classList.remove("is-loading"); });
      });
  }

  document.addEventListener("click", function (e) {
    var a = e.target.closest && e.target.closest("[data-ij-live] a[href]");
    if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0 || a.target) return;
    var u = new URL(a.href, location.href);
    if (u.origin !== location.origin || u.pathname !== location.pathname) return; // ijarachi sahifasi va h.k. — oddiy o'tish
    e.preventDefault();
    load(u.pathname + u.search, true);
  });

  document.addEventListener("submit", function (e) {
    var f = e.target;
    if (!f.closest || !f.closest("[data-ij-live]") || (f.method || "get").toLowerCase() !== "get") return;
    e.preventDefault();
    var q = new URLSearchParams(new FormData(f)).toString();
    load(f.getAttribute("action") + (q ? "?" + q : ""), true);
  });

  window.addEventListener("popstate", function () { load(location.pathname + location.search, false); });
});

// Eslatmalar qo'ng'irog'i: tashqariga bosilsa yoki Esc — yopiladi.
document.addEventListener("click", function (e) {
  document.querySelectorAll("details[data-ij-bell][open]").forEach(function (d) {
    if (!d.contains(e.target)) d.removeAttribute("open");
  });
});
document.addEventListener("keydown", function (e) {
  if (e.key !== "Escape") return;
  document.querySelectorAll("details[data-ij-bell][open]").forEach(function (d) { d.removeAttribute("open"); });
});

// Eslatmalar har kirishda o'zi ochiladi — hal qilinmaguncha (qarz to'lanmaguncha, shartnoma
// uzaytirilmaguncha). Bir kirish (brauzer seansi) davomida bir marta; eslatmalar soni oshsa — yana.
// Kirish sahifasida belgi tozalanadi: keyingi kirishda yana ochiladi.
document.addEventListener("DOMContentLoaded", function () {
  var store = null;
  try { store = window.sessionStorage; } catch (e) { store = null; }
  if (!store) return;
  if (/\/ijara\/login\.php$/.test(location.pathname)) { try { store.removeItem("ijBellSeen"); } catch (e) {} return; }
  var bell = document.querySelector("details[data-ij-bell]");
  if (!bell) return;
  var n = parseInt(bell.getAttribute("data-count") || "0", 10);
  var seen = parseInt(store.getItem("ijBellSeen") || "0", 10);
  if (n > 0 && n > seen) bell.setAttribute("open", "");
  try { store.setItem("ijBellSeen", String(n)); } catch (e) {}
});
