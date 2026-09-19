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
