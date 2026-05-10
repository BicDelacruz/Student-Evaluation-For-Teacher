<?php
if (!function_exists("year_level_label")) {
    function year_level_label(int $year_level): string
    {
        if ($year_level === 1) {
            return "1st Year";
        }

        if ($year_level === 2) {
            return "2nd Year";
        }

        if ($year_level === 3) {
            return "3rd Year";
        }

        return $year_level . "th Year";
    }
}

$section_search = clean_string($_GET["q"] ?? "");
$filter_department_id = (int) ($_GET["department_id"] ?? 0);
$filter_course_id = (int) ($_GET["course_id"] ?? 0);
$filter_year_level = (int) ($_GET["year_level"] ?? 0);

$params = [];
$where = "WHERE 1 = 1";

if ($section_search !== "") {
    $where .= "
        AND (
            sec.section_name LIKE :section_search
            OR c.course_code LIKE :course_code_search
            OR c.course_name LIKE :course_name_search
            OR d.department_name LIKE :department_search
            OR ay.academic_year_name LIKE :academic_year_search
            OR t.term_name LIKE :term_search
        )
    ";

    $params["section_search"] = "%" . $section_search . "%";
    $params["course_code_search"] = "%" . $section_search . "%";
    $params["course_name_search"] = "%" . $section_search . "%";
    $params["department_search"] = "%" . $section_search . "%";
    $params["academic_year_search"] = "%" . $section_search . "%";
    $params["term_search"] = "%" . $section_search . "%";
}

if ($filter_department_id > 0) {
    $where .= " AND c.department_id = :department_id";
    $params["department_id"] = $filter_department_id;
}

if ($filter_course_id > 0) {
    $where .= " AND sec.course_id = :course_id";
    $params["course_id"] = $filter_course_id;
}

if ($filter_year_level > 0) {
    $where .= " AND sec.year_level = :year_level";
    $params["year_level"] = $filter_year_level;
}

