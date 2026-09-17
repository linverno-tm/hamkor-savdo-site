/* Admin panel — kichik qulayliklar. Hammasi JS'siz ham ishlaydi. */
document.documentElement.classList.add("js");

document.addEventListener("submit", function (ev) {
  var msg = ev.target.getAttribute("data-confirm");
  if (msg && !window.confirm(msg)) ev.preventDefault();
});

// Holat tanlanishi bilan saqlanadi (JS'siz "OK" tugmasi ko'rinadi).
document.addEventListener("change", function (ev) {
  if (ev.target.hasAttribute && ev.target.hasAttribute("data-autosubmit")) {
    ev.target.form.submit();
  }
});

document.addEventListener("click", function (ev) {
  var btn = ev.target.closest && ev.target.closest("[data-add-row]");
  if (btn) {
    ev.preventDefault();
    var tpl = document.getElementById(btn.getAttribute("data-add-row"));
    var target = document.getElementById(btn.getAttribute("data-target"));
    if (!tpl || !target) return;
    var idx = target.children.length + Date.now();
    target.insertAdjacentHTML("beforeend", tpl.innerHTML.replace(/__i__/g, String(idx)));
    return;
  }
  var eye = ev.target.closest && ev.target.closest("[data-toggle-password]");
  if (eye) {
    ev.preventDefault();
    var input = document.getElementById(eye.getAttribute("data-toggle-password"));
    if (!input) return;
    var show = input.type === "password";
    input.type = show ? "text" : "password";
    eye.textContent = show ? "Yashirish" : "Ko'rsatish";
  }
});
