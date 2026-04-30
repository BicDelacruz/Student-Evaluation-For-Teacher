document.addEventListener("DOMContentLoaded", () => {
    initializeDarkMode();
    initializePinInputs();
    initializeModals();
    initializeStudentActions();
    initializeFacultyActions();
    initializeSectionAutoFill();
    initializeFilterForm();
    initializeAcademicStructure();
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

function initializeAcademicStructure() {
    if (!window.ADMIN_ACADEMIC_STRUCTURE) {
        return;
    }

    initializeStructureTabs();
    initializeStructureSearch();
    initializeStructureEntityActions();
    initializeStructureSectionForms();
    initializeStructureSubjectForms();
}

function initializeStructureTabs() {
    const container = document.querySelector("[data-structure-tabs]");
    if (!container) {
        return;
    }

    const buttons = Array.from(container.querySelectorAll("[data-structure-tab]"));
    const panes = Array.from(document.querySelectorAll("[data-structure-pane]"));
    let activeTab = container.getAttribute("data-active-tab") || "departments";

    const setActiveTab = (tab, pushHistory) => {
        activeTab = tab;

        buttons.forEach((button) => {
            const isActive = button.getAttribute("data-structure-tab") === tab;
            button.classList.toggle("is-active", isActive);
        });

        panes.forEach((pane) => {
            const isActive = pane.getAttribute("data-structure-pane") === tab;
            pane.classList.toggle("is-active", isActive);
        });

        const nextUrl = new URL(window.location.href);
        nextUrl.searchParams.set("tab", tab);
        if (pushHistory) {
            window.history.pushState({}, "", nextUrl);
        }
    };

    buttons.forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            const tab = button.getAttribute("data-structure-tab");
            if (!tab || tab === activeTab) {
                return;
            }

            setActiveTab(tab, true);
        });
    });

    window.addEventListener("popstate", () => {
        const currentUrl = new URL(window.location.href);
        setActiveTab(currentUrl.searchParams.get("tab") || "departments", false);
    });

    setActiveTab(activeTab, false);
}

function initializeStructureSearch() {
    document.querySelectorAll("[data-structure-search]").forEach((input) => {
        const scopeName = input.getAttribute("data-structure-search");
        const scope = document.querySelector(`[data-search-scope="${scopeName}"]`);
        if (!scope) {
            return;
        }

        const items = Array.from(scope.querySelectorAll("[data-search-item]"));
        const applyFilter = () => {
            const query = input.value.trim().toLowerCase();
            items.forEach((item) => {
                const haystack = (item.getAttribute("data-search-text") || "").toLowerCase();
                item.hidden = query !== "" && !haystack.includes(query);
            });
        };

        input.addEventListener("input", applyFilter);
        applyFilter();
    });
}

function initializeStructureEntityActions() {
    const currentRecords = {
        department: null,
        course: null,
        section: null,
        subject: null,
    };

    document.querySelectorAll("[data-structure-json]").forEach((button) => {
        button.addEventListener("click", () => {
            const rawData = button.getAttribute("data-structure-json");
            const entity = button.getAttribute("data-structure-entity");
            const action = button.getAttribute("data-structure-action");

            if (!rawData || !entity || !action) {
                return;
            }

            const record = JSON.parse(rawData);
            currentRecords[entity] = record;

            if (action === "view") {
                fillStructureView(entity, record);
            }

            if (action === "edit") {
                fillStructureEdit(entity, record);
            }
        });
    });

    document.querySelectorAll("[data-structure-view-edit]").forEach((button) => {
        button.addEventListener("click", () => {
            const entity = button.getAttribute("data-structure-view-edit");
            if (!entity || !currentRecords[entity]) {
                return;
            }

            const viewModal = document.querySelector(`#view${capitalize(entity)}Modal`);
            const editModal = document.querySelector(`#edit${capitalize(entity)}Modal`);
            if (viewModal) {
                viewModal.classList.remove("is-visible");
            }

            fillStructureEdit(entity, currentRecords[entity]);
            if (editModal) {
                editModal.classList.add("is-visible");
            }
        });
    });
}

