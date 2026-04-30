document.addEventListener("DOMContentLoaded", () => {
    initializeDarkMode();
    initializePinInputs();
    initializeModals();
    initializeStudentActions();
    initializeFacultyActions();
    initializeSectionAutoFill();
    initializeFilterForm();
    initializeAcademicStructure();
    initializeAssignmentManagement();
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

function initializeAssignmentManagement() {
    if (!window.ADMIN_ASSIGNMENT_MANAGEMENT) {
        return;
    }

    initializeAssignmentTabs();
    initializeAssignmentFilters();
    initializeAssignmentActions();
    initializeAssignmentFacultyForms();
    initializeAssignmentSectionForms();
}

function initializeAssignmentTabs() {
    const container = document.querySelector("[data-assignment-tabs]");
    if (!container) {
        return;
    }

    const buttons = Array.from(container.querySelectorAll("[data-assignment-tab]"));
    const panes = Array.from(document.querySelectorAll("[data-assignment-pane]"));
    let activeTab = container.getAttribute("data-active-tab") || "faculty_to_subject";

    const setActiveTab = (tab, pushHistory) => {
        activeTab = tab;

        buttons.forEach((button) => {
            const isActive = button.getAttribute("data-assignment-tab") === tab;
            button.classList.toggle("is-active", isActive);
        });

        panes.forEach((pane) => {
            const isActive = pane.getAttribute("data-assignment-pane") === tab;
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
            const tab = button.getAttribute("data-assignment-tab");
            if (!tab || tab === activeTab) {
                return;
            }

            setActiveTab(tab, true);
        });
    });

    window.addEventListener("popstate", () => {
        const currentUrl = new URL(window.location.href);
        setActiveTab(currentUrl.searchParams.get("tab") || "faculty_to_subject", false);
    });

    setActiveTab(activeTab, false);
}

function initializeAssignmentFilters() {
    const facultyState = {
        search: "",
        subject: "",
        faculty: "",
        course: "",
    };
    const sectionState = {
        search: "",
        course: "",
        section: "",
        year: "",
    };

    const facultyItems = Array.from(document.querySelectorAll('[data-assignment-type="faculty"]'));
    const sectionItems = Array.from(document.querySelectorAll('[data-assignment-type="section"]'));

    const applyFacultyFilters = () => {
        let visibleCount = 0;
        facultyItems.forEach((item) => {
            const searchText = (item.getAttribute("data-search-text") || "").toLowerCase();
            const subject = item.getAttribute("data-filter-subject") || "";
            const faculty = item.getAttribute("data-filter-faculty") || "";
            const course = item.getAttribute("data-filter-course") || "";
            const courseList = course ? course.split(",") : [];

            const matches =
                (!facultyState.search || searchText.includes(facultyState.search)) &&
                (!facultyState.subject || subject === facultyState.subject) &&
                (!facultyState.faculty || faculty === facultyState.faculty) &&
                (!facultyState.course || course === "ALL" || courseList.includes(facultyState.course));

            item.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        const counter = document.querySelector('[data-assignment-count="faculty_to_subject"]');
        if (counter) {
            counter.textContent = String(visibleCount);
        }
    };

    const applySectionFilters = () => {
        let visibleCount = 0;
        sectionItems.forEach((item) => {
            const searchText = (item.getAttribute("data-search-text") || "").toLowerCase();
            const course = item.getAttribute("data-filter-course") || "";
            const section = item.getAttribute("data-filter-section") || "";
            const year = item.getAttribute("data-filter-year") || "";

            const matches =
                (!sectionState.search || searchText.includes(sectionState.search)) &&
                (!sectionState.course || course === sectionState.course) &&
                (!sectionState.section || section === sectionState.section) &&
                (!sectionState.year || year === sectionState.year);

            item.hidden = !matches;
            if (matches) {
                visibleCount += 1;
            }
        });

        const counter = document.querySelector('[data-assignment-count="section_to_subject"]');
        if (counter) {
            counter.textContent = String(visibleCount);
        }
    };

    const facultySearch = document.querySelector('[data-assignment-search="faculty_to_subject"]');
    if (facultySearch) {
        facultySearch.addEventListener("input", () => {
            facultyState.search = facultySearch.value.trim().toLowerCase();
            applyFacultyFilters();
        });
    }

    const facultySubjectFilter = document.querySelector('[data-assignment-filter="faculty-subject"]');
    if (facultySubjectFilter) {
        facultySubjectFilter.addEventListener("change", () => {
            facultyState.subject = facultySubjectFilter.value;
            applyFacultyFilters();
        });
    }

    const facultyNameFilter = document.querySelector('[data-assignment-filter="faculty-name"]');
    if (facultyNameFilter) {
        facultyNameFilter.addEventListener("change", () => {
            facultyState.faculty = facultyNameFilter.value;
            applyFacultyFilters();
        });
    }

    const facultyCourseFilter = document.querySelector('[data-assignment-filter="faculty-course"]');
    if (facultyCourseFilter) {
        facultyCourseFilter.addEventListener("change", () => {
            facultyState.course = facultyCourseFilter.value;
            applyFacultyFilters();
        });
    }

    const sectionSearch = document.querySelector('[data-assignment-search="section_to_subject"]');
    if (sectionSearch) {
        sectionSearch.addEventListener("input", () => {
            sectionState.search = sectionSearch.value.trim().toLowerCase();
            applySectionFilters();
        });
    }

    const sectionCourseFilter = document.querySelector('[data-assignment-filter="section-course"]');
    if (sectionCourseFilter) {
        sectionCourseFilter.addEventListener("change", () => {
            sectionState.course = sectionCourseFilter.value;
            applySectionFilters();
        });
    }

    const sectionNameFilter = document.querySelector('[data-assignment-filter="section-name"]');
    if (sectionNameFilter) {
        sectionNameFilter.addEventListener("change", () => {
            sectionState.section = sectionNameFilter.value;
            applySectionFilters();
        });
    }

    const sectionYearFilter = document.querySelector('[data-assignment-filter="section-year"]');
    if (sectionYearFilter) {
        sectionYearFilter.addEventListener("change", () => {
            sectionState.year = sectionYearFilter.value;
            applySectionFilters();
        });
    }

    applyFacultyFilters();
    applySectionFilters();
}

function initializeAssignmentActions() {
    const currentRecords = {
        faculty_assignment: null,
        section_enrollment: null,
    };

    document.querySelectorAll("[data-assignment-json]").forEach((button) => {
        button.addEventListener("click", () => {
            const rawData = button.getAttribute("data-assignment-json");
            const entity = button.getAttribute("data-assignment-entity");
            const action = button.getAttribute("data-assignment-action");

            if (!rawData || !entity || !action) {
                return;
            }

            const record = JSON.parse(rawData);
            currentRecords[entity] = record;

            if (entity === "faculty_assignment" && action === "view") {
                fillFacultyAssignmentView(record);
            }

            if (entity === "faculty_assignment" && action === "edit") {
                fillFacultyAssignmentEdit(record);
            }

            if (entity === "section_enrollment" && action === "view") {
                fillSectionEnrollmentView(record);
            }

            if (entity === "section_enrollment" && action === "edit") {
                fillSectionEnrollmentEdit(record);
            }
        });
    });
}

function initializeAssignmentFacultyForms() {
    document.querySelectorAll("[data-assignment-faculty-form]").forEach((form) => {
        const context = form.getAttribute("data-assignment-faculty-form");
        if (!context) {
            return;
        }

        const departmentSelect = form.querySelector(`[data-assignment-department="${context}"]`);
        const geToggle = form.querySelector(`[data-assignment-ge-toggle="${context}"]`);
        const subjectSelect = form.querySelector(`[data-assignment-subject-select="${context}"]`);

        if (departmentSelect) {
            departmentSelect.addEventListener("change", () => syncAssignmentFacultyForm(context));
        }

        if (geToggle) {
            geToggle.addEventListener("change", () => syncAssignmentFacultyForm(context));
        }

        form.querySelectorAll('input[name="course_codes[]"]').forEach((checkbox) => {
            checkbox.addEventListener("change", () => syncAssignmentFacultyForm(context));
        });

        if (subjectSelect) {
            subjectSelect.addEventListener("change", () => syncAssignmentSubjectAutoFill(context));
        }

        syncAssignmentFacultyForm(context);
    });
}

function syncAssignmentFacultyForm(context) {
    const form = document.querySelector(`[data-assignment-faculty-form="${context}"]`);
    if (!form) {
        return;
    }

    const departmentSelect = form.querySelector(`[data-assignment-department="${context}"]`);
    const facultySelect = form.querySelector(`[data-assignment-faculty-select="${context}"]`);
    const geToggle = form.querySelector(`[data-assignment-ge-toggle="${context}"]`);
    const courseWrap = form.querySelector(`[data-assignment-course-wrap="${context}"]`);
    const subjectSelect = form.querySelector(`[data-assignment-subject-select="${context}"]`);
    const submitButton = form.querySelector(`[data-assignment-submit="faculty-${context}"]`);
    const courseSummary = form.querySelector(`[data-assignment-course-summary="${context}"]`);
    const hiddenCoursesWrap = form.querySelector("[data-assignment-edit-hidden-courses]");

    const selectedDepartmentCode = departmentSelect ? departmentSelect.value : "";
    const selectedDepartmentName = assignmentDepartmentName(selectedDepartmentCode);
    const isGe = Boolean(geToggle && geToggle.checked) || Boolean(form.querySelector('input[name="is_general_education"][value="1"]'));
    const selectedCourses = Array.from(form.querySelectorAll('input[name="course_codes[]"]'))
        .filter((checkbox) => checkbox.checked && !checkbox.disabled)
        .map((checkbox) => checkbox.value);

    if (facultySelect) {
        let hasVisibleSelectedOption = false;
        Array.from(facultySelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const matchesDepartment = !selectedDepartmentName || option.getAttribute("data-department-name") === selectedDepartmentName;
            option.hidden = !matchesDepartment;
            if (option.value === facultySelect.value && matchesDepartment) {
                hasVisibleSelectedOption = true;
            }
        });

        if (facultySelect.value && !hasVisibleSelectedOption) {
            facultySelect.value = "";
        }
    }

    if (courseWrap) {
        courseWrap.classList.toggle("is-hidden", isGe);
    }

    form.querySelectorAll('input[name="course_codes[]"]').forEach((checkbox) => {
        const item = checkbox.closest(".assignment-checklist-item");
        const itemDepartment = item ? item.getAttribute("data-department-code") : "";
        const visible = !selectedDepartmentCode || itemDepartment === selectedDepartmentCode;
        checkbox.disabled = isGe || !visible;
        if (item) {
            item.classList.toggle("is-hidden", isGe || !visible);
        }
        if (checkbox.disabled) {
            checkbox.checked = false;
        }
    });

    if (courseSummary) {
        courseSummary.textContent = isGe
            ? "All Courses (General Education)"
            : (selectedCourses.length ? selectedCourses.join(", ") : "Saved course scope");
    }

    if (hiddenCoursesWrap && context === "edit") {
        const hiddenCourses = Array.from(hiddenCoursesWrap.querySelectorAll('input[name="course_codes[]"]')).map((input) => input.value);
        if (courseSummary) {
            courseSummary.textContent = isGe ? "All Courses (General Education)" : (hiddenCourses.length ? hiddenCourses.join(", ") : "Saved course scope");
        }
    }

    if (subjectSelect) {
        let hasVisibleSelectedSubject = false;
        Array.from(subjectSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionIsGe = option.getAttribute("data-is-ge") === "1";
            const optionDepartment = option.getAttribute("data-department-code") || "";
            const optionCourses = (option.getAttribute("data-course-codes") || "").split(",").filter(Boolean);

            let visible = false;
            if (isGe) {
                visible = optionIsGe;
            } else {
                const hasCourseMatch = selectedCourses.length > 0 && selectedCourses.some((courseCode) => optionCourses.includes(courseCode));
                visible = !optionIsGe && optionDepartment === selectedDepartmentCode && hasCourseMatch;
            }

            if (context === "edit" && !selectedCourses.length && !isGe) {
                visible = !optionIsGe && optionDepartment === selectedDepartmentCode;
            }

            option.hidden = !visible;
            if (option.value === subjectSelect.value && visible) {
                hasVisibleSelectedSubject = true;
            }
        });

        if (subjectSelect.value && !hasVisibleSelectedSubject) {
            subjectSelect.value = "";
        }
    }

    syncAssignmentSubjectAutoFill(context);

    if (submitButton) {
        const hasCourseSelection = isGe || selectedCourses.length > 0;
        const hasSubjectSelection = Boolean(subjectSelect && subjectSelect.value);
        submitButton.disabled = !(selectedDepartmentCode && hasCourseSelection && hasSubjectSelection);
    }
}

function syncAssignmentSubjectAutoFill(context) {
    const form = document.querySelector(`[data-assignment-faculty-form="${context}"]`);
    if (!form) {
        return;
    }

    const subjectSelect = form.querySelector(`[data-assignment-subject-select="${context}"]`);
    const yearInput = form.querySelector(`[data-assignment-year-level="${context}"]`);
    const semesterInput = form.querySelector(`[data-assignment-semester="${context}"]`);
    if (!subjectSelect || !yearInput || !semesterInput) {
        return;
    }

    const selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        yearInput.value = "";
        semesterInput.value = "";
        return;
    }

    yearInput.value = selectedOption.getAttribute("data-year-level") || "";
    semesterInput.value = selectedOption.getAttribute("data-semester") || "";
}

