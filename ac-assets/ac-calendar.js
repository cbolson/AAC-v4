/*
Script		:	Ajax availability calendar www.ajaxavailabilitycalendar.com
Author		: Chris Bolson www.cbolson.com

File		  : ac-functions.inc.js
Date mod  : 2023-01-18
Use			  : All javascript calendar functions
*/

// import utils
import { createEl, addClass, debounce, addStyles, isMobile } from "./_utils.js";

// define current url
// this gets the current directory and adds the path to the ajax file.
// var urlRoot is set in admin where we are loading the calendar - if not defined (ie font-end cal) use current base url as assets are relative to this
const urlCurrent = typeof urlRoot != "undefined" ? urlRoot : "";
// alternative manual method - replace "_calendar_url_" with your calendar url
//const urlCurrent = typeof(urlRoot) != 'undefined' ? urlRoot : "_calendar_url_/ac-ajax/calendar.ajax.php";

// define ajax urls
const urlCal = `${urlCurrent}ac-ajax/calendar.ajax.php?`;
const urlSettings = `${urlCurrent}ac-ajax/settings.ajax.php?`;

// get calendar JS script element ID to retrieve parameters
const acCal = document.querySelector("#ac-cal");

// define script variables as defined in script tag or defaults
const acWrapperID = acCal.getAttribute("ac-container")
  ? acCal.getAttribute("ac-container")
  : "KO"; // REQUIRED - element where calendar is to be placed
const acItemID = acCal.getAttribute("ac-item")
  ? acCal.getAttribute("ac-item")
  : "KO"; // REQUIRED - id of calendar item to show availability
const acLang = acCal.getAttribute("ac-lang")
  ? acCal.getAttribute("ac-lang")
  : "en"; // OPTIONAL (default en) - language to show calendar
const acNumMonthsInital = acCal.getAttribute("ac-months")
  ? acCal.getAttribute("ac-months")
  : 0; // OPTIONAL (default 0 for responsive) - number of months to show
const acMonthWidth = acCal.getAttribute("ac-width")
  ? acCal.getAttribute("ac-width")
  : 300; // OPTIONAL (default 300) - if the number of months is responsive the code will attempt to use this width to calculate the number of months to show.
const acDateStart = acCal.getAttribute("ac-dateStart")
  ? acCal.getAttribute("ac-dateStart")
  : ""; // OPTIONAL (default null) - date start field for date range picker
const acDateEnd = acCal.getAttribute("ac-dateEnd")
  ? acCal.getAttribute("ac-dateEnd")
  : ""; // OPTIONAL (default null) - date end field for date range picker

//
let direction = "today"; // start calendar on current month

let acStartDate = ""; // empty for initial load, will be overwritten once calendar has loaded
let fieldDateStart = "";
let fieldDateEnd = "";
let dateStartSet = false;
let errors = false; // if an error is detected whilst retrieving and defining data this will be set to "true" and prevent the calendar from being rendered
// admin defined settings (over written below)
let settings,
  minNightsAllowed = 0,
  startDate;
let acWrapper, acNavLoadingEl, acNumMonthsEl, acNumMonthsElTmp, clickableDates;
let txtToday,
  txtBack,
  txtNext,
  txtDatesNotAvailable,
  txtMinNights,
  txtDateEendKO;

// create common elements once then CLONE in loops (not 100% convinced that this method saves time)
const newMonthEl = createEl("div");
const monthTitleEl = createEl("h2");
const weekDayTitlesEl = createEl("ul");
const weekDaysNumbersEl = createEl("ul");
const weekDayEl = createEl("li");
const acSpinner =
  '<div class="ac-spinner"><div></div><div></div><div></div><div></div></div>';

// add classes to common elements
addClass(newMonthEl, "ac-month");
addClass(weekDayTitlesEl, "ac-day-title");
addClass(weekDaysNumbersEl, "ac-days");

