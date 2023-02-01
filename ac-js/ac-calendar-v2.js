// utility functions
import {
  createEl,
  addClass,
  debounce,
  addInlineStyles,
  isMobile,
  datediff,
} from "./_utils.js";

// define current url
// this gets the current directory and adds the path to the ajax file.
// var urlRoot is set in admin where we are loading the calendar - if not defined (ie font-end cal) use current base url as assets are relative to this
const urlCalendar = typeof urlRoot != "undefined" ? urlRoot : "";
// alternative manual method - replace "_calendar_url_" with your calendar url
//const urlCalendar = typeof(urlRoot) != 'undefined' ? urlRoot : "_calendar_url_/ac-ajax/calendar.ajax.php";

// define ajax urls
const urlAjaxCal = `${urlCalendar}ac-ajax/calendar.ajax.php?`;
const urlAjaxSettings = `${urlCalendar}ac-ajax/settings.ajax.php?`;

// DOM elements
let acWrapper,
  acContainer,
  acNavLoadingEl,
  acNumMonthsEl,
  acNumMonthsElTmp,
  clickableDates,
  fieldDateStart,
  fieldDateEnd;

// params which will be defined in custom element <ac-calendar>
let acItemID, acLang, idDateStart, idDateEnd, acNumMonthsInitial, acMonthWidth;

let direction = "today"; // start calendar on current month
let acStartDate; // empty for initial load, will be overwritten once calendar has loaded
let dateStartSet = false; // date form fields defined (default false - these cab be passed in the custom element <ac-calendar>)
let errors = false; // if an error is detected whilst retrieving and defining data this will be set to "true" and prevent the calendar from being rendered

// settings
let settings,
  minNightsAllowed = 0,
  startDate;

// texts
let txtToday,
  txtBack,
  txtNext,
  txtDatesNotAvailable,
  txtMinNights,
  txtDateEndKO;

// temp to force css reload
const d = new Date();
let curTime = d.getTime();

// create common elements once then CLONE in loops (not 100% convinced that this method saves time)
const newMonthEl = createEl("div", "ac-month");
const monthTitleEl = createEl("div", "ac-month-title");
const weekDayTitlesEl = createEl("ul", "ac-weekdays");
const weekDaysNumbersEl = createEl("ul", "ac-days");
const weekDayEl = createEl("li");
const acSpinner =
  '<div class="ac-spinner"><div></div><div></div><div></div><div></div></div>';

// create template to hold calendar
const acTemplate = document.createElement("template");
acTemplate.innerHTML = `
<div id="ac-container">
  <ul id="ac-nav">
        <li data-direction="back" title="Previous month">&#x276E;</li>
        <li data-direction="today" title="Today" class="loader">Today</li>
        <li data-direction="next" title="Next month">&#x276F;</li>
    </ul>
    <div id="ac-months">
        months loaded here....
    </div>
</div>
`;

class MyCalendar extends HTMLElement {
  constructor() {
    super();

    // get attributes to define calendar settings
    acItemID = this.getAttribute("ac-id")
      ? this.getAttribute("ac-id")
      : displayError({ msg: "No item id set", code: "0.01" }); // REQUIRED - id of calendar item to show availability

    acLang = this.getAttribute("ac-lang") ? this.getAttribute("ac-lang") : "en"; // OPTIONAL (default en) - language to show calendar
    acNumMonthsInitial = this.getAttribute("ac-months-to-show")
      ? this.getAttribute("ac-months-to-show")
      : 0; // OPTIONAL (default 0 for responsive) - number of months to show
    acMonthWidth = this.getAttribute("ac-width")
      ? this.getAttribute("ac-width")
      : 300; // OPTIONAL (default 300) - if the number of months is responsive the code will attempt to use this width to calculate the number of months to show.
    idDateStart = this.getAttribute("ac-date-start")
      ? this.getAttribute("ac-date-start")
      : null; // OPTIONAL (default null) - date start field for date range picker
    idDateEnd = this.getAttribute("ac-date-end")
      ? this.getAttribute("ac-date-end")
      : null; // OPTIONAL (default null) - date end field for date range picker

    acWrapper = this.attachShadow({ mode: "open" });
    acWrapper.appendChild(acTemplate.content.cloneNode(true));

    // main calendar container -
    acContainer = acWrapper.querySelector("#ac-container");

    // element - loader (today & spinner)
    acNavLoadingEl = acWrapper.querySelector(".loader");

    // element - months will be displayed here
    acNumMonthsEl = acWrapper.querySelector("#ac-months");

    // define number of months to show according to screen width - DO WE NEED TO DO THIS EVERY TIME ????
    acNumMonthsElTmp = monthsToShow();

    setUpCalendar();
  }
}
// add custom element to the window
window.customElements.define("ac-calendar", MyCalendar);