function initializeAssignmentSectionForms() {
    document.querySelectorAll("[data-assignment-section-form]").forEach((form) => {
        const context = form.getAttribute("data-assignment-section-form");
        if (!context) {
            return;
        }

        [
            `[data-assignment-section-department="${context}"]`,
            `[data-assignment-section-course="${context}"]`,
            `[data-assignment-section-year="${context}"]`,
            `[data-assignment-section-semester="${context}"]`,
            `[data-assignment-subject-type="${context}"]`,
            `[data-assignment-section-select="${context}"]`,
        ].forEach((selector) => {
            const field = form.querySelector(selector);
            if (field) {
                field.addEventListener("change", () => syncAssignmentSectionForm(context));
            }
        });

        form.querySelectorAll('input[name="section_names[]"], input[name="subject_codes[]"]').forEach((checkbox) => {
            checkbox.addEventListener("change", () => syncAssignmentSectionForm(context));
        });

        syncAssignmentSectionForm(context);
    });
}

function syncAssignmentSectionForm(context) {
    const form = document.querySelector(`[data-assignment-section-form="${context}"]`);
    if (!form) {
        return;
    }

    const departmentSelect = form.querySelector(`[data-assignment-section-department="${context}"]`);
    const courseSelect = form.querySelector(`[data-assignment-section-course="${context}"]`);
    const yearSelect = form.querySelector(`[data-assignment-section-year="${context}"]`);
    const semesterSelect = form.querySelector(`[data-assignment-section-semester="${context}"]`);
    const subjectTypeSelect = form.querySelector(`[data-assignment-subject-type="${context}"]`);
    const sectionSelect = form.querySelector(`[data-assignment-section-select="${context}"]`);
    const sectionChecklist = form.querySelector(`[data-assignment-section-checklist="${context}"]`);
    const subjectChecklist = form.querySelector(`[data-assignment-subject-checklist="${context}"]`);
    const submitButton = form.querySelector(`[data-assignment-submit="section-${context}"]`);

    const departmentCode = departmentSelect ? departmentSelect.value : "";
    const courseCode = courseSelect ? courseSelect.value : "";
    const yearLevel = yearSelect ? yearSelect.value : "";
    const semester = semesterSelect ? semesterSelect.value : "";
    const subjectType = subjectTypeSelect ? subjectTypeSelect.value : "program_only";

    if (courseSelect) {
        let hasVisibleSelectedOption = false;
        Array.from(courseSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible = !departmentCode || option.getAttribute("data-department-code") === departmentCode;
            option.hidden = !visible;
            if (option.value === courseSelect.value && visible) {
                hasVisibleSelectedOption = true;
            }
        });

        if (courseSelect.value && !hasVisibleSelectedOption) {
            courseSelect.value = "";
        }
    }

    if (sectionChecklist) {
        sectionChecklist.querySelectorAll(".assignment-checklist-item").forEach((item) => {
            const checkbox = item.querySelector('input[name="section_names[]"]');
            if (!checkbox) {
                return;
            }

            const visible =
                (!departmentCode || item.getAttribute("data-department-code") === departmentCode) &&
                (!courseCode || item.getAttribute("data-course-code") === courseCode) &&
                (!yearLevel || item.getAttribute("data-year-level") === yearLevel);

            item.classList.toggle("is-hidden", !visible);
            checkbox.disabled = !visible;
            if (!visible) {
                checkbox.checked = false;
            }
        });
    }

    if (sectionSelect) {
        let hasVisibleSelectedSection = false;
        Array.from(sectionSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible =
                (!departmentCode || option.getAttribute("data-department-code") === departmentCode) &&
                (!courseCode || option.getAttribute("data-course-code") === courseCode) &&
                (!yearLevel || option.getAttribute("data-year-level") === yearLevel);

            option.hidden = !visible;
            if (option.value === sectionSelect.value && visible) {
                hasVisibleSelectedSection = true;
            }
        });

        if (sectionSelect.value && !hasVisibleSelectedSection) {
            sectionSelect.value = "";
        }
    }

    if (subjectChecklist) {
        subjectChecklist.querySelectorAll(".assignment-checklist-item").forEach((item) => {
            const checkbox = item.querySelector('input[name="subject_codes[]"]');
            if (!checkbox) {
                return;
            }

            const itemIsGe = item.getAttribute("data-is-ge") === "1";
            const itemSemester = item.getAttribute("data-semester") || "";
            const itemYear = item.getAttribute("data-year-level") || "";
            const courseCodes = (item.getAttribute("data-course-codes") || "").split(",").filter(Boolean);

            const matchesType =
                (subjectType === "program_only" && !itemIsGe) ||
                (subjectType === "ge_only" && itemIsGe) ||
                subjectType === "program_and_ge";

            const matchesCourse = itemIsGe ? true : (!courseCode || courseCodes.includes(courseCode));
            const visible = matchesType && matchesCourse && (!yearLevel || itemYear === yearLevel) && (!semester || itemSemester === semester);

            item.classList.toggle("is-hidden", !visible);
            checkbox.disabled = !visible;
            if (!visible) {
                checkbox.checked = false;
            }
        });
    }

    updateAssignmentSelectedCount(form, `[data-assignment-selected-count="sections-${context}"]`, 'input[name="section_names[]"]', "section(s) selected");
    updateAssignmentSelectedCount(form, `[data-assignment-selected-count="subjects-${context}"]`, 'input[name="subject_codes[]"]', "subject(s) selected");

    if (submitButton) {
        const hasSections = context === "edit"
            ? Boolean(sectionSelect && sectionSelect.value)
            : Array.from(form.querySelectorAll('input[name="section_names[]"]')).some((checkbox) => checkbox.checked && !checkbox.disabled);
        const hasSubjects = Array.from(form.querySelectorAll('input[name="subject_codes[]"]')).some((checkbox) => checkbox.checked && !checkbox.disabled);
        submitButton.disabled = !(departmentCode && courseCode && yearLevel && semester && hasSections && hasSubjects);
    }
}