function fillStructureView(entity, record) {
    if (entity === "department") {
        setStructureViewValues("department", {
            code: record.code || "",
            name: record.name || "",
            head: record.head || "",
        });
        return;
    }

    if (entity === "course") {
        setStructureViewValues("course", {
            code: record.code || "",
            status: record.status || "",
            name: record.name || "",
            description: record.description || "",
            department_name: record.department_name || structureDepartmentName(record.department_code || ""),
            year_levels: record.year_levels || "",
            student_count: record.student_count || "",
        });
        return;
    }

    if (entity === "section") {
        setStructureViewValues("section", {
            name: record.name || "",
            department_label: structureDepartmentName(record.department_code || ""),
            course_label: structureCourseLabel(record.course_code || ""),
            year_level: record.year_level || "",
            academic_year: record.academic_year || "",
            semester: record.semester || "",
            total_students: record.total_students || 0,
        });
        return;
    }

    if (entity === "subject") {
        setStructureViewValues("subject", {
            code: record.code || "",
            units: record.units || "",
            title: record.title || "",
            description: record.description || "",
            department_label: record.is_general_education ? "All Departments (General Education)" : structureDepartmentName(record.department_code || ""),
            course_labels: record.is_general_education ? "General Education" : (record.course_codes || []).map((code) => structureCourseLabel(code)).join(", "),
            year_level: record.year_level || "",
            semester: record.semester || "",
        });
    }
}

function setStructureViewValues(prefix, values) {
    const modal = document.querySelector(`#view${capitalize(prefix)}Modal`);
    if (!modal) {
        return;
    }

    Object.entries(values).forEach(([key, value]) => {
        const field = modal.querySelector(`[data-structure-view-field="${prefix}-${key}"]`);
        if (field) {
            field.value = value ?? "";
        }
    });
}

function fillStructureEdit(entity, record) {
    if (entity === "department") {
        const modal = document.querySelector("#editDepartmentModal");
        if (!modal) {
            return;
        }

        setValue(modal, '[name="original_code"]', record.code || "");
        setValue(modal, '[name="code"]', record.code || "");
        setValue(modal, '[name="name"]', record.name || "");
        setValue(modal, '[name="head"]', record.head || "");
        return;
    }

    if (entity === "course") {
        const modal = document.querySelector("#editCourseModal");
        if (!modal) {
            return;
        }

        setValue(modal, '[name="original_code"]', record.code || "");
        setValue(modal, '[name="code"]', record.code || "");
        setValue(modal, '[name="status"]', record.status || "active");
        setValue(modal, '[name="name"]', record.name || "");
        setValue(modal, '[name="description"]', record.description || "");
        setValue(modal, '[name="department_code"]', record.department_code || "");
        setValue(modal, '[name="year_levels"]', String(record.year_levels || 4));
        setValue(modal, '[name="student_count"]', String(record.student_count || 0));
        return;
    }

    if (entity === "section") {
        const modal = document.querySelector("#editSectionModal");
        if (!modal) {
            return;
        }

        setValue(modal, '[name="original_name"]', record.name || "");
        setValue(modal, '[name="original_course_code"]', record.course_code || "");
        setValue(modal, '[name="original_year_level"]', record.year_level || "");
        setValue(modal, '[name="original_semester"]', record.semester || "");
        setValue(modal, '[name="original_academic_year"]', record.academic_year || "");
        setValue(modal, '[name="name"]', record.name || "");
        setValue(modal, '[name="department_code"]', record.department_code || "");
        setValue(modal, '[name="course_code"]', record.course_code || "");
        setValue(modal, '[name="year_level"]', record.year_level || "");
        setValue(modal, '[name="semester"]', record.semester || "");
        setValue(modal, '[name="academic_year"]', record.academic_year || "");
        syncStructureCourseSelect(modal, "section");
        return;
    }

    if (entity === "subject") {
        const modal = document.querySelector("#editSubjectModal");
        if (!modal) {
            return;
        }

        setValue(modal, '[name="original_code"]', record.code || "");
        setValue(modal, '[name="code"]', record.code || "");
        setValue(modal, '[name="units"]', record.units || "");
        setValue(modal, '[name="title"]', record.title || "");
        setValue(modal, '[name="description"]', record.description || "");

        const geToggle = modal.querySelector("[data-ge-toggle]");
        if (geToggle) {
            geToggle.checked = Boolean(record.is_general_education);
        }

        setValue(modal, '[name="department_code"]', record.is_general_education ? "" : (record.department_code || ""));
        modal.querySelectorAll('input[name="course_codes[]"]').forEach((checkbox) => {
            checkbox.checked = Array.isArray(record.course_codes) && record.course_codes.includes(checkbox.value);
        });
        setValue(modal, '[name="year_level"]', record.year_level || "");
        setValue(modal, '[name="semester"]', record.semester || "");
        syncSubjectForm(modal);
    }
}

