// --- Dashboard Navigation Sidebar --- //
const navBtns = document.querySelectorAll(".nav-btn")

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
    if (guidelinesAgreed) {
        evalRestrictDiv.classList.add("hidden")
    }
}

function displayHistory() {
    hideTabs()
    toggleElement(historyContainer)
}

function displaySettings() {
    hideTabs()
    toggleElement(settingsContainer)
}

function changeCurrentTab(navBtnClass) {
    navBtns.forEach((f) => {
        f.classList.remove("active-nav-btn")
    })
    document.querySelector(navBtnClass).classList.add("active-nav-btn") 
    currentTab = document.querySelector(".nav-btn.active-nav-btn").textContent.trim();

    /*
        .dashboard-nav-btn
        .guidelines-nav-btn
        .eval-nav-btn
        .history-nav-btn
        .settings-nav-btn 
    */
}

// --- Login and Two Factor Auth --- //

const loginBtn = document.querySelector(".login-btn")
const clearBtn = document.querySelector(".clear-btn")
const verifyBtn = document.querySelector(".verify-btn")
const passkeyBtn = document.querySelector(".passkey-btn")
const backBtn = document.querySelector(".back-btn")
const passwordToggle = document.querySelector(".password-toggle")
const pinInputs = document.querySelectorAll(".pin-input")

const categoryBtns = document.querySelectorAll(".category-login-btn")

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
        currentCategory = document.querySelector(".category [class$='active-login-catergory']").textContent.trim()

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
    if (passwordToggle != null) {
        passwordInput.type = "password"
        passwordToggle.classList.remove("is-visible")
        passwordToggle.setAttribute("aria-label", "Show password")
        passwordToggle.setAttribute("aria-pressed", "false")
    }
})

addClickListener(loginBtn, () => {
    toggleElement(twoFaContainer)
    toggleElement(loginContainer)
    if (pinInputs.length > 0) {
        pinInputs.forEach((input) => {
            input.value = ""
        })
        pinInputs[0].focus()
    }
})

addClickListener(passwordToggle, () => {
    if (passwordInput == null) return

    const shouldShowPassword = passwordInput.type === "password"
    passwordInput.type = shouldShowPassword ? "text" : "password"
    passwordToggle.classList.toggle("is-visible", shouldShowPassword)
    passwordToggle.setAttribute("aria-label", shouldShowPassword ? "Hide password" : "Show password")
    passwordToggle.setAttribute("aria-pressed", shouldShowPassword ? "true" : "false")
})

addClickListener(verifyBtn, () => {
    window.location.href = "dashboard.html"
})

addClickListener(backBtn, () => {
    toggleElement(twoFaContainer)
    toggleElement(loginContainer)
    pinInputs.forEach((input) => {
        input.value = ""
    })
})

pinInputs.forEach((input, index) => {
    input.addEventListener("input", (event) => {
        const cleanedValue = event.target.value.replace(/\D/g, "").slice(-1)
        event.target.value = cleanedValue

        if (cleanedValue && index < pinInputs.length - 1) {
            pinInputs[index + 1].focus()
        }
    })

    input.addEventListener("keydown", (event) => {
        if (event.key === "Backspace" && input.value === "" && index > 0) {
            pinInputs[index - 1].focus()
        }

        if (event.key === "ArrowLeft" && index > 0) {
            pinInputs[index - 1].focus()
        }

        if (event.key === "ArrowRight" && index < pinInputs.length - 1) {
            pinInputs[index + 1].focus()
        }
    })

    input.addEventListener("paste", (event) => {
        const pastedData = (event.clipboardData || window.clipboardData).getData("text")
        const digits = pastedData.replace(/\D/g, "").slice(0, pinInputs.length).split("")

        if (digits.length === 0) return

        event.preventDefault()
        pinInputs.forEach((pinInput, pinIndex) => {
            pinInput.value = digits[pinIndex] || ""
        })

        const nextIndex = Math.min(digits.length, pinInputs.length) - 1
        if (nextIndex >= 0) {
            pinInputs[nextIndex].focus()
        }
    })
})

// -- Guideline Container -- // 
const acceptChbx = document.querySelector(".guide-agree-chkbx")
const proceedBtn = document.querySelector(".guide-proceed-btn")
let guidelinesAgreed = false;

if (acceptChbx) acceptChbx.addEventListener("change", () => {
    if (acceptChbx.checked) {
        proceedBtn.classList.add("guide-proceed-btn-accepted")
    } else {
        proceedBtn.classList.remove("guide-proceed-btn-accepted")
    }
})

addClickListener(proceedBtn, () => {
    if (acceptChbx.checked) {
        guidelinesAgreed = true
        acceptChbx.disabled = true
        displayEval()
        changeCurrentTab(".eval-nav-btn")
    }   
})

// --- Evaluation Container --- //

const goToGuidelinesBtn = document.querySelector(".goto-guidelines-btn")
const evalRestrictDiv = document.querySelector(".eval-restrict")



addClickListener(goToGuidelinesBtn, () => {
    displayGuidelines()
    changeCurrentTab(".guidelines-nav-btn")
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