function updateAssignmentSelectedCount(form, counterSelector, checkboxSelector, suffix) {
    const counter = form.querySelector(counterSelector);
    if (!counter) {
        return;
    }

    const count = Array.from(form.querySelectorAll(checkboxSelector)).filter((checkbox) => checkbox.checked && !checkbox.disabled).length;
    counter.textContent = `${count} ${suffix}`;
}

function fillFacultyAssignmentView(record) {
    setAssignmentViewValue("faculty-member", assignmentFacultyName(record.faculty_id));
    setAssignmentViewValue("faculty-department", assignmentDepartmentName(record.department_code));
    setAssignmentViewValue("faculty-subject", assignmentSubjectLabel(record.subject_code));
    setAssignmentViewValue("faculty-courses", assignmentCourseScopeLabel(record));
    setAssignmentViewValue("faculty-year-level", record.year_level || "");
    setAssignmentViewValue("faculty-semester", record.semester || "");
    setAssignmentViewValue("faculty-academic-year", record.academic_year || "");
}

function fillFacultyAssignmentEdit(record) {
    const form = document.querySelector('[data-assignment-faculty-form="edit"]');
    if (!form) {
        return;
    }

    setValue(form, '[name="id"]', record.id || "");
    setValue(form, '[name="department_code"]', record.department_code || "");
    setValue(form, '[name="faculty_id"]', record.faculty_id || "");
    setValue(form, '[name="subject_code"]', record.subject_code || "");
    setValue(form, '[name="year_level"]', record.year_level || "");
    setValue(form, '[name="semester"]', record.semester || "");
    setValue(form, '[name="academic_year"]', record.academic_year || "");

    const hiddenCoursesWrap = form.querySelector("[data-assignment-edit-hidden-courses]");
    if (hiddenCoursesWrap) {
        const courseInputs = Array.isArray(record.course_codes)
            ? record.course_codes.map((courseCode) => `<input type="hidden" name="course_codes[]" value="${escapeHtml(courseCode)}">`).join("")
            : "";
        const geInput = record.is_general_education ? '<input type="hidden" name="is_general_education" value="1">' : "";
        hiddenCoursesWrap.innerHTML = `${courseInputs}${geInput}`;
    }

    syncAssignmentFacultyForm("edit");
}