// use promises to define and create setting, header etc.  Finally load the calendar data
async function setUpCalendar() {
  await getSettings();
  await defineSettings();
  await addStyles();
  await addNavControls();
  loadCal(direction);
}

// FETCH calendar settings - this function will call as soon as the dom is ready
async function getSettings() {
  const paramsString = `lang=${acLang}`;
  const searchParams = new URLSearchParams(paramsString);
  const response = await fetch(urlAjaxSettings + searchParams);
  //console.log(response);
  if (!response.ok) {
    return displayError({
      msg: "Unable to find calendar settings file",
      code: "0.03",
    });
  } else {
    settings = await response.json();
    if (settings.error) {
      return displayError(settings.error);
    }
  }
}

// define texts & settings
async function defineSettings() {
  txtToday = settings.texts["today"];
  txtBack = settings.texts["back"];
  txtNext = settings.texts["next"];
  txtMinNights = settings.texts["min_nights"];
  txtDateEndKO = settings.texts["end_before_start"];
  txtDatesNotAvailable = settings.texts["dates_not_available"];
  minNightsAllowed = settings.min_nights;

  // set nav texts and title attribute to settings lang texts
  acContainer
    .querySelector(`[data-direction="back"]`)
    .setAttribute("title", txtBack);
  acContainer
    .querySelector(`[data-direction="next"]`)
    .setAttribute("title", txtNext);
  const navToday = acContainer.querySelector(`[data-direction="today"]`);
  navToday.setAttribute("title", txtToday);
  navToday.innerText = txtToday;

  // weekday titles
  for (let j = 1; j < 8; j++) {
    const li = weekDayEl.cloneNode(true);
    li.textContent = settings.texts["day_" + j + ""];
    weekDayTitlesEl.appendChild(li);
  }
}

// create header, fetch main colors from db and include style sheet
async function addStyles() {
  //const head = document.getElementsByTagName("HEAD")[0];
  let acStyles = settings.styles;

  // define styles
  let cssStyles = "";
  acStyles.forEach((style) => {
    let newStyle = `${style.name}:${style.val};
        `;
    cssStyles += newStyle;
  });

  const styleSheet = createEl("style");
  styleSheet.innerHTML = `
  @import "${urlCalendar}ac-css/ac-style.css?${curTime}";
  #ac-container * {
    ${cssStyles}
  }
  `;
  acWrapper.prepend(styleSheet);
}

// add calendar nav
async function addNavControls() {
  const acControls = acContainer.querySelectorAll("[data-direction]");
  acControls.forEach((btn) => {
    btn.addEventListener("click", () => {
      loadCal(btn.getAttribute("data-direction"));
    });
  });
}

// FETCH calendar JSON data
function loadCal(direction) {
  // show spinner
  showSpinner(true);

  // define data to send via fetch POST
  let params = {
    id_item: "" + acItemID + "",
    lang: "" + acLang + "",
    numMonths: "" + acNumMonthsElTmp + "",
    startDate: "" + acStartDate + "",
    direction: "" + direction + "",
  };
  const searchParams = new URLSearchParams(params);
  (async function () {
    let response = await fetch(urlAjaxCal + searchParams);
    // If the call failed, throw an error
    if (!response.ok) {
      return displayError({
        msg: "Unable to read month data",
        code: "0.04",
      });
    } else {
      // Otherwise, get the post JSON
      const data = await response.json();
      // define new start date for next cal load
      acStartDate = data["start-date"];

      drawCal(data);
    }
  })();
}

