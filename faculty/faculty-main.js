// --- Faculty Dashboard Navigation Sidebar --- //
const navBtns = document.querySelectorAll(".nav-btn")

let currentTab = (document.querySelector(".nav-btn.active-nav-btn") != null)
    ? document.querySelector(".nav-btn.active-nav-btn").textContent.trim()
    : null;

const facultyDashboardContainer = document.querySelector(".faculty-dashboard-container")
const facultySubjectsContainer = document.querySelector(".faculty-subjects-container")
const facultyEvalresultsContainer = document.querySelector(".faculty-evalresults-container")
const facultyCriteriaContainer = document.querySelector(".faculty-criteria-container")
const facultyCommentsContainer = document.querySelector(".faculty-comments-container")
const facultyParticipationContainer = document.querySelector(".faculty-participation-container")
const facultyReportsContainer = document.querySelector(".faculty-reports-container")


navBtns.forEach((e) => {
    addClickListener(e, () => {
        e.classList.add("active-nav-btn")
        navBtns.forEach((f) => {
            if (f != e) f.classList.remove("active-nav-btn")
        })
        if (currentTab != null) currentTab = document.querySelector(".nav-btn.active-nav-btn").textContent.trim();

        switch (currentTab) {
            case "Dashboard":
                displayFacultyDashboard()
                break;
            case "My Subjects":
                displayFacultySubjects()
                break;
            case "Evaluation Results":
                displayFacultyEvalResults()
                break;
            case "Criteria Scores":
                displayFacultyCriteria()
                break;
            case "Comments":
                displayFacultyComments()
                break;
            case "Participation":
                displayFacultyParticipation()
                break;
            case "Reports":
                displayFacultyReports()
                break;
            default:
                displayFacultyDashboard();
                break;
        }
    })
})

function hideTabs() {
    const tabs = document.querySelectorAll(".tabs [class^='faculty-']")
    tabs.forEach((e) => {
        if (e) e.classList.add("hidden")
    })
}

function displayFacultyDashboard() {
    hideTabs()
    toggleElement(facultyDashboardContainer)
}

function displayFacultySubjects() {
    hideTabs()
    toggleElement(facultySubjectsContainer)
}

function displayFacultyEvalResults() {
    hideTabs()
    toggleElement(facultyEvalresultsContainer)
}

function displayFacultyCriteria() {
    hideTabs()
    toggleElement(facultyCriteriaContainer)
}

function displayFacultyComments() {
    hideTabs()
    toggleElement(facultyCommentsContainer)
}

function displayFacultyParticipation() {
    hideTabs()
    toggleElement(facultyParticipationContainer)
}

function displayFacultyReports() {
    hideTabs()
    toggleElement(facultyReportsContainer)
}

function changeCurrentTab(navBtnClass) {
    navBtns.forEach((f) => {
        f.classList.remove("active-nav-btn")
    })
    document.querySelector(navBtnClass).classList.add("active-nav-btn")
    currentTab = document.querySelector(".nav-btn.active-nav-btn").textContent.trim();

    /*
        .dashboard-nav-btn
        .subjects-nav-btn
        .evalresults-nav-btn
        .criteria-nav-btn
        .comments-nav-btn
        .participation-nav-btn
        .reports-nav-btn
    */
}


// --- Back Buttons --- //

const backToDashboardBtns = document.querySelectorAll(".back-to-dashboard-btn")
const backToResultsBtns = document.querySelectorAll(".back-to-results-btn")
const viewAllBtn = document.querySelector(".view-all-btn")

backToDashboardBtns.forEach((btn) => {
    addClickListener(btn, () => {
        displayFacultyDashboard()
        changeCurrentTab(".dashboard-nav-btn")
    })
})

backToResultsBtns.forEach((btn) => {
    addClickListener(btn, () => {
        displayFacultyEvalResults()
        changeCurrentTab(".evalresults-nav-btn")
    })
})

addClickListener(viewAllBtn, () => {
    displayFacultySubjects()
    changeCurrentTab(".subjects-nav-btn")
})


// --- Logout Modal --- //
const logoutBtn = document.querySelector(".logout-btn")
const closeLogoutBtn = document.querySelector(".close-logout-btn")
const continueBtn = document.querySelector(".continue-btn")
const coverBackground = document.querySelector(".cover-background")
const warningBtn2 = document.querySelector(".warning-btn-2")

addClickListener(logoutBtn, () => {
    toggleElement(coverBackground)
})

addClickListener(continueBtn, () => {
    toggleElement(coverBackground)
})

addClickListener(closeLogoutBtn, () => {
    toggleElement(coverBackground)
})

addClickListener(warningBtn2, () => {
    window.location.href = "login.html"
})


// --- Utility Functions --- //

function toggleElement(element) {
    if (element != null) element.classList.toggle("hidden")
}

function addClickListener(element, func) {
    if (element != null) element.addEventListener("click", func)
}