function fillSectionEnrollmentView(record) {
    setAssignmentViewValue("section-department", assignmentDepartmentName(record.department_code));
    setAssignmentViewValue("section-course", assignmentCourseLabel(record.course_code));
    setAssignmentViewValue("section-year-level", record.year_level || "");
    setAssignmentViewValue("section-name", record.section_name || "");
    setAssignmentViewValue("section-semester", record.semester || "");
    setAssignmentViewValue("section-academic-year", record.academic_year || "");

    const statusBadge = document.querySelector('[data-assignment-view-status="section-status"]');
    if (statusBadge) {
        const status = record.status || "active";
        statusBadge.textContent = status;
        statusBadge.className = `status-badge ${status === "inactive" ? "inactive" : "active"}`;
    }

    const list = document.querySelector('[data-assignment-view-list="section-subjects"]');
    if (list) {
        const items = (record.subject_codes || []).map((subjectCode) => {
            const facultyAssignment = findMatchingFacultyAssignment(record, subjectCode);
            const facultyText = facultyAssignment ? assignmentFacultyName(facultyAssignment.faculty_id) : "No faculty assigned yet";
            return `<div class="assignment-detail-item"><strong>${escapeHtml(assignmentSubjectLabel(subjectCode))}</strong><span>Faculty: ${escapeHtml(facultyText)}</span></div>`;
        });
        list.innerHTML = items.length ? items.join("") : '<div class="assignment-detail-item"><span>No subjects assigned.</span></div>';
    }
}

