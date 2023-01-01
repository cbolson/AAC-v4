// add defined class to element
export function createEl(elType) {
  return document.createElement(`${elType}`);
}
// add defined class to element
export function addClass(el, className) {
  return el.classList.add(`${className}`);
}

// show spinner or "today" text
export function showSpinner(show) {
  if (show) acNavLoading.innerHTML = acSpinner;
  else acNavLoading.innerHTML = txtToday;
}

// Debounce - prevent calling the resize window function on every slightest move.
export function debounce(func, time) {
  var time = time || 100; // 100 by default if no param
  var timer;
  return function (event) {
    if (timer) clearTimeout(timer);
    timer = setTimeout(func, time, event);
  };
}
export const addStyles = (node, styles) =>
  Object.keys(styles).forEach((key) => (node.style[key] = styles[key]));