// add JSON months returned to calendar container
function drawCal(data) {
  if (data.error) {
    return displayError(data.error);
  }
  // clear calendar wrapper contents
  acNumMonthsEl.innerHTML = "";

  // define data parts returned
  const resMonths = data["months"];
  const resWeekDays = data["weekdays"];

  // loop through each month returmed to create calendar month
  for (let i = 0; i < resMonths.length; i++) {
    // month title
    let monthTitleClone = monthTitleEl.cloneNode(true);
    monthTitleClone.textContent = resMonths[i].month_title;

    const weekDayTitlesClone = weekDayTitlesEl.cloneNode(true);
    let weekDaysNumbersClone = weekDaysNumbersEl.cloneNode(true);
    let days = resMonths[i].days;
    for (let j = 0; j < days.length; j++) {
      // clone li element
      let dateNum = weekDayEl.cloneNode(true);

      // define vars from result
      let dateNumClass = days[j].c;
      let dateNumFormat = days[j].df;
      let dateNumTitle = days[j].ds;
      let dateNumState = days[j].s;

      if (dateNumState) {
        // add date state if defined
        dateNumTitle += " - " + dateNumState;
      }
      dateNum.setAttribute("title", dateNumTitle);
      dateNum.setAttribute("id", "date_" + dateNumFormat);
      dateNum.setAttribute("data-date", dateNumFormat);
      // add date classes
      for (let cl = 0; cl < dateNumClass.length; cl++) {
        dateNum.classList.add("" + dateNumClass[cl] + "");
      }
      //dateNum.classList.add(""+dateNumClass+"");
      dateNum.textContent = days[j].n;

      // add date to ul
      weekDaysNumbersClone.appendChild(dateNum);
    }

    // put month together
    const newMonthCl = newMonthEl.cloneNode(true);
    newMonthCl.append(
      monthTitleClone,
      weekDayTitlesClone,
      weekDaysNumbersClone
    );

    // add calendar month
    acNumMonthsEl.appendChild(newMonthCl);

    // remove spinner
    showSpinner(false);

    // add click event to dates
    activateDates();
  }
}

// make available dates clickable for bookings form date range selection
function activateDates() {
  if (idDateStart) {
    // define date field elements (these field ids are external to the calendar script)
    fieldDateStart = document.querySelector(`#${idDateStart}`);
    fieldDateEnd = document.querySelector(`#${idDateEnd}`);

    // get all items with update-state class
    clickableDates = acContainer.querySelectorAll(".available");
    // add click event to state elements
    clickableDates.forEach((d) => {
      d.addEventListener("click", setDate);
      d.addEventListener("mouseenter", highlightDates);
    });
  }
}

// dates selected not available
function dateNotAvail(el) {
  alert("" + txtDatesNotAvailable + "");
  clearDates();
}

// remove date range select styles
function clearRangeStyles(removeStartDate = true) {
  if (removeStartDate) {
    // remove all (reset)
    clickableDates.forEach((d) => {
      d.classList.remove(
        "date-select-start",
        "date-select-between",
        "date-select-end",
        "date-select-end-am"
      );
    });
  } else {
    // leave start date
    clickableDates.forEach((d) => {
      d.classList.remove(
        "date-select-between",
        "date-select-end",
        "date-select-end-am"
      );
    });
  }
}
// reset date range
function clearDates() {
  dateStartSet = false;
  fieldDateStart.value = "";
  fieldDateEnd.value = "";
  clearRangeStyles(true);
}