function fillSectionEnrollmentEdit(record) {
    const form = document.querySelector('[data-assignment-section-form="edit"]');
    if (!form) {
        return;
    }

    setValue(form, '[name="id"]', record.id || "");
    setValue(form, '[name="department_code"]', record.department_code || "");
    setValue(form, '[name="course_code"]', record.course_code || "");
    setValue(form, '[name="year_level"]', record.year_level || "");
    setValue(form, '[name="semester"]', record.semester || "");
    setValue(form, '[name="section_name"]', record.section_name || "");
    setValue(form, '[name="subject_type"]', record.subject_type || "program_only");
    setValue(form, '[name="academic_year"]', record.academic_year || "");
    setValue(form, '[name="status"]', record.status || "active");

    form.querySelectorAll('input[name="subject_codes[]"]').forEach((checkbox) => {
        checkbox.checked = Array.isArray(record.subject_codes) && record.subject_codes.includes(checkbox.value);
    });

    syncAssignmentSectionForm("edit");
}

function setAssignmentViewValue(fieldName, value) {
    const field = document.querySelector(`[data-assignment-view-field="${fieldName}"]`);
    if (field) {
        field.value = value || "";
    }
}

function assignmentDepartmentName(code) {
    const record = (window.ADMIN_ASSIGNMENT_MANAGEMENT.departments || []).find((department) => department.code === code);
    return record ? record.name : code;
}