// insert error message into dom
function displayError({ msg, code }) {
  let msgLocation;
  if (code == "0.01") {
    // this error code means that the calendar wrapper has not been defined to we just append the message to the body
    msgLocation = document.querySelector("body");
  } else {
    msgLocation = document.querySelector(`#${acWrapperID}`);
  }
  //console.log(msgLocation);
  const msgEl = createEl("div");
  addStyles(msgEl, {
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

// show spinner or "today" text
function showSpinner(show) {
  if (show) acNavLoadingEl.innerHTML = acSpinner;
  else acNavLoadingEl.innerHTML = txtToday;
}

// FETCH calendar settings - this function will call as soon as the dom is ready
(async function () {
  const paramsString = `lang=${acLang}`;
  const searchParams = new URLSearchParams(paramsString);
  const response = await fetch(urlSettings + searchParams);
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

    // write styles to document head
    if (!errors) setUpCalendar();
  }
})();

// use promises to define and create setting, header etc.  Finally load the calendar data
async function setUpCalendar() {
  await defineSettings();
  await renderHeader();
  await buildCalendarWrapper();
  await addNavControls();
  loadCal(direction);
}

async function defineSettings() {
  // define texts & settings
  txtToday = settings.texts["today"];
  txtBack = settings.texts["back"];
  txtNext = settings.texts["next"];
  txtMinNights = settings.texts["min_nights"];
  txtDateEendKO = settings.texts["end_before_start"];
  txtDatesNotAvailable = settings.texts["dates_not_available"];
  minNightsAllowed = settings.min_nights;
}

// create header, fetch main colors from db and include style sheet
async function renderHeader() {
  const head = document.getElementsByTagName("HEAD")[0];
  let styles = settings.styles;

  // define styles
  let cssStyles = "";
  styles.forEach((style) => {
    let newStyle = `${style.name}:${style.val};
        `;
    cssStyles += newStyle;
  });

  const styleSheet = createEl("style");
  styleSheet.innerHTML = "#ac-container * {" + cssStyles + "}";
  const link = createEl("link");
  link.id = "ac-stylesheet";
  link.rel = "stylesheet";
  link.type = "text/css";
  link.href = "" + urlCurrent + "ac-assets/ac-style.css?" + Date.now() + "&v=2";
  head.append(styleSheet, link);

  if (isMobile) {
    // check if viewport is defined
    if (!document.querySelector('meta[name="viewport"]')) {
      // page does NOT have a viewport defined - add it to ensure that the calendar displays correctly
      var metaViewPort = createEl("meta");
      metaViewPort.setAttribute("name", "viewport");
      metaViewPort.content =
        "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0";
      head.appendChild(metaViewPort);
    }
  }
}

// create wrapper to hold calendar months
async function buildCalendarWrapper() {
  // calendar wrapper on parent page
  acWrapper = document.querySelector(`#${acWrapperID}`);

  // element -  wrapper
  const acContainer = createEl("div");
  acContainer.setAttribute("id", "ac-container");

  // element - nav
  const addNavEl = createEl("ul");
  addNavEl.setAttribute("id", "ac-nav");

  // element - nav[back]
  const acNavBackEl = createEl("li");
  acNavBackEl.setAttribute("data-direction", "back");
  acNavBackEl.setAttribute("title", "" + txtBack + "");
  acNavBackEl.innerHTML = "&#x276E;"; // <

  // element -  nav[next]
  const acNavNextEl = createEl("li");
  acNavNextEl.setAttribute("data-direction", "next");
  acNavNextEl.setAttribute("title", "" + txtNext + "");
  acNavNextEl.innerHTML = "&#x276F;"; // ">""

  // element - loader (today & spinner)
  acNavLoadingEl = createEl("li");
  acNavLoadingEl.classList.add("loader");
  acNavLoadingEl.setAttribute("data-direction", "today");

  // el - month wrapper
  acNumMonthsEl = createEl("div");
  acNumMonthsEl.setAttribute("id", "ac-months");
  // weekday titles
  for (let j = 1; j < 8; j++) {
    const li = weekDayEl.cloneNode(true);
    li.textContent = settings.texts["day_" + j + ""];
    weekDayTitlesEl.appendChild(li);
  }

  // remove any existing contents from calendar wrapper
  acWrapper.innerHTML = "";

  // put elements together
  addNavEl.append(acNavBackEl, acNavLoadingEl, acNavNextEl);
  acContainer.append(addNavEl, acNumMonthsEl);
  acWrapper.append(acContainer);

  // define number of months to show according to screen width - DO WE NEED TO DO THIS EVERY TIME ????
  acNumMonthsElTmp = monthsToShow();
}

// add calendar nav
async function addNavControls() {
  var acControls = document.querySelectorAll("[data-direction]");
  acControls.forEach((btn) => {
    btn.onclick = function () {
      loadCal(btn.getAttribute("data-direction"));
    };
  });
}

// define how many months to show according to space OR user defined
function monthsToShow() {
  // define number of months to show if not set
  if (acNumMonthsInital == 0) {
    // if number of months not defined
    // define number of months to show acording to parent width - not 100% sure this is a good idea yet
    const acWidth = acWrapper.clientWidth;
    return Math.floor(acWidth / acMonthWidth);
  } else {
    return acNumMonthsInital; // user defined
  }
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
  //console.log(acStartDate);
  const searchParams = new URLSearchParams(params);
  (async function () {
    let response = await fetch(urlCal + searchParams);
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

var datediff = function (first, second) {
  // Take the difference between the dates and divide by milliseconds per day.
  // Round to nearest whole number to deal with DST.
  return Math.round((second - first) / (1000 * 60 * 60 * 24));
};

// functions for interactive calendar when user has defined start and end date fields

// make available dates clickable for bookings form date range selection
function activateDates() {
  if (acDateStart) {
    // define date field elements (these field ids are external to the calendar script)
    fieldDateStart = document.querySelector(`#${acDateStart}`);
    fieldDateEnd = document.querySelector(`#${acDateEnd}`);

    // get all items with update-state class
    clickableDates = document.querySelectorAll(".available");
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
      alert(txtDateEendKO);
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
          let betweenDate = document.querySelector(`#date_${strDate}`);
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
          let betweenDate = document.querySelector(`#date_${strDate}`);
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

// once document has loaded
document.addEventListener("DOMContentLoaded", function () {
  // DOM container for calendar is required
  if (acWrapperID == "KO") {
    return displayError({
      msg: "You must define the ID of the document element where the calendar is to be placed",
      code: "0.01",
    });
  }
  // item ID is required
  if (acItemID == "KO") {
    return displayError({
      msg: "You must define the ID of the calendar to be shown",
      code: "0.02",
    });
  }

  // detect window resize and call reloadOnResize function
  window.addEventListener("resize", debounce(reloadOnResize, 150));
});
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
