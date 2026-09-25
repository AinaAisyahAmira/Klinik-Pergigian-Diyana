/* Drives #bookingModal (dentist/treatment/calendar/time) and #registerModal
   (account fields + final submit to register.php). The two are separate
   Bootstrap modals — picking a slot and clicking "Continue" hands off from
   one to the other; "Back" hands it back. */

(function () {
  let cursor = new Date();
  cursor.setDate(1);
  let selectedDate = null;
  let selectedTime = null;
  let monthCache = {}; // avoid re-fetching a month you've already seen this session

  const dentistSelect = document.getElementById("dentistSelect");
  const treatmentSelect = document.getElementById("treatment");
  const calendarWrap  = document.getElementById("calendarWrap");
  const monthLabel    = document.getElementById("calMonthLabel");
  const daysGrid       = document.getElementById("calendarDays");
  const timeSection   = document.getElementById("timeSlotSection");
  const timeList       = document.getElementById("timeSlotList");
  const msg             = document.getElementById("bookingMsg");
  const continueBtn   = document.getElementById("continueToRegisterBtn");

  const bookingModalEl  = document.getElementById("bookingModal");
  const registerModalEl = document.getElementById("registerModal");
  const bookingModal  = new bootstrap.Modal(bookingModalEl);
  const registerModal = new bootstrap.Modal(registerModalEl);

  const registerForm    = document.getElementById("registerForm");
  const registerMsg     = document.getElementById("registerMsg");
  const registerSummary = document.getElementById("registerSummary");
  const bookingSuccess  = document.getElementById("bookingSuccess");
  const submitBtn       = document.getElementById("submitBookingBtn");

  const MONTH_NAMES = ["January","February","March","April","May","June","July",
    "August","September","October","November","December"];

  function fmt(d) {
    return d.getFullYear() + "-" + String(d.getMonth()+1).padStart(2,"0") + "-" + String(d.getDate()).padStart(2,"0");
  }

  function showMsg(text) {
    msg.textContent = text;
    msg.classList.remove("d-none");
  }
  function hideMsg() { msg.classList.add("d-none"); }

  function resetSelection() {
    selectedDate = null;
    selectedTime = null;
    continueBtn.disabled = true;
    timeSection.style.display = "none";
    hideMsg();
  }

  // Continue should only enable once dentist + treatment + date + time are
  // all chosen — previously it only tracked the time-slot click, so picking
  // a time before a treatment left Continue enabled with an incomplete
  // selection, and picking a treatment after a time never got checked at all.
  function updateContinueState() {
    continueBtn.disabled = !(dentistSelect.value && treatmentSelect.value && selectedDate && selectedTime);
  }
  treatmentSelect.addEventListener("change", updateContinueState);

  /* ---------------------------------------------------------------- */
  /* Calendar (unchanged logic, just no longer tied to a <form>)       */
  /* ---------------------------------------------------------------- */
  async function loadMonth() {
    const dentist = dentistSelect.value;
    if (!dentist) return;

    const year = cursor.getFullYear(), month = cursor.getMonth() + 1;
    monthLabel.textContent = MONTH_NAMES[cursor.getMonth()] + " " + year;
    const cacheKey = dentist + "_" + year + "_" + month;

    if (!monthCache[cacheKey]) {
      try {
        const res = await fetch(
          `get_availability.php?action=month&dentist=${encodeURIComponent(dentist)}&year=${year}&month=${month}`
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
    daysGrid.innerHTML = "";
    const firstOfMonth = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    const startPad = firstOfMonth.getDay();
    const daysInMonth = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate();
    const today = new Date(); today.setHours(0,0,0,0);

    for (let i = 0; i < startPad; i++) {
      daysGrid.insertAdjacentHTML("beforeend", `<div class="cal-day"></div>`);
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

      daysGrid.insertAdjacentHTML(
        "beforeend",
        `<div class="${classes.join(" ")}" data-date="${key}" data-status="${status}">${d}</div>`
      );
    }

    daysGrid.querySelectorAll(".cal-day.in-month").forEach(cell => {
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
    updateContinueState();

    const dentist = dentistSelect.value;
    const cacheKey = dentist + "_" + cursor.getFullYear() + "_" + (cursor.getMonth() + 1);
    renderDays(monthCache[cacheKey]); // re-render so the clicked day highlights as selected

    await loadDaySlots();
  }

  async function loadDaySlots() {
    const dentist = dentistSelect.value;
    try {
      const res = await fetch(
        `get_availability.php?action=day&dentist=${encodeURIComponent(dentist)}&date=${selectedDate}`
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
          updateContinueState();
          timeList.querySelectorAll(".time-slot-btn").forEach(b => b.classList.remove("chosen"));
          btn.classList.add("chosen");
        });
        timeList.appendChild(btn);
      });
    } catch (err) {
      showMsg("Couldn't load time slots — please try again.");
    }
  }

  dentistSelect.addEventListener("change", () => {
    resetSelection();
    if (dentistSelect.value) {
      calendarWrap.style.display = "block";
      cursor = new Date(); cursor.setDate(1);
      loadMonth();
    } else {
      calendarWrap.style.display = "none";
    }
  });

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

  /* ---------------------------------------------------------------- */
  /* Hand-off: bookingModal -> registerModal                           */
  /* ---------------------------------------------------------------- */
  // Only open registerModal once bookingModal has fully finished closing —
  // avoids two modal backdrops stacking on top of each other.
  let goingToRegister = false;
  continueBtn.addEventListener("click", () => {
    if (!dentistSelect.value || !treatmentSelect.value || !selectedDate || !selectedTime) {
      showMsg("Please select a dentist, treatment, date, and time before continuing.");
      return;
    }

    document.getElementById("hiddenDentist").value   = dentistSelect.value;
    document.getElementById("hiddenTreatment").value = treatmentSelect.value;
    document.getElementById("hiddenDate").value      = selectedDate;
    document.getElementById("hiddenTime").value      = selectedTime;

    registerSummary.textContent =
      `${dentistSelect.value} · ${treatmentSelect.value} · ${selectedDate} at ${selectedTime.slice(0,5)}`;

    goingToRegister = true;
    bookingModal.hide();
  });

  bookingModalEl.addEventListener("hidden.bs.modal", () => {
    if (goingToRegister) {
      goingToRegister = false;
      registerModal.show();
      return;
    }
    // Closed via the X / backdrop / Esc without continuing — reset everything.
    dentistSelect.value = "";
    treatmentSelect.value = "";
    calendarWrap.style.display = "none";
    resetSelection();
  });

  /* ---------------------------------------------------------------- */
  /* Hand-off: registerModal -> back to bookingModal                   */
  /* ---------------------------------------------------------------- */
  let goingBackToBooking = false;
  document.getElementById("backToBookingBtn").addEventListener("click", () => {
    goingBackToBooking = true;
    registerModal.hide();
  });

  registerModalEl.addEventListener("hidden.bs.modal", () => {
    if (goingBackToBooking) {
      goingBackToBooking = false;
      bookingModal.show(); // booking selections were left intact, so this resumes where they left off
      return;
    }
    // Closed via X / backdrop / Esc / "Close" after success — reset for next time.
    registerForm.reset();
    registerForm.classList.remove("d-none");
    bookingSuccess.classList.add("d-none");
    registerMsg.classList.add("d-none");

    dentistSelect.value = "";
    treatmentSelect.value = "";
    calendarWrap.style.display = "none";
    resetSelection();
  });

  /* ---------------------------------------------------------------- */
  /* Submit registerForm to register.php via fetch — stays in-modal.   */
  /* ---------------------------------------------------------------- */
  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    registerMsg.classList.add("d-none");
    submitBtn.disabled = true;
    submitBtn.textContent = "Booking…";

    try {
      const res = await fetch("register.php", {
        method: "POST",
        body: new FormData(registerForm),
      });

      let data;
      try {
        data = await res.json();
      } catch {
        throw new Error("Unexpected server response — check the browser console / network tab.");
      }

      if (!res.ok || !data.success) {
        throw new Error(data.message || "Something went wrong — please try again.");
      }

      document.getElementById("bookingSuccessDetails").textContent =
        `${data.dentist} on ${data.date} at ${data.time.slice(0, 5)}.`;
      registerForm.classList.add("d-none");
      bookingSuccess.classList.remove("d-none");

    } catch (err) {
      registerMsg.textContent = err.message;
      registerMsg.classList.remove("d-none");
      submitBtn.disabled = false;
    } finally {
      submitBtn.textContent = "Register & Book Appointment";
    }
  });
})();