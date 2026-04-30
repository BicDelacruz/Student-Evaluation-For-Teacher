document.addEventListener("DOMContentLoaded", () => {
    initializeDarkMode();
    initializePinInputs();
    initializeModals();
    initializeStudentActions();
    initializeFacultyActions();
    initializeSectionAutoFill();
    initializeFilterForm();
});

function initializeDarkMode() {
    const toggle = document.querySelector("[data-dark-mode-toggle]");
    const storedTheme = window.localStorage.getItem("admin-theme");

    if (storedTheme === "dark") {
        document.body.classList.add("dark-mode");
    }

    if (!toggle) {
        return;
    }

    toggle.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        const currentTheme = document.body.classList.contains("dark-mode") ? "dark" : "light";
        window.localStorage.setItem("admin-theme", currentTheme);
    });
}

function initializePinInputs() {
    const pinInputs = Array.from(document.querySelectorAll(".pin-input"));
    const pinTarget = document.querySelector("[data-pin-target]");

    if (pinInputs.length === 0 || !pinTarget) {
        return;
    }

    const updatePinValue = () => {
        pinTarget.value = pinInputs.map((input) => input.value).join("");
    };

    pinInputs.forEach((input, index) => {
        input.addEventListener("input", (event) => {
            const cleaned = event.target.value.replace(/\D/g, "").slice(-1);
            event.target.value = cleaned;
            updatePinValue();

            if (cleaned && pinInputs[index + 1]) {
                pinInputs[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (event) => {
            if (event.key === "Backspace" && input.value === "" && pinInputs[index - 1]) {
                pinInputs[index - 1].focus();
            }

            if (event.key === "ArrowLeft" && pinInputs[index - 1]) {
                pinInputs[index - 1].focus();
            }

            if (event.key === "ArrowRight" && pinInputs[index + 1]) {
                pinInputs[index + 1].focus();
            }
        });

        input.addEventListener("paste", (event) => {
            const text = (event.clipboardData || window.clipboardData).getData("text");
            const digits = text.replace(/\D/g, "").slice(0, pinInputs.length).split("");

            if (digits.length === 0) {
                return;
            }

            event.preventDefault();
            pinInputs.forEach((pinInput, digitIndex) => {
                pinInput.value = digits[digitIndex] || "";
            });
            updatePinValue();

            const targetIndex = Math.min(digits.length - 1, pinInputs.length - 1);
            pinInputs[targetIndex].focus();
        });
    });
}

function initializeModals() {
    const openButtons = document.querySelectorAll("[data-open-modal]");
    const closeButtons = document.querySelectorAll("[data-close-modal]");

    openButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const targetSelector = button.getAttribute("data-open-modal");
            const modal = document.querySelector(targetSelector);
            if (modal) {
                modal.classList.add("is-visible");
            }
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const modal = button.closest(".modal");
            if (modal) {
                modal.classList.remove("is-visible");
            }
        });
    });

    document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.classList.remove("is-visible");
            }
        });
    });
}