function initializeStructureSectionForms() {
    document.querySelectorAll("[data-structure-section-form], [data-structure-section-edit-form]").forEach((form) => {
        syncStructureCourseSelect(form, "section");

        const departmentSelect = form.querySelector('[data-structure-course-filter="section"]');
        if (departmentSelect) {
            departmentSelect.addEventListener("change", () => syncStructureCourseSelect(form, "section"));
        }

        form.addEventListener("submit", (event) => {
            if (!form.matches("[data-structure-section-form]")) {
                return;
            }

            const hasSectionName = Array.from(form.querySelectorAll("[data-section-name-field]")).some((field) => field.value.trim() !== "");
            if (!hasSectionName) {
                event.preventDefault();
                window.alert("Enter at least one section name before saving.");
            }
        });
    });
}

function syncStructureCourseSelect(container, context) {
    const departmentSelect = container.querySelector(`[data-structure-course-filter="${context}"]`);
    const courseSelect = container.querySelector(`[data-structure-course-select="${context}"]`);
    if (!departmentSelect || !courseSelect) {
        return;
    }

    const departmentCode = departmentSelect.value;
    let hasVisibleSelectedOption = false;

    Array.from(courseSelect.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;
            return;
        }

        const isVisible = !departmentCode || option.getAttribute("data-department-code") === departmentCode;
        option.hidden = !isVisible;

        if (option.value === courseSelect.value && isVisible) {
            hasVisibleSelectedOption = true;
        }
    });

    if (courseSelect.value && !hasVisibleSelectedOption) {
        courseSelect.value = "";
    }
}

function initializeStructureSubjectForms() {
    document.querySelectorAll("[data-structure-subject-form], [data-structure-subject-edit-form]").forEach((form) => {
        const geToggle = form.querySelector("[data-ge-toggle]");
        const departmentSelect = form.querySelector('[data-structure-course-filter="subject"]');

        if (geToggle) {
            geToggle.addEventListener("change", () => syncSubjectForm(form));
        }

        if (departmentSelect) {
            departmentSelect.addEventListener("change", () => syncSubjectForm(form));
        }

        form.addEventListener("submit", (event) => {
            const isGe = Boolean(geToggle && geToggle.checked);
            if (isGe) {
                return;
            }

            const hasSelectedCourse = Array.from(form.querySelectorAll('input[name="course_codes[]"]')).some((checkbox) => checkbox.checked && !checkbox.disabled);
            if (!hasSelectedCourse) {
                event.preventDefault();
                window.alert("Select at least one course or mark the subject as General Education.");
            }
        });

        syncSubjectForm(form);
    });
}

function syncSubjectForm(form) {
    const geToggle = form.querySelector("[data-ge-toggle]");
    const departmentWrap = form.querySelector("[data-subject-department-wrap]");
    const coursesWrap = form.querySelector("[data-subject-courses-wrap]");
    const departmentSelect = form.querySelector('[data-structure-course-filter="subject"]');
    const checklist = form.querySelector("[data-structure-course-checklist]");

    if (!geToggle || !departmentWrap || !coursesWrap || !departmentSelect || !checklist) {
        return;
    }

    const isGe = geToggle.checked;
    departmentWrap.classList.toggle("is-hidden", isGe);
    coursesWrap.classList.toggle("is-hidden", isGe);

    const selectedDepartment = departmentSelect.value;

    checklist.querySelectorAll(".course-checklist-item").forEach((item) => {
        const checkbox = item.querySelector('input[name="course_codes[]"]');
        if (!checkbox) {
            return;
        }

        if (isGe) {
            item.classList.add("is-hidden");
            checkbox.checked = false;
            checkbox.disabled = true;
            return;
        }

        const matchesDepartment = !selectedDepartment || item.getAttribute("data-department-code") === selectedDepartment;
        item.classList.toggle("is-hidden", !matchesDepartment);
        checkbox.disabled = !matchesDepartment;
        if (!matchesDepartment) {
            checkbox.checked = false;
        }
    });

    if (isGe) {
        departmentSelect.value = "";
    }
}

function structureDepartmentName(code) {
    if (code === "ALL") {
        return "All Departments (General Education)";
    }

    const record = (window.ADMIN_ACADEMIC_STRUCTURE.departments || []).find((department) => department.code === code);
    return record ? record.name : code;
}

function structureCourseLabel(code) {
    const record = (window.ADMIN_ACADEMIC_STRUCTURE.courses || []).find((course) => course.code === code);
    return record ? `${record.code} - ${record.name}` : code;
}

function setValue(container, selector, value) {
    const field = container.querySelector(selector);
    if (!field) {
        return;
    }

    field.value = value ?? "";
}

function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}


