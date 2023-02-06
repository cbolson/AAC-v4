// arr icons for item states
const icons = {
  0: '<svg class="icon icon-cross"><use xlink:href="assets/symbol-defs.svg#icon-cross"></use></svg>',
  1: '<svg class="icon icon-checkmark"><use xlink:href="assets/symbol-defs.svg#icon-checkmark"></use></svg>',
  loading:
    '<svg class="icon icon-spinner6"><use xlink:href="assets/symbol-defs.svg#icon-spinner6"></use></svg>',
};

// get current url - this *should* let us define the complete url to the ajax file without having to define it with PHP or hard code.
let currUrl = document.URL,
  newUrl;
if (currUrl.charAt(currUrl.length - 1) === "/") {
  newUrl = currUrl.slice(0, currUrl.lastIndexOf("/"));
  newUrl = newUrl.slice(0, newUrl.lastIndexOf("/")) + "/";
} else {
  newUrl = currUrl.slice(0, currUrl.lastIndexOf("/")) + "/";
}
// define ajax url - replace current url admin dir with ajax dir and add ajax file
urlAjax = newUrl.replace("ac-admin", "ac-ajax") + "admin.ajax.php";

// update item state via AJAX
var updateState = function () {
  let data = {
    action: "mod-state",
    type: this.getAttribute("data-type"),
    id: this.getAttribute("data-id"),
    state: this.getAttribute("data-state"),
  };
  // replace with spinner
  this.innerHTML = icons["loading"];

  // ajax fetch
  fetch(urlAjax, {
    method: "POST",
    mode: "same-origin",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(data),
  })
    .then((response) => response.text())
    .then((html) => (this.innerHTML = icons[html]));
};

// once doc has loaded
document.addEventListener("DOMContentLoaded", function () {
  // get all items with update-state class
  var statesElements = document.querySelectorAll(".update-state");

  // add click event to state elements
  statesElements.forEach((el) => {
    el.addEventListener("click", updateState, false);
  });

  // responsive nav
  const hamburger = document.querySelector("#hamburger");

  hamburger.addEventListener("click", () => {
    const isOpened = hamburger.getAttribute("aria-expanded") === "true";
    if (isOpened ? toggleMenu("false") : toggleMenu("true"));
  });

  function toggleMenu(state) {
    hamburger.setAttribute("aria-expanded", `${state}`);
  }

  const toggleBtns = document.querySelectorAll("[toggle-id]");
  toggleBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const idToggle = btn.getAttribute("toggle-id");
      const elToggle = document.querySelector(`#${idToggle}`);
      elToggle.classList.toggle("hidden");

      fields = elToggle.querySelectorAll("input");
      if (elToggle.classList.contains("hidden")) {
        // need to disable form fields to prevent them from being submitted
        fields.forEach((field) => field.setAttribute("disabled", true));
      } else {
        // remove "disabled"
        fields.forEach((field) => field.removeAttribute("disabled"));
      }
    });
  });
});
