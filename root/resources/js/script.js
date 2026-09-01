// Mohammad Zishan Ansari
let habits = [];
let completions = [];

let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth();

document.addEventListener("DOMContentLoaded", () => {
    loadData();
    const habitInput =
        document.getElementById("habitInput");

    habitInput.addEventListener("keydown", event => {
        if (event.key === "Enter") {
            event.preventDefault();
            createHabit();
        }
    });
});

/* LOAD DATA */
async function loadData() {
    const response =
        await fetch("../config/api.php?action=getData");

    const data =
        await response.json();

    habits =
        data.habits || [];

    completions =
        data.completions || [];
    renderDashboard();
}


/* RENDER EVERYTHING */
function renderDashboard() {
    renderDate();
    renderCalendar();
    updateStats();
    renderDailyChart();
    renderWeeklyChart();
    renderTrendChart();
}


/* DATE */
function renderDate() {
    const date =
        new Date(currentYear, currentMonth);
    document.getElementById("monthTitle")
        .textContent =
        date.toLocaleString(
            "default",
            {
                month: "long",
                year: "numeric"
            }
        );

    document.getElementById("currentDate")
        .textContent =
        new Date().toLocaleDateString(
            "default",
            {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric"
            }
        );
}

/* DAYS IN MONTH */
function getDaysInMonth() {
    return new Date(
        currentYear,
        currentMonth + 1,
        0
    ).getDate();
}

/* DATE STRING */
function getDateString(day) {

    const month =
        String(currentMonth + 1)
            .padStart(2, "0");

    const dayNumber =
        String(day)
            .padStart(2, "0");

    return `${currentYear}-${month}-${dayNumber}`;
}

/* TODAY'S REAL DATE STRING (independent of the viewed month/year) */
function getTodayString() {
    const today = new Date();

    const year =
        today.getFullYear();

    const month =
        String(today.getMonth() + 1)
            .padStart(2, "0");

    const day =
        String(today.getDate())
            .padStart(2, "0");

    return `${year}-${month}-${day}`;
}

/* ADD HABIT */
async function createHabit() {
    const input =
        document.getElementById("habitInput");

    const name =
        input.value.trim();

    if (!name) return;
    const response =
        await fetch(
            "../config/api.php?action=addHabit",
            {
                method: "POST",
                headers: {
                    "Content-Type":
                        "application/json"
                },
                body:
                    JSON.stringify({
                        name: name
                    })
            }
        );

    const data =
        await response.json();

    if (data.success) {
        habits =
            data.habits;

        input.value = "";
        renderDashboard();
    }
}

/* CALENDAR */
function renderCalendar() {
    const header =
        document.getElementById(
            "calendarHeader"
        );

    const body =
        document.getElementById(
            "trackerBody"
        );

    const days =
        getDaysInMonth();

    let weekHeader =
        `
        <tr class="week-row">

            <th
                rowspan="2"
                class="habit-header"
            >
                HABIT
            </th>
        `;

    let dayHeader =
        `
        <tr class="day-row">
        `;

    /* CREATE WEEK DIVISIONS */
    let day = 1;
    let week = 1;
    while (day <= days) {
        const remaining =
            days - day + 1;

        const weekDays =
            Math.min(7, remaining);

        weekHeader += `
            <th colspan="${weekDays}">
                WEEK ${week}
            </th>
        `;
        week++;
        day += 7;
    }
    weekHeader += "</tr>";

    /* DAY NUMBERS */
    for (let day = 1; day <= days; day++) {
        const date =
            new Date(
                currentYear,
                currentMonth,
                day
            );

        const weekday =
            date.toLocaleDateString(
                "default",
                {
                    weekday: "short"
                }
            );

        const weekStart =
            (day - 1) % 7 === 0;

        const isToday =
            getDateString(day) === getTodayString();

        dayHeader += `
            <th
                class="
                    ${weekStart ? "week-start" : ""}
                    ${isToday ? "today-column" : ""}
                "
            >

                ${weekday}

                <span class="day-number">
                    ${day}
                </span>

            </th>
        `;
    }

    dayHeader += "</tr>";
    header.innerHTML =
        weekHeader +
        dayHeader;

    /* HABIT ROWS */
    if (habits.length === 0) {
        body.innerHTML = `
            <tr>
                <td
                    colspan="${days + 1}"
                    style="
                        padding:35px;
                        color:#888;
                        text-align:center;
                    "
                >
                    Enter your first habit above
                    to begin tracking.
                </td>
            </tr>
        `;
        return;
    }

    body.innerHTML =
        habits.map(habit => {
            let row =
                `
                <tr>

                    <td class="habit-name">

                        ${escapeHtml(habit.name)}

                        <button
                            class="edit-habit"
                            onclick="editHabit(${habit.id})"
                        >
                            EDIT
                        </button>

                    </td>
                `;

            const todayString =
                getTodayString();
            for (
                let day = 1;
                day <= days;
                day++
            ) {

                const date =
                    getDateString(day);

                const completed =
                    completions.some(item =>
                        Number(item.habit_id) ===
                        Number(habit.id)
                        &&
                        item.completed_date === date
                    );

                const weekStart =
                    (day - 1) % 7 === 0;

                const isToday =
                    date === todayString;

                row += `
                    <td
                        class="
                            day-cell
                            ${weekStart ? "week-start" : ""}
                            ${isToday ? "today-column" : ""}
                        "
                    >
                        <div
                            class="
                                check-cell
                                ${completed ? "completed" : ""}
                                ${isToday ? "" : "disabled"}
                            "

                            title="
                                ${isToday
                        ? "Click to mark completion"
                        : "Only today can be checked"}
                            "

                            ${isToday
                        ? `onclick="toggleHabit(${habit.id}, '${date}')"`
                        : ""}
                        ></div>

                    </td>
                `;
            }

            row += "</tr>";
            return row;
        }).join("");
}

