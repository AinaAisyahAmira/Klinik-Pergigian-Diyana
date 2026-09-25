/* Drives the calendar/time-slot picker on our_dentist.php.
   Adapted from booking_calendar.js, but simplified: no dentist <select>
   (the dentist is fixed via window.BOOKING_DENTIST) and no modal
   hand-off — this form posts straight to our_dentist.php. */

(function () {
  const DENTIST = window.BOOKING_DENTIST;
  if (!DENTIST) return;

  // get_availability.php lives next to config.php, one level above this page.
  const API_BASE = "../get_availability.php";

  let cursor = new Date();
  cursor.setDate(1);
  let selectedDate = null;
  let selectedTime = null;
  let monthCache = {};

  const calMonthLabel = document.getElementById("calMonthLabel");
  const calendarDays  = document.getElementById("calendarDays");
  const timeSection   = document.getElementById("timeSlotSection");
  const timeList      = document.getElementById("timeSlotList");
  const msg           = document.getElementById("bookingMsg");
  const submitBtn     = document.getElementById("submitBookingBtn");
  const hiddenDate    = document.getElementById("hiddenDate");
  const hiddenTime    = document.getElementById("hiddenTime");
  const treatmentSel  = document.getElementById("treatment");
  const form          = document.getElementById("bookingForm");

  const MONTH_NAMES = ["January","February","March","April","May","June","July",
    "August","September","October","November","December"];

  function fmt(d) {
    return d.getFullYear() + "-" + String(d.getMonth()+1).padStart(2,"0") + "-" + String(d.getDate()).padStart(2,"0");
  }
  function showMsg(text) { msg.textContent = text; msg.classList.remove("d-none"); }
  function hideMsg() { msg.classList.add("d-none"); }

  function resetSelection() {
    selectedDate = null;
    selectedTime = null;
    hiddenDate.value = "";
    hiddenTime.value = "";
    submitBtn.disabled = true;
    timeSection.style.display = "none";
    hideMsg();
  }

  function updateSubmitState() {
    submitBtn.disabled = !(treatmentSel.value && selectedDate && selectedTime);
  }
  treatmentSel.addEventListener("change", updateSubmitState);

  async function loadMonth() {
    const year = cursor.getFullYear(), month = cursor.getMonth() + 1;
    calMonthLabel.textContent = MONTH_NAMES[cursor.getMonth()] + " " + year;
    const cacheKey = DENTIST + "_" + year + "_" + month;

    if (!monthCache[cacheKey]) {
      try {
        const res = await fetch(
          `${API_BASE}?action=month&dentist=${encodeURIComponent(DENTIST)}&year=${year}&month=${month}`
        );
        monthCache[cacheKey] = await res.json();
      } catch (err) {
        showMsg("Couldn't load availability right now — please try again.");
        return;
      }
    }
    renderDays(monthCache[cacheKey]);
  }

  function renderDays(statusMap) {
    calendarDays.innerHTML = "";
    const firstOfMonth = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    const startPad = firstOfMonth.getDay();
    const daysInMonth = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate();
    const today = new Date(); today.setHours(0,0,0,0);

    for (let i = 0; i < startPad; i++) {
      calendarDays.insertAdjacentHTML("beforeend", `<div class="cal-day"></div>`);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateObj = new Date(cursor.getFullYear(), cursor.getMonth(), d);
      const key = fmt(dateObj);
      let status = (statusMap && statusMap[key]) || "unavailable";
      if (dateObj < today) status = "past";

      const classes = ["cal-day", "in-month"];
      if (status === "available") classes.push("is-available");
      if (status === "full") classes.push("is-full");
      if (status === "unavailable") classes.push("is-unavailable");
      if (status === "past") classes.push("is-past");
      if (key === selectedDate) classes.push("is-selected");

      const selectedStyle = key === selectedDate
        ? ' style="background:#2e8b8b !important;color:#ffffff !important;width:34px !important;min-width:34px !important;max-width:34px !important;height:34px !important;min-height:34px !important;max-height:34px !important;aspect-ratio:1 / 1;border-radius:50%;box-shadow:0 0 0 3px rgba(46,139,139,.2);font-weight:700;box-sizing:border-box;"'
        : "";

      calendarDays.insertAdjacentHTML(
        "beforeend",
        `<div class="${classes.join(" ")}"${selectedStyle} data-date="${key}" data-status="${status}">${d}</div>`
      );
    }

    calendarDays.querySelectorAll(".cal-day.in-month").forEach(cell => {
      cell.addEventListener("click", onDayClick);
    });
  }

  async function onDayClick(e) {
    const cell = e.currentTarget;
    const status = cell.dataset.status;

    if (status === "past" || status === "unavailable" || status === "full") {
      showMsg(
        status === "unavailable" ? "The dentist isn't available this day — please choose another date." :
        status === "full"        ? "This day is fully booked — please choose another date." :
                                    "That date has already passed."
      );
      return;
    }

    hideMsg();
    selectedDate = cell.dataset.date;
    selectedTime = null;
    hiddenDate.value = selectedDate;
    hiddenTime.value = "";
    calendarDays.querySelectorAll(".cal-day.is-selected").forEach(day => day.classList.remove("is-selected"));
    cell.classList.add("is-selected");
    updateSubmitState();

    const cacheKey = DENTIST + "_" + cursor.getFullYear() + "_" + (cursor.getMonth() + 1);
    renderDays(monthCache[cacheKey]); // re-render so the clicked day highlights

    await loadDaySlots();
  }

  async function loadDaySlots() {
    try {
      const res = await fetch(
        `${API_BASE}?action=day&dentist=${encodeURIComponent(DENTIST)}&date=${selectedDate}`
      );
      const data = await res.json();

      timeSection.style.display = "block";
      timeList.innerHTML = "";

      (data.slots || []).forEach(slot => {
        const label = slot.time.slice(0, 5); // "09:00:00" -> "09:00"
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "time-slot-btn" + (slot.status === "booked" ? " taken" : "");
        btn.textContent = label;
        btn.disabled = slot.status === "booked";
        btn.addEventListener("click", () => {
          selectedTime = slot.time;
          hiddenTime.value = selectedTime;
          updateSubmitState();
          timeList.querySelectorAll(".time-slot-btn").forEach(b => b.classList.remove("chosen"));
          btn.classList.add("chosen");
          btn.style.backgroundColor = "#2e8b8b";
          btn.style.borderColor = "#2e8b8b";
          btn.style.color = "#ffffff";
        });
        timeList.appendChild(btn);
      });
    } catch (err) {
      showMsg("Couldn't load time slots — please try again.");
    }
  }

  document.getElementById("prevMonthBtn").addEventListener("click", () => {
    cursor.setMonth(cursor.getMonth() - 1);
    resetSelection();
    loadMonth();
  });
  document.getElementById("nextMonthBtn").addEventListener("click", () => {
    cursor.setMonth(cursor.getMonth() + 1);
    resetSelection();
    loadMonth();
  });

  // Belt-and-braces: don't let the form submit without a date/time,
  // even if something odd happens to the disabled state above.
  form.addEventListener("submit", (e) => {
    if (!hiddenDate.value || !hiddenTime.value) {
      e.preventDefault();
      showMsg("Please select a date and time before confirming.");
    }
  });

  resetSelection();
  loadMonth();
})();