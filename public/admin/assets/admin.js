/* Admin panel — kichik qulayliklar. Hammasi JS'siz ham ishlaydi. */
document.addEventListener("submit", function (ev) {
  var msg = ev.target.getAttribute("data-confirm");
  if (msg && !window.confirm(msg)) ev.preventDefault();
});

document.addEventListener("click", function (ev) {
  var btn = ev.target.closest && ev.target.closest("[data-add-row]");
  if (!btn) return;
  ev.preventDefault();
  var tpl = document.getElementById(btn.getAttribute("data-add-row"));
  var target = document.getElementById(btn.getAttribute("data-target"));
  if (!tpl || !target) return;
  var idx = target.children.length + Date.now();
  var html = tpl.innerHTML.replace(/__i__/g, String(idx));
  target.insertAdjacentHTML("beforeend", html);
});