/* TOGGLE COMPLETION */
async function toggleHabit(
    habitId,
    date
) {

    if (date !== getTodayString()) {
        return;
    }

    const response =
        await fetch(
            "../config/api.php?action=toggle",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify({
                        habit_id: habitId,
                        date: date
                    })
            }
        );

    const data =
        await response.json();

    completions =
        data.completions || [];

    renderDashboard();
}

/* EDIT OR DELETE HABIT */
function editHabit(id) {
    const habit =
        habits.find(
            item =>
                Number(item.id) === Number(id)
        );
    if (!habit) return;

    const action =
        prompt(
            `Habit: ${habit.name}

Type:
EDIT   - Rename habit
DELETE - Remove habit`
        );

    if (!action) return;

    /* DELETE */
    if (
        action
            .trim()
            .toUpperCase() === "DELETE"
    ) {
        deleteHabit(id);
        return;
    }

    /* EDIT */
    if (
        action
            .trim()
            .toUpperCase() === "EDIT"
    ) {

        const newName =
            prompt(
                "Enter the new habit name:",
                habit.name
            );

        if (
            newName &&
            newName.trim()
        ) {

            updateHabit(
                id,
                newName.trim()
            );
        }
    }
}

/* UPDATE HABIT */
async function updateHabit(
    id,
    name
) {

    const response =
        await fetch(
            "../config/api.php?action=updateHabit",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify({
                        id: id,
                        name: name
                    })
            }
        );

    const data =
        await response.json();

    if (data.success) {

        habits =
            data.habits;
        renderDashboard();
    }
}

/* DELETE HABIT */
async function deleteHabit(id) {
    const habit =
        habits.find(
            item =>
                Number(item.id) === Number(id)
        );

    if (!habit) return;
    const confirmDelete =
        confirm(
            `Remove "${habit.name}"?`
        );

    if (!confirmDelete) return;
    const response =
        await fetch(
            `../config/api.php?action=deleteHabit&id=${id}`,
            {
                method: "DELETE"
            }
        );

    const data =
        await response.json();

    habits =
        data.habits || [];

    completions =
        data.completions || [];
    renderDashboard();
}

/* STATS */
function updateStats() {

    const days =
        getDaysInMonth();

    const monthPrefix =
        `${currentYear}-${String(
            currentMonth + 1
        ).padStart(2, "0")}`;

    const monthCompletions =
        completions.filter(item =>
            item.completed_date
                .startsWith(monthPrefix)
        );

    const completed =
        monthCompletions.length;

    const possible =
        habits.length * days;

    const percentage =
        possible > 0
            ? Math.round(
                (completed / possible) * 100
            )
            : 0;

    document.getElementById(
        "totalHabits"
    ).textContent =
        habits.length;

    document.getElementById(
        "completedHabits"
    ).textContent =
        completed;

    document.getElementById(
        "completionRate"
    ).textContent =
        percentage + "%";

    document.getElementById(
        "progressPercent"
    ).textContent =
        percentage + "%";

    /* UPDATE CIRCLE */
    const circle =
        document.getElementById(
            "progressCircle"
        );

    const circumference = 302;

    const offset =
        circumference -
        (
            percentage / 100
        ) * circumference;

    circle.style.strokeDashoffset =
        offset;

    document.getElementById(
        "bestStreak"
    ).textContent =
        calculateBestStreak() +
        " days";
}