$section_statement = $pdo->prepare("
    SELECT
    sec.*,
    c.course_code,
    c.course_name,
    c.department_id,
    d.department_name,
    t.term_name,
    ay.academic_year_id AS connected_academic_year_id,
    COALESCE(ay.academic_year_name, 'No academic year assigned') AS academic_year_name,
    COUNT(DISTINCT sse.student_id) AS student_count
    FROM `section` sec
    INNER JOIN course c ON c.course_id = sec.course_id
    INNER JOIN department d ON d.department_id = c.department_id
    INNER JOIN term t ON t.term_id = sec.term_id
    LEFT JOIN academic_year ay ON ay.academic_year_id = sec.academic_year_id
    LEFT JOIN student_section_enrollment sse
        ON sse.section_id = sec.section_id
        AND sse.enrollment_status = 'Active'
    $where
    GROUP BY sec.section_id
    ORDER BY c.course_code, sec.year_level, sec.section_name
");

$section_statement->execute($params);
$sections = $section_statement->fetchAll();

$section_count = count($sections);

$selected_section = null;

if ($modal_type === "section" && $modal_id > 0) {
    $statement = $pdo->prepare("
        SELECT
        sec.*,
        c.course_code,
        c.course_name,
        c.department_id,
        d.department_name,
        t.term_name,
        ay.academic_year_id AS connected_academic_year_id,
        COALESCE(ay.academic_year_name, 'No academic year assigned') AS academic_year_name,
        COUNT(DISTINCT sse.student_id) AS student_count
        FROM `section` sec
        INNER JOIN course c ON c.course_id = sec.course_id
        INNER JOIN department d ON d.department_id = c.department_id
        INNER JOIN term t ON t.term_id = sec.term_id
        LEFT JOIN academic_year ay ON ay.academic_year_id = sec.academic_year_id
        LEFT JOIN student_section_enrollment sse
            ON sse.section_id = sec.section_id
            AND sse.enrollment_status = 'Active'
        WHERE sec.section_id = :section_id
        GROUP BY sec.section_id
        LIMIT 1
    ");

    $statement->execute(["section_id" => $modal_id]);
    $selected_section = $statement->fetch();
}
?>

<div class="panel-top">
    <h2>Sections (<?php echo $section_count; ?>)</h2>

    <a class="primary-button" href="academic_structure.php?panel=sections&type=section&action=add">
        <?php echo icon_svg("plus"); ?> Add Section
    </a>
</div>

<form class="filter-bar sections-filter auto-filter-form" method="GET" action="academic_structure.php">
    <input type="hidden" name="panel" value="sections">

    <div class="search-box">
        <?php echo icon_svg("search"); ?>
        <input type="text" name="q" value="<?php echo e($section_search); ?>" placeholder="Search by section name, program, or year level...">
    </div>

    <select name="department_id">
        <option value="0">All Colleges</option>
        <?php foreach ($departments_all as $department): ?>
            <option value="<?php echo (int) $department["department_id"]; ?>" <?php echo $filter_department_id === (int) $department["department_id"] ? "selected" : ""; ?>>
                <?php echo e($department["department_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="course_id">
        <option value="0">All Programs</option>
        <?php foreach ($courses_all as $course): ?>
            <option value="<?php echo (int) $course["course_id"]; ?>" <?php echo $filter_course_id === (int) $course["course_id"] ? "selected" : ""; ?>>
                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="year_level">
        <option value="0">All Year Levels</option>
        <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?php echo $i; ?>" <?php echo $filter_year_level === $i ? "selected" : ""; ?>>
                <?php echo year_level_label($i); ?>
            </option>
        <?php endfor; ?>
    </select>

    <a href="academic_structure.php?panel=sections" class="clear-filter">Clear</a>
</form>

<?php if (count($sections) === 0): ?>
    <div class="empty-card">No sections found matching your search criteria.</div>
<?php endif; ?>

<div class="section-grid">
    <?php foreach ($sections as $section): ?>
        <article class="section-card">
            <div class="card-actions">
                <a class="view" href="academic_structure.php?panel=sections&type=section&action=view&id=<?php echo (int) $section["section_id"]; ?>">
                    <?php echo icon_svg("eye"); ?>
                </a>

                <a class="edit" href="academic_structure.php?panel=sections&type=section&action=edit&id=<?php echo (int) $section["section_id"]; ?>">
                    <?php echo icon_svg("edit"); ?>
                </a>

                <form method="POST" action="academic_structure.php?panel=sections" onsubmit="return confirm('Delete this section record?');">
                    <input type="hidden" name="form_action" value="delete_section">
                    <input type="hidden" name="section_id" value="<?php echo (int) $section["section_id"]; ?>">
                    <button class="delete" type="submit"><?php echo icon_svg("trash"); ?></button>
                </form>
            </div>

            <h3><?php echo e($section["section_name"]); ?></h3>
            <p><strong>Students:</strong> <?php echo (int) $section["student_count"]; ?></p>
            <p><strong>College:</strong> <?php echo e($section["department_name"]); ?></p>
            <p><strong>Program:</strong> <?php echo e($section["course_code"] . " - " . $section["course_name"]); ?></p>
            <p><strong>Year Level:</strong> <?php echo year_level_label((int) $section["year_level"]); ?></p>
            <p><strong>Academic Year:</strong> <?php echo e($section["academic_year_name"]); ?></p>
            <p><strong>Semester:</strong> <?php echo e($section["term_name"]); ?></p>
        </article>
    <?php endforeach; ?>
</div>

<?php if ($modal_type === "section" && $modal_action === "add"): ?>
    <div class="modal-overlay">
        <div class="modal-box medium tall">
            <h2>Add New Section</h2>
            <p class="modal-help">Add up to 5 sections at once. Fill in at least one section.</p>

            <form method="POST" action="academic_structure.php?panel=sections" id="addSectionForm">
                <input type="hidden" name="form_action" value="add_sections">

                <div class="section-validation-box" id="addSectionValidationBox" hidden></div>

                <div class="modal-scroll">
                    <?php
                    $section_placeholders = [
                        1 => "e.g., BSIT 2A",
                        2 => "e.g., BSIT 2B",
                        3 => "e.g., BSIT 2C",
                        4 => "e.g., BSIT 2D",
                        5 => "e.g., BSIT 2E"
                    ];
                    ?>

                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="section-input-group">
                            <strong>Section <?php echo $i; ?></strong>
                            <label>Section Name</label>

                            <input
                                type="text"
                                name="section_name_<?php echo $i; ?>"
                                class="section-name-input"
                                data-section-number="<?php echo $i; ?>"
                                placeholder="<?php echo e($section_placeholders[$i]); ?>"
                            >

                            <small class="section-inline-error" hidden></small>
                        </div>
                    <?php endfor; ?>

                    <div class="modal-divider"></div>

                    <label>Program</label>
                    <select name="course_id" id="add_section_course_id" required>
                        <option value="" data-course-code="">Select Program</option>
                        <?php foreach ($courses_all as $course): ?>
                            <option
                                value="<?php echo (int) $course["course_id"]; ?>"
                                data-course-code="<?php echo e($course["course_code"]); ?>"
                            >
                                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="two-column">
                        <div>
                            <label>Year Level</label>
                            <select name="year_level" id="add_section_year_level" required>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>">
                                        <?php echo year_level_label($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label>Semester</label>
                            <select name="term_name" required>
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                    </div>

                    <label>Academic Year</label>
                    <select name="academic_year_id" required>
                        <option value="">Select Academic Year</option>
                        <?php foreach ($academic_years_all as $year): ?>
                            <option value="<?php echo (int) $year["academic_year_id"]; ?>">
                                <?php echo e($year["academic_year_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Maximum Students</label>
                    <input type="number" name="maximum_student_count" value="50" min="1" max="500">
                </div>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=sections" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button" id="addSectionsButton">Add Sections</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "section" && $modal_action === "view" && $selected_section): ?>
    <div class="modal-overlay">
        <div class="modal-box medium section-details-modal">
            <h2>Section Details</h2>

            <label>Section Name</label>
            <input type="text" value="<?php echo e($selected_section["section_name"]); ?>" readonly>

            <label>College</label>
            <input type="text" value="<?php echo e($selected_section["department_name"]); ?>" readonly>

            <div class="two-column section-course-row">
                <div>
                    <label>Program</label>
                    <input
                        type="text"
                        value="<?php echo e($selected_section["course_code"] . " - " . $selected_section["course_name"]); ?>"
                        title="<?php echo e($selected_section["course_code"] . " - " . $selected_section["course_name"]); ?>"
                        readonly
                    >
                </div>

                <div>
                    <label>Year Level</label>
                    <input type="text" value="<?php echo year_level_label((int) $selected_section["year_level"]); ?>" readonly>
                </div>
            </div>

            <div class="two-column">
                <div>
                    <label>Academic Year</label>
                    <input type="text" value="<?php echo e($selected_section["academic_year_name"]); ?>" readonly>
                </div>

                <div>
                    <label>Semester</label>
                    <input type="text" value="<?php echo e($selected_section["term_name"]); ?>" readonly>
                </div>
            </div>

            <label>Total Students</label>
            <input type="text" value="<?php echo (int) $selected_section["student_count"]; ?>" readonly>

            <div class="modal-divider"></div>

            <div class="modal-actions">
                <a href="academic_structure.php?panel=sections" class="secondary-button">Cancel</a>
                <a href="academic_structure.php?panel=sections&type=section&action=edit&id=<?php echo (int) $selected_section["section_id"]; ?>" class="dark-button">Edit Information</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($modal_type === "section" && $modal_action === "edit" && $selected_section): ?>
    <div class="modal-overlay">
        <div class="modal-box medium tall">
            <h2>Edit Section</h2>

            <form method="POST" action="academic_structure.php?panel=sections" id="editSectionForm">
                <input type="hidden" name="form_action" value="update_section">
                <input type="hidden" name="section_id" value="<?php echo (int) $selected_section["section_id"]; ?>">

                <div class="section-validation-box" id="editSectionValidationBox" hidden></div>

                <div class="modal-scroll">
                    <label>Section Name</label>
                    <input
                        type="text"
                        name="section_name"
                        value="<?php echo e($selected_section["section_name"]); ?>"
                        class="section-name-input"
                        data-section-number="1"
                        required
                    >
                    <small class="section-inline-error" hidden></small>

                    <label>Program</label>
                    <select name="course_id" id="edit_section_course_id" required>
                        <?php foreach ($courses_all as $course): ?>
                            <option
                                value="<?php echo (int) $course["course_id"]; ?>"
                                data-course-code="<?php echo e($course["course_code"]); ?>"
                                <?php echo (int) $selected_section["course_id"] === (int) $course["course_id"] ? "selected" : ""; ?>
                            >
                                <?php echo e($course["course_code"] . " - " . $course["course_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="two-column">
                        <div>
                            <label>Year Level</label>
                            <select name="year_level" id="edit_section_year_level" required>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (int) $selected_section["year_level"] === $i ? "selected" : ""; ?>>
                                        <?php echo year_level_label($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div>
                            <label>Semester</label>
                            <select name="term_name" required>
                                <?php foreach (["First Semester", "Second Semester", "Summer"] as $semester): ?>
                                    <option value="<?php echo e($semester); ?>" <?php echo $selected_section["term_name"] === $semester ? "selected" : ""; ?>>
                                        <?php echo e($semester); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <label>Academic Year</label>
                    <select name="academic_year_id" required>
                        <?php foreach ($academic_years_all as $year): ?>
                            <option value="<?php echo (int) $year["academic_year_id"]; ?>" <?php echo (int) $selected_section["academic_year_id"] === (int) $year["academic_year_id"] ? "selected" : ""; ?>>
                                <?php echo e($year["academic_year_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Maximum Students</label>
                    <input type="number" name="maximum_student_count" value="<?php echo (int) $selected_section["maximum_student_count"]; ?>" min="1" max="500">

                    <label>Status</label>
                    <select name="section_status" required>
                        <?php foreach (["Active", "Inactive", "Closed"] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selected_section["section_status"] === $status ? "selected" : ""; ?>>
                                <?php echo e($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <a href="academic_structure.php?panel=sections" class="secondary-button">Cancel</a>
                    <button type="submit" class="dark-button" id="updateSectionButton">Update Section</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<style>
    .section-validation-box {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        color: #be123c;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 16px;
        font-size: 14px;
        line-height: 1.45;
    }

    .section-validation-box strong {
        display: block;
        margin-bottom: 6px;
    }

    .section-validation-box ul {
        margin-left: 18px;
    }

    .section-inline-error {
        display: block;
        color: #be123c;
        font-size: 12px;
        margin-top: 6px;
        font-weight: 700;
    }

    .section-name-input.section-name-invalid {
        border-color: #f43f5e !important;
        background: #fff1f2 !important;
        box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.10) !important;
    }

    .dark-button:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }
</style>

<script>
    function setupSectionNameValidation(settings) {
        const form = document.getElementById(settings.formId);
        const courseSelect = document.getElementById(settings.courseSelectId);
        const yearSelect = document.getElementById(settings.yearSelectId);
        const messageBox = document.getElementById(settings.messageBoxId);
        const submitButton = document.getElementById(settings.submitButtonId);

        if (!form || !courseSelect || !yearSelect || !messageBox || !submitButton) {
            return;
        }

        const inputs = form.querySelectorAll(".section-name-input");
        const sectionLetters = ["A", "B", "C", "D", "E"];

        function escapeRegExp(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        }

        function compactText(value) {
            return value
                .trim()
                .toUpperCase()
                .replace(/[\s\-_]+/g, "");
        }

        function getSelectedCourseCode() {
            const selectedOption = courseSelect.options[courseSelect.selectedIndex];

            if (!selectedOption) {
                return "";
            }

            return selectedOption.getAttribute("data-course-code") || "";
        }

        function getExampleText(input) {
            const courseCode = getSelectedCourseCode() || "BSIT";
            const yearLevel = yearSelect.value || "2";
            const sectionNumber = parseInt(input.getAttribute("data-section-number") || "1", 10);
            const letter = sectionLetters[sectionNumber - 1] || "A";

            return courseCode.toUpperCase() + " " + yearLevel + letter;
        }

        function updatePlaceholders() {
            inputs.forEach(function (input) {
                input.placeholder = "e.g., " + getExampleText(input);
            });
        }

        function isValidSectionName(sectionName, courseCode, yearLevel) {
            const compactSectionName = compactText(sectionName);
            const compactCourseCode = compactText(courseCode);

            if (compactSectionName === "" || compactCourseCode === "" || yearLevel === "") {
                return false;
            }

            const pattern = new RegExp("^" + escapeRegExp(compactCourseCode) + yearLevel + "[A-Z]+$");

            return pattern.test(compactSectionName);
        }

        function setInputError(input, message) {
            const inlineError = input.parentElement.querySelector(".section-inline-error");

            input.classList.add("section-name-invalid");

            if (inlineError) {
                inlineError.textContent = message;
                inlineError.hidden = false;
            }
        }

        function clearInputError(input) {
            const inlineError = input.parentElement.querySelector(".section-inline-error");

            input.classList.remove("section-name-invalid");

            if (inlineError) {
                inlineError.textContent = "";
                inlineError.hidden = true;
            }
        }

        function renderMessages(messages) {
            messageBox.replaceChildren();

            if (messages.length === 0) {
                messageBox.hidden = true;
                return;
            }

            const title = document.createElement("strong");
            title.textContent = "Please fix the section name format.";
            messageBox.appendChild(title);

            const list = document.createElement("ul");

            messages.forEach(function (message) {
                const item = document.createElement("li");
                item.textContent = message;
                list.appendChild(item);
            });

            messageBox.appendChild(list);
            messageBox.hidden = false;
        }

        function validateSectionNames(requireOneSection) {
            const courseCode = getSelectedCourseCode();
            const yearLevel = yearSelect.value;
            const messages = [];
            const seenSections = new Map();
            let hasAtLeastOneSection = false;

            inputs.forEach(function (input) {
                clearInputError(input);

                const sectionName = input.value.trim();

                if (sectionName === "") {
                    return;
                }

                hasAtLeastOneSection = true;

                const sectionNumber = input.getAttribute("data-section-number") || "";
                const expectedExample = getExampleText(input);
                const compactSectionName = compactText(sectionName);

                if (courseCode === "") {
                    const message = "Section " + sectionNumber + " needs a selected program first.";
                    setInputError(input, message);
                    messages.push(message);
                    return;
                }

                if (yearLevel === "") {
                    const message = "Section " + sectionNumber + " needs a selected year level first.";
                    setInputError(input, message);
                    messages.push(message);
                    return;
                }

                if (!isValidSectionName(sectionName, courseCode, yearLevel)) {
                    const message =
                        "Section " + sectionNumber +
                        " must match the selected program and year level. Use this format: " +
                        expectedExample + ".";

                    setInputError(input, message);
                    messages.push(message);
                    return;
                }

                if (seenSections.has(compactSectionName)) {
                    const message =
                        "Section " + sectionNumber +
                        " is duplicated. Each section name must be unique.";

                    setInputError(input, message);
                    messages.push(message);
                    return;
                }

                seenSections.set(compactSectionName, true);
            });

            if (requireOneSection && !hasAtLeastOneSection) {
                messages.push("Please enter at least one section name.");
            }

            renderMessages(messages);

            submitButton.disabled = messages.length > 0;

            return messages.length === 0;
        }

        inputs.forEach(function (input) {
            input.addEventListener("input", function () {
                validateSectionNames(false);
            });

            input.addEventListener("blur", function () {
                validateSectionNames(false);
            });
        });

        courseSelect.addEventListener("change", function () {
            updatePlaceholders();
            validateSectionNames(false);
        });

        yearSelect.addEventListener("change", function () {
            updatePlaceholders();
            validateSectionNames(false);
        });

        form.addEventListener("submit", function (event) {
            const isValid = validateSectionNames(true);

            if (!isValid) {
                event.preventDefault();

                const firstInvalidInput = form.querySelector(".section-name-invalid");

                if (firstInvalidInput) {
                    firstInvalidInput.focus();
                }
            }
        });

        updatePlaceholders();
        validateSectionNames(false);
    }

    setupSectionNameValidation({
        formId: "addSectionForm",
        courseSelectId: "add_section_course_id",
        yearSelectId: "add_section_year_level",
        messageBoxId: "addSectionValidationBox",
        submitButtonId: "addSectionsButton"
    });

    setupSectionNameValidation({
        formId: "editSectionForm",
        courseSelectId: "edit_section_course_id",
        yearSelectId: "edit_section_year_level",
        messageBoxId: "editSectionValidationBox",
        submitButtonId: "updateSectionButton"
    });
</script>