function initializeStudentActions() {
    const studentButtons = document.querySelectorAll("[data-student-json]");
    const viewModal = document.querySelector("#viewStudentModal");
    const editModal = document.querySelector("#editStudentModal");
    const viewEditButton = document.querySelector("[data-view-edit-trigger]");
    let currentStudent = null;

    studentButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const rawData = button.getAttribute("data-student-json");
            if (!rawData) {
                return;
            }

            currentStudent = JSON.parse(rawData);

            if (button.matches("[data-student-view]")) {
                fillStudentView(currentStudent);
            }

            if (button.matches("[data-student-edit]")) {
                fillStudentEdit(currentStudent);
            }
        });
    });

    if (viewEditButton && viewModal && editModal) {
        viewEditButton.addEventListener("click", () => {
            if (!currentStudent) {
                return;
            }

            viewModal.classList.remove("is-visible");
            fillStudentEdit(currentStudent);
            editModal.classList.add("is-visible");
        });
    }

    document.querySelectorAll("[data-confirm-message]").forEach((button) => {
        button.addEventListener("click", (event) => {
            const message = button.getAttribute("data-confirm-message") || "Are you sure?";
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function fillStudentView(student) {
    setFormValues(document.querySelector("#viewStudentModal"), student, true);
    const badge = document.querySelector("[data-view-status]");
    if (badge) {
        badge.textContent = student.status || "active";
        badge.className = `status-badge ${student.status === "suspended" ? "suspended" : "active"}`;
    }
}

function fillStudentEdit(student) {
    const modal = document.querySelector("#editStudentModal");
    setFormValues(modal, student, false);

    const originalIdInput = modal.querySelector('input[name="original_student_id"]');
    if (originalIdInput) {
        originalIdInput.value = student.student_id || "";
    }

    const passwordInput = modal.querySelector('input[name="password"]');
    if (passwordInput) {
        passwordInput.value = "";
    }

    syncSectionInputs(modal);
}

function setFormValues(container, student, isReadOnly) {
    if (!container || !student) {
        return;
    }

    container.querySelectorAll("[data-field]").forEach((field) => {
        const key = field.getAttribute("data-field");
        if (!key) {
            return;
        }

        if (field.tagName === "SELECT") {
            field.value = student[key] || "";
        } else {
            field.value = student[key] || "";
        }

        if (isReadOnly) {
            field.setAttribute("readonly", "readonly");
            field.setAttribute("disabled", "disabled");
        } else {
            if (field.hasAttribute("data-keep-readonly")) {
                field.setAttribute("readonly", "readonly");
            } else {
                field.removeAttribute("readonly");
            }
            field.removeAttribute("disabled");
        }
    });
}

function initializeSectionAutoFill() {
    document.querySelectorAll("[data-student-form]").forEach((form) => {
        const courseSelect = form.querySelector('[name="course"]');
        const yearSelect = form.querySelector('[name="year_level"]');
        const sectionSelect = form.querySelector('[name="section"]');

        if (!courseSelect || !yearSelect || !sectionSelect) {
            return;
        }

        const refresh = () => syncSectionInputs(form);
        courseSelect.addEventListener("change", refresh);
        yearSelect.addEventListener("change", refresh);
        sectionSelect.addEventListener("change", refresh);

        refresh();
    });
}

function initializeFilterForm() {
    const filterForm = document.querySelector("[data-filter-form]");
    if (!filterForm) {
        return;
    }

    filterForm.querySelectorAll("select").forEach((select) => {
        select.addEventListener("change", () => {
            filterForm.submit();
        });
    });
}

function syncSectionInputs(form) {
    const courseSelect = form.querySelector('[name="course"]');
    const yearSelect = form.querySelector('[name="year_level"]');
    const sectionSelect = form.querySelector('[name="section"]');
    const semesterInput = form.querySelector('[name="semester"]');
    const yearInput = form.querySelector('[name="academic_year"]');
    const departmentInput = form.querySelector('[name="department"]');

    if (!courseSelect || !yearSelect || !sectionSelect) {
        return;
    }

    const metaMap = window.ADMIN_SECTION_META || {};
    const currentValue = sectionSelect.value;

    Array.from(sectionSelect.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;
            return;
        }

        const meta = metaMap[option.value];
        const matchesCourse = !courseSelect.value || (meta && meta.course === courseSelect.value);
        const matchesYear = !yearSelect.value || (meta && meta.year_level === yearSelect.value);
        option.hidden = !(matchesCourse && matchesYear);
    });

    if (currentValue) {
        const selectedOption = Array.from(sectionSelect.options).find((option) => option.value === currentValue && !option.hidden);
        if (!selectedOption) {
            sectionSelect.value = "";
        }
    }

    const selectedMeta = metaMap[sectionSelect.value];
    if (!selectedMeta) {
        if (semesterInput) {
            semesterInput.value = "";
        }
        if (yearInput) {
            yearInput.value = "";
        }
        return;
    }

    if (semesterInput) {
        semesterInput.value = selectedMeta.semester || "";
    }
    if (yearInput) {
        yearInput.value = selectedMeta.academic_year || "";
    }
    if (departmentInput) {
        departmentInput.value = selectedMeta.department || "";
    }
    if (courseSelect && !courseSelect.value) {
        courseSelect.value = selectedMeta.course || "";
    }
    if (yearSelect && !yearSelect.value) {
        yearSelect.value = selectedMeta.year_level || "";
    }
}

function initializeFacultyActions() {
    const facultyButtons = document.querySelectorAll("[data-faculty-json]");
    const viewModal = document.querySelector("#viewFacultyModal");
    const editModal = document.querySelector("#editFacultyModal");
    const viewEditButton = document.querySelector("[data-view-faculty-edit-trigger]");
    let currentFaculty = null;

    facultyButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const rawData = button.getAttribute("data-faculty-json");
            if (!rawData) {
                return;
            }

            currentFaculty = JSON.parse(rawData);

            if (button.matches("[data-faculty-view]")) {
                fillFacultyView(currentFaculty);
            }

            if (button.matches("[data-faculty-edit]")) {
                fillFacultyEdit(currentFaculty);
            }
        });
    });

    if (viewEditButton && viewModal && editModal) {
        viewEditButton.addEventListener("click", () => {
            if (!currentFaculty) {
                return;
            }

            viewModal.classList.remove("is-visible");
            fillFacultyEdit(currentFaculty);
            editModal.classList.add("is-visible");
        });
    }
}

function fillFacultyView(faculty) {
    const modal = document.querySelector("#viewFacultyModal");
    if (!modal) {
        return;
    }

    modal.querySelectorAll("[data-faculty-field]").forEach((field) => {
        const key = field.getAttribute("data-faculty-field");
        if (!key) {
            return;
        }

        field.value = faculty[key] ?? "";
        field.setAttribute("readonly", "readonly");
    });

    const badge = modal.querySelector("[data-view-faculty-status]");
    if (badge) {
        const status = faculty.status || "active";
        badge.textContent = status;
        badge.className = `status-badge ${status === "inactive" ? "inactive" : "active"}`;
    }
}

function fillFacultyEdit(faculty) {
    const modal = document.querySelector("#editFacultyModal");
    if (!modal) {
        return;
    }

    modal.querySelectorAll("[data-faculty-field]").forEach((field) => {
        const key = field.getAttribute("data-faculty-field");
        if (!key) {
            return;
        }

        field.value = faculty[key] ?? "";
        field.removeAttribute("readonly");
        field.removeAttribute("disabled");
    });

    const originalInput = modal.querySelector('input[name="original_faculty_id"]');
    if (originalInput) {
        originalInput.value = faculty.faculty_id || "";
    }

    const statusInput = modal.querySelector('input[name="status"]');
    if (statusInput) {
        statusInput.value = faculty.status || "active";
    }

    const passwordInput = modal.querySelector('input[name="password"]');
    if (passwordInput) {
        passwordInput.value = "";
    }
}