/* DAILY PROGRESS CHART */
function renderDailyChart() {
    const chart =
        document.getElementById(
            "dailyChart"
        );

    const labelsRow =
        document.getElementById(
            "dailyChartLabels"
        );

    const summary =
        document.getElementById(
            "dailySummary"
        );

    const days =
        getDaysInMonth();

    const todayString =
        getTodayString();

    let html = "";
    let labelsHtml = "";

    let percentageSum = 0;
    let bestDay = null;
    let bestPercentage = -1;

    for (
        let day = 1;
        day <= days;
        day++
    ) {

        const date =
            getDateString(day);

        const completed =
            completions.filter(
                item =>
                    item.completed_date === date
            ).length;

        const percentage =
            habits.length > 0
                ? (
                    completed /
                    habits.length
                ) * 100
                : 0;

        percentageSum += percentage;

        if (percentage > bestPercentage) {
            bestPercentage = percentage;
            bestDay = day;
        }

        const isToday =
            date === todayString;

        html += `
            <div
                class="
                    bar
                    ${isToday ? "bar-today" : ""}
                "

                style="
                    height:
                    ${Math.max(
            percentage,
            2
        )}%
                "

                title="
                    Day ${day}
                    - ${Math.round(
            percentage
        )}% completed
                "
            ></div>
        `;

        /* only show every 5th label (plus day 1 and the last day) to avoid crowding */
        const showLabel =
            day === 1 ||
            day === days ||
            day % 5 === 0;

        labelsHtml += `
            <span class="${isToday ? "bar-label-today" : ""}">
                ${showLabel ? day : ""}
            </span>
        `;
    }
    chart.innerHTML = html;
    if (labelsRow) {
        labelsRow.innerHTML = labelsHtml;
    }
    if (summary) {
        const average =
            days > 0
                ? Math.round(percentageSum / days)
                : 0;

        summary.textContent =
            habits.length > 0
                ? `Average ${average}% completed per day  •  Best day: ${bestDay} (${Math.round(bestPercentage)}%)`
                : "Add a habit to see daily activity";
    }
}

/* WEEKLY PROGRESS - REAL CALCULATION */
function renderWeeklyChart() {
    const chart =
        document.getElementById(
            "weeklyChart"
        );

    const days =
        getDaysInMonth();

    const totalWeeks =
        Math.ceil(days / 7);

    let html = "";

    for (
        let week = 0;
        week < totalWeeks;
        week++
    ) {
        const startDay =
            week * 7 + 1;

        const endDay =
            Math.min(
                startDay + 6,
                days
            );

        const daysInWeek =
            endDay -
            startDay +
            1;
        let weeklyCompleted = 0;

        for (
            let day = startDay;
            day <= endDay;
            day++
        ) {
            const date =
                getDateString(day);

            weeklyCompleted +=
                completions.filter(
                    item =>
                        item.completed_date === date
                ).length;
        }

        const possible =
            habits.length *
            daysInWeek;

        const percentage =
            possible > 0
                ? Math.round(
                    (
                        weeklyCompleted /
                        possible
                    ) * 100
                )
                : 0;

        html += `
            <div class="week-column">

                <div
                    class="week-column-percent"
                >
                    ${percentage}%
                </div>

                <div
                    class="week-column-bar"

                    style="
                        height:
                        ${Math.max(
            percentage,
            2
        )}%
                    "

                    title="
                        Week ${week + 1}:
                        ${weeklyCompleted}
                        completions
                    "
                ></div>

                <div
                    class="week-column-label"
                >
                    W${week + 1}
                </div>

            </div>
        `;
    }
    chart.innerHTML = html;
}

/* TREND CHART */
function renderTrendChart() {
    const days =
        getDaysInMonth();
    const points = [];

    for (
        let day = 1;
        day <= days;
        day++
    ) {
        const date =
            getDateString(day);

        const completed =
            completions.filter(
                item =>
                    item.completed_date === date
            ).length;

        const percentage =
            habits.length > 0
                ? completed / habits.length
                : 0;

        const x =
            (day - 1) *
            (
                900 /
                Math.max(days - 1, 1)
            );

        const y =
            170 -
            percentage * 150;

        points.push(
            `${x},${y}`
        );
    }

    document.getElementById(
        "trendLine"
    ).setAttribute(
        "points",
        points.join(" ")
    );
}

/* STREAK */
function calculateBestStreak() {
    const dates =
        [
            ...new Set(
                completions.map(
                    item =>
                        item.completed_date
                )
            )
        ].sort();

    if (!dates.length) {
        return 0;
    }

    let best = 1;
    let current = 1;

    for (
        let i = 1;
        i < dates.length;
        i++
    ) {
        const previous =
            new Date(
                dates[i - 1] +
                "T00:00:00"
            );

        const now =
            new Date(
                dates[i] +
                "T00:00:00"
            );

        const difference =
            Math.round(
                (
                    now -
                    previous
                ) /
                86400000
            );

        if (difference === 1) {

            current++;
        } else {
            current = 1;
        }

        best =
            Math.max(
                best,
                current
            );
    }
    return best;
}

/* MONTH NAVIGATION */
function changeMonth(direction) {
    currentMonth += direction;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }

    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderDashboard();
}

function goToToday() {
    const today =
        new Date();

    currentYear =
        today.getFullYear();

    currentMonth =
        today.getMonth();
    renderDashboard();
}

/* HTML ESCAPING */
function escapeHtml(text) {
    const div =
        document.createElement("div");

    div.textContent =
        text;

    return div.innerHTML;
}
