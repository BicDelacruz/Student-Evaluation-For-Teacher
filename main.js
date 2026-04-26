// --- Dashboard Navigation Sidebar --- //
const navBtns = document.querySelectorAll(".nav-btn")
const categoryBtns = document.querySelectorAll(".category-login-btn")

let currentTab = (document.querySelector(".nav-btn.active-nav-btn") != null) ? document.querySelector(".nav-btn.active-nav-btn").textContent.trim() : null;

const dashboardContainer = document.querySelector(".dashboard-container")
const guidelinesContainer = document.querySelector(".guidelines-container")
const evalContainer = document.querySelector(".eval-container")
const historyContainer = document.querySelector(".history-container")
const settingsContainer = document.querySelector(".settings-container")


navBtns.forEach((e) => {
    addClickListener(e, () => {
        e.classList.add("active-nav-btn")
        navBtns.forEach((f) => {
            if (f != e) f.classList.remove("active-nav-btn")
        })
        if (currentTab != null) currentTab = document.querySelector(".nav-btn.active-nav-btn").textContent.trim();

        switch (currentTab) {
            case "Dashboard":
                displayDashboard()
                break;
            case "Guidelines":
                displayGuidelines()
                break;
            case "Evaluation":
                displayEval()
                break;
            case "Submission History":
                displayHistory()
                break;
            case "Settings":
                displaySettings()
                break;
            default:
                displayDashboard();
                break;
        }
    })
})

function hideTabs() {
    const tabs = document.querySelectorAll(".tabs [class$='-container']")
    tabs.forEach((e) => {
        if (e) e.classList.add("hidden")
    })
}

function displayDashboard() {
    hideTabs()
    toggleElement(dashboardContainer)
}

function displayGuidelines() {
    hideTabs()
    toggleElement(guidelinesContainer)
}

function displayEval() {
    hideTabs()
    toggleElement(evalContainer)
}

function displayHistory() {
    hideTabs()
    toggleElement(historyContainer)
}

function displaySettings() {
    hideTabs()
    toggleElement(settingsContainer)
}

// --- Login and Two Factor Auth --- //

const loginBtn = document.querySelector(".login-btn")
const clearBtn = document.querySelector(".clear-btn")
const verifyBtn = document.querySelector(".verify-btn")
const passkeyBtn = document.querySelector(".passkey-btn")
const backBtn = document.querySelector(".back-btn")

const idNumLabel = document.querySelector(".idnum-label")
const IdNumInput = document.querySelector(".id-input")
const passwordInput = document.querySelector(".password-input")

const loginContainer = document.querySelector(".login-container")
const twoFaContainer = document.querySelector(".twofa-container")

let currentCategory = (document.querySelector(".category [class$='active-login-catergory']") != null) ? document.querySelector(".category [class$='active-login-catergory']").textContent.trim() : null

categoryBtns.forEach((e) => {
    addClickListener(e, () => {
        e.classList.add("active-login-catergory")
        categoryBtns.forEach((f) => {
            if (f != e) f.classList.remove("active-login-catergory")
        })
        if (currentCategory != null) currentCategory = document.querySelector(".category [class$='active-login-catergory']").textContent.trim()

        switch (currentCategory) {
            case "Student":
                idNumLabel.textContent = "Student ID Number"
                IdNumInput.placeholder = "Enter you student ID"
                break;
            case "Faculty":
                idNumLabel.textContent = "Faculty ID Number"
                IdNumInput.placeholder = "Enter you Faculty ID"
                break;
            default:

                break;
        }
    })
})

toggleElement(twoFaContainer)

addClickListener(clearBtn, () => {
    IdNumInput.value = ""
    passwordInput.value = ""
})

addClickListener(loginBtn, () => {
    toggleElement(twoFaContainer)
    toggleElement(loginContainer)
})

addClickListener(verifyBtn, () => {
    window.location.href = "dashboard.html"
})

addClickListener(backBtn, () => {
    toggleElement(twoFaContainer)
    toggleElement(loginContainer)
})

// -- Guideline Container -- // 
const acceptChbx = document.querySelector(".guide-agree-chkbx")
const proceedBtn = document.querySelector(".guide-proceed-btn")

addClickListener(proceedBtn, () => {
    if (acceptChbx.checked) {
        
    }
})

// --- Evaluation Container --- //

const goToGuidelinesBtn = document.querySelector(".goto-guidelines-btn");

addClickListener(goToGuidelinesBtn, () => {
    displayGuidelines("active-nav-btn")
    navBtns.forEach((f) => {
        f.classList.remove("active-nav-btn")
    })
    if (document.querySelector(".guidelines-nav-btn") != null ) document.querySelector(".guidelines-nav-btn").classList.add("active-nav-btn") 
    if (currentTab != null) currentTab = document.querySelector(".nav-btn.active-nav-btn").textContent.trim();
})


// --- Logout Modal --- ///
const logoutBtn = document.querySelector(".logout-btn")
const closeLogoutBtn = document.querySelector(".close-logout-btn")
const continueBtn = document.querySelector(".continue-btn")
const coverBackground = document.querySelector(".cover-background") 
const firstWarning = document.querySelectorAll(".warning-1")
const finalWarning = document.querySelectorAll(".warning-2")
const warningBtn1 = document.querySelector(".warning-btn-1")
const warningBtn2 = document.querySelector(".warning-btn-2")

finalWarning.forEach((e) => {
    toggleElement(e)
})

addClickListener(logoutBtn, () => {
    toggleElement(coverBackground)
})

addClickListener(continueBtn, () => {
    toggleElement(coverBackground)
})

addClickListener(closeLogoutBtn, () => {
    toggleElement(coverBackground)
});

addClickListener(warningBtn1, () => {
    firstWarning.forEach((e) => {
        toggleElement(e)
    })
    finalWarning.forEach((e) => {
        toggleElement(e)
    })
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

