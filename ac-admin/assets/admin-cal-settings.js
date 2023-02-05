// define css custom property data (name, selector, property, default and related form field)
const styleData = [
  {
    name: "--ac-month-bg", // css custom property name
    "css-selector": ".ac-month", // css selector
    "css-property": "background", // css prop
    default: "#FFFFFF", // default value
    related: "", // related field (for colors to set bg input field to correct color) - NEEDED?????
  },
  {
    name: "--ac-month-title-bg",
    "css-selector": ".ac-month-title",
    "css-property": "background",
    default: "#046889",
    related: "",
  },
  {
    name: "--ac-month-title-clr",
    "css-selector": ".ac-month-title",
    "css-property": "color",
    default: "#FFFFFF",
    related: "--ac-month-title-bg",
  },
  {
    name: "--ac-weekday-bg",
    "css-selector": ".ac-weekdays li",
    "css-property": "background",
    default: "#8fd9f2",
    related: "",
  },
  {
    name: "--ac-weekday-clr",
    "css-selector": ".ac-weekdays li",
    "css-property": "color",
    default: "#000000",
    related: "--ac-weekday-bg",
  },
  {
    name: "--ac-day-bg",
    "css-selector": ".ac-days li",
    "css-property": "background",
    default: "#f0f0f0",
    related: "",
  },
  {
    name: "--ac-day-clr",
    "css-selector": ".ac-days li",
    "css-property": "color",
    default: "#000000",
    related: "--ac-day-bg",
  },
  {
    name: "--ac-day-clr-hover",
    "css-selector": ".ac-days li",
    "css-property": "color",
    "is-hover": true,
    default: "#ffa500",
    related: "",
  },
  {
    name: "--ac-weekend-bg",
    "css-selector": ".weekend",
    "css-property": "background",
    default: "#f0f0f0",
    related: "",
  },
  {
    name: "--ac-weekend-clr",
    "css-selector": ".weekend",
    "css-property": "color",
    default: "#000000",
    related: "--ac-weekend-bg",
  },
  {
    name: "--ac-nav-clr",
    "css-selector": "#ac-nav li",
    "css-property": "color",
    default: "#046889",
    related: "",
  },
  {
    name: "--ac-nav-clr-hover",
    "css-selector": "#ac-nav li",
    "css-property": "color",
    "is-hover": true,
    default: "#000000",
    related: "",
  },
  {
    name: "--ac-booked-bg",
    "css-selector": ".booked",
    "css-property": "background",
    default: "#ff9090",
    related: "",
  },
  {
    name: "--ac-booked-clr",
    "css-selector": ".booked",
    "css-property": "color",
    default: "#000000",
    related: "--ac-booked-bg",
  },
  {
    name: "--ac-select-range",
    "css-selector": ".date-select-start, .date-select-end",
    "css-property": "background",
    default: "#ffcc00",
    related: "",
  },
  {
    name: "--ac-select-between",
    "css-selector": ".date-select-between",
    "css-property": "background",
    default: "#fdeeb3",
    related: "",
  },
  {
    name: "--ac-border-radius",
    "css-selector": ".ac-month",
    "css-property": "borderRadius",
    units: "px",
    default: "10",
    related: "",
  },
];
const myCalShadow = document.querySelector("ac-calendar");
const myCal = myCalShadow.shadowRoot.querySelector("#ac-container");
const styleInputs = document.querySelectorAll("[style-input]");
// hide nav
//myCalShadow.shadowRoot.querySelector("#ac-nav").style.display = "none";

function resetStyles() {
  // loop through default styles to reset
  styleData.forEach((elData) => {
    fieldEl = elData["name"];
    defaultVal = elData["default"];

    // update input fields
    const field = document.querySelector(`#${fieldEl}`);
    field.value = `${defaultVal}`;
    field.style.background = `${defaultVal}`;

    // update preview
    changeStyle(fieldEl, defaultVal);
  });
}
function changeStyle(type, val) {
  console.log(type);
  // get data for this field
  const elData = styleData.find((el) => el.name === `${type}`);

  let units = "";
  if (elData["is-hover"] === undefined) {
    // some fields (eg hover) won't have selectors for live testing
    const elements = myCal.querySelectorAll(`${elData["css-selector"]}`);
    const prop = elData["css-property"];
    if (elData["units"]) {
      units = elData["units"];
    }
    if (elData["related"]) {
      parentEl = document.querySelector(`#${elData["related"]}`);
      parentEl.style.color = val;
    }
    // set style for all cal elements with this classname (or id)
    for (let el of elements) {
      el.style[prop] = val + units;
    }
  }
}
rangeChange = function (el) {
  changeStyle(el.id, el.value);
};
// add color selector to each style input
styleInputs.forEach((field) => {
  var hueb = new Huebee(field, {
    hue0: 210,
  });
  hueb.on("change", function (color, hue, sat, lum) {
    changeStyle(field.id, color);
  });
});
