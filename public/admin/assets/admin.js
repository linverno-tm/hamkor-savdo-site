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

// Holat tanlanishi bilan saqlanadi (JS'siz "OK" tugmasi ko'rinadi).
document.addEventListener("change", function (ev) {
  if (ev.target.hasAttribute && ev.target.hasAttribute("data-autosubmit")) {
    ev.target.form.submit();
  }
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
  // Popover ochilganda birinchi maydonga fokus.
  document.querySelectorAll("details.inline-details").forEach(function (d) {
    d.addEventListener("toggle", function () {
      if (!d.open) return;
      var f = d.querySelector("input:not([type=hidden])");
      if (f) f.focus();
    });
  });
});