// selected start or end date - update form and calendar dates between (if end date)
function setDate() {
  const dateSelected = this.getAttribute("data-date");

  if (!dateStartSet) {
    // clear any previous date selection
    clearRangeStyles(true);

    // save start date to check available dates when end date is selected
    startDate = dateSelected;
    console.log(startDate);
    // add date to start date field
    fieldDateStart.value = startDate;

    // empty end date form field
    fieldDateEnd.value = "";

    // set dateClickStart so that the next click will be end date
    dateStartSet = true;

    // add start date class to this date

    this.classList.contains("booked-am")
      ? addClass(this, "date-select-start-pm")
      : addClass(this, "date-select-start");
  } else {
    // setting end date - need to check and highlight dates between
    let dateMove = new Date(startDate);
    let dateEnd = new Date(dateSelected);
    let strDate = startDate;
    const numNights = datediff(dateMove, dateEnd); // calculte number of nights (only used if min nights > 0 )

    if (dateSelected < startDate) {
      alert(txtDateEndKO);
      clearDates();
    } else if (numNights < minNightsAllowed) {
      alert(txtMinNights);
    } else {
      // set end date field value
      fieldDateEnd.value = dateSelected;

      // mark dates between
      while (strDate < dateSelected) {
        strDate = dateMove.toISOString().slice(0, 10);
        if (strDate > startDate && strDate < dateSelected) {
          // get date element from month
          let betweenDate = acContainer.querySelector(`#date_${strDate}`);
          if (betweenDate.classList.contains("booked")) {
            // date already booked - alert and reset
            return dateNotAvail(betweenDate);
          } else {
            addClass(betweenDate, "date-select-between");
          }
        }
        // move date foward by one day
        dateMove.setDate(dateMove.getDate() + 1);
      }

      // add selected class
      this.classList.contains("booked-pm")
        ? addClass(this, "date-select-end-am")
        : addClass(this, "date-select-end");

      // reset click to make next date start date again
      dateStartSet = false;
    }
  }
}
// TEST - highlight dates
function highlightDates() {
  if (dateStartSet) {
    // Only if start date has been defined (clicked)

    const dateSelected = this.getAttribute("data-date");
    let dateMove = new Date(startDate);
    let dateEnd = new Date(dateSelected);
    let strDate = startDate;

    // clear date range already marked to be able to mouseover back and forth over dates
    clearRangeStyles(false);

    if (dateSelected > startDate) {
      // mark dates between
      while (strDate < dateSelected) {
        strDate = dateMove.toISOString().slice(0, 10);
        if (strDate > startDate && strDate < dateSelected) {
          let betweenDate = acContainer.querySelector(`#date_${strDate}`);
          addClass(betweenDate, "date-select-between");
        }
        // move date foward by one day
        dateMove.setDate(dateMove.getDate() + 1);
      }
      this.classList.contains("booked-pm")
        ? addClass(this, "date-select-end-am")
        : addClass(this, "date-select-end");
    }
  }
}

// define how many months to show according to space OR user defined
function monthsToShow() {
  // define number of months to show if not set
  if (acNumMonthsInitial == 0) {
    // if number of months not defined
    // define number of months to show according to parent width - not 100% sure this is a good idea yet
    const acWidth = acContainer.clientWidth;
    return Math.floor(acWidth / acMonthWidth);
  } else {
    return acNumMonthsInitial; // user defined
  }
}

// show spinner or "today" text
function showSpinner(show) {
  if (show) acNavLoadingEl.innerHTML = acSpinner;
  else acNavLoadingEl.innerHTML = txtToday;
}

// insert error message into dom
function displayError({ msg, code }) {
  let msgLocation;
  if (code == "0.01") {
    // this error code means that the calendar wrapper has not been defined to we just append the message to the body
    msgLocation = document.querySelector("body");
  } else {
    msgLocation = document.querySelector(`#${acWrapperID}`);
  }
  const msgEl = createEl("div");
  addInlineStyles(msgEl, {
    border: "1px solid red",
    padding: ".5em 1em",
    backgroundColor: "#fce2e2",
    borderRadius: ".25rem",
    marginBlock: ".25rem",
  });
  msgEl.innerHTML = `error ${code}:<br> ${msg}`;
  msgLocation.prepend(msgEl);
  errors = true; // prevent calendar from being rendered
}

// detect window resize and call reloadOnResize function
window.addEventListener("resize", debounce(reloadOnResize, 150));

// recreate calendar on window resize - will remember selected first month even if it is not current month
function reloadOnResize() {
  // NOT on mobiles as they resize automatically on scroll to remove the header bar
  if (!isMobile) {
    // recalculate number of months we can show according to window size
    acNumMonthsElTmp = monthsToShow();
    // initiate calendar
    loadCal("current");
  }
}