function assignmentCourseLabel(code) {
    const record = (window.ADMIN_ASSIGNMENT_MANAGEMENT.courses || []).find((course) => course.code === code);
    return record ? `${record.code} - ${record.name}` : code;
}

function assignmentFacultyName(id) {
    const record = (window.ADMIN_ASSIGNMENT_MANAGEMENT.faculty || []).find((faculty) => faculty.faculty_id === id);
    if (!record) {
        return id;
    }

    return [record.first_name, record.middle_name, record.last_name].filter(Boolean).join(" ");
}

function assignmentSubjectLabel(code) {
    const record = (window.ADMIN_ASSIGNMENT_MANAGEMENT.subjects || []).find((subject) => subject.code === code);
    return record ? `${record.code} - ${record.title}` : code;
}

function assignmentCourseScopeLabel(record) {
    if (record.is_general_education) {
        return "All Courses";
    }

    return Array.isArray(record.course_codes) && record.course_codes.length ? record.course_codes.join(", ") : "No Course Scope";
}

function findMatchingFacultyAssignment(enrollmentRecord, subjectCode) {
    return (window.ADMIN_ASSIGNMENT_MANAGEMENT.facultyAssignments || []).find((assignment) => {
        if (assignment.subject_code !== subjectCode) {
            return false;
        }

        if (assignment.semester !== enrollmentRecord.semester || assignment.academic_year !== enrollmentRecord.academic_year) {
            return false;
        }

        return assignment.is_general_education || (assignment.course_codes || []).includes(enrollmentRecord.course_code);
    }) || null;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}


