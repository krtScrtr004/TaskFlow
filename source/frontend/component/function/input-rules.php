<?php

/**
 * Returns an HTML fragment describing validation rules for a work name input.
 *
 * Renders a rules block (as an HTML string) that states:
 *  - The name must be between the NAME_MIN and NAME_MAX constants in length.
 *  - The name must not contain three or more consecutive special characters.
 *
 * The HTML is generated via output buffering and returned for inclusion in forms.
 *
 * @return bool|string HTML string containing the rules on success, or false on failure
 */
function workNameRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= NAME_MIN ?> and <?= NAME_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render HTML rules for a work description field.
 *
 * Produces an HTML fragment wrapped in <div class="rules"> containing a <ul> with
 * items that (1) state the allowed character length using the LONG_TEXT_MIN and
 * LONG_TEXT_MAX constants, and (2) warn against three or more consecutive special characters.
 *
 * The function captures the markup via output buffering and returns the buffered content.
 *
 * @return string|false HTML string containing the rules on success, or false on buffering failure
 */
function workDescriptionRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= LONG_TEXT_MIN ?> and <?= LONG_TEXT_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generates HTML markup describing validation rules for the worker count input.
 *
 * Returns a small HTML fragment containing an unordered list that explains the
 * value must be a positive integer and that the maximum allowed workers is taken
 * from the WORKER_COUNT_MAX constant. The markup is produced using output buffering.
 *
 * @return bool|string The generated HTML string on success, or false on failure
 *                     (function signature allows bool but it typically returns a string).
 * @see WORKER_COUNT_MAX
 */
function workWorkerCountRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a positive integer.</li>
            <li>Maximum number of workers is <?= WORKER_COUNT_MAX ?>.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
/**
 * Renders budget input validation rules as an HTML fragment.
 *
 * Produces a <div class="rules"> containing an unordered list of validation rules:
 *  - Must be a positive number.
 *  - Maximum budget is formatted from the BUDGET_MAX constant (two decimal places).
 *  - Allows up to two decimal places.
 * When $mode is 1 or 2, an additional rule is appended stating that the total budget must not
 * exceed the Project (mode=1) or Phase (mode=2) budget.
 *
 * The HTML is captured via output buffering and returned as a string.
 *
 * @param int $mode Mode controlling contextual rule inclusion:
 *      - 0: general rules only (default)
 *      - 1: include Project budget constraint
 *      - 2: include Phase budget constraint
 *
 * @return string|false HTML string containing the rules, or false on failure
 */

function workBudgetRules(int $mode = 0): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a positive number.</li>
            <li>Maximum budget is ₱<?= number_format(BUDGET_MAX, 2) ?>.</li>
            <li>Have up to two decimal places.</li>
            <?php if ($mode === 1 || $mode === 2): ?>
                <li>Total budget must not exceed the <?php echo $mode === 1 ? 'Project' : 'Phase' ?> budget.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders and returns an HTML fragment containing validation rules for the
 * "work contingency rate" input.
 *
 * The fragment includes a user-facing rule that the value must be a number
 * within the range defined by CONTINGENCY_RATE_MIN and CONTINGENCY_RATE_MAX.
 * Output is produced via output buffering and returned to the caller so it
 * can be embedded where needed.
 *
 * @return bool|string HTML string containing the rules on success, or false on buffering/failure
 */
function workContingencyRateRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a number between <?= CONTINGENCY_RATE_MIN ?> and <?= CONTINGENCY_RATE_MAX ?>.</li>
            <!-- <li>Represents the percentage of budget reserved for unexpected costs.</li> -->
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render the work budget note input rules as an HTML fragment.
 *
 * Outputs a small HTML block describing validation rules for the "work budget note"
 * input, including the allowed character length (using LONG_TEXT_MIN and LONG_TEXT_MAX)
 * and a restriction on consecutive special characters.
 *
 * The generated HTML is buffered and returned as a string (suitable for echoing
 * directly into templates). The returned fragment contains a <div class="rules">
 * with an unordered list describing the rules.
 *
 * @return bool|string HTML string containing the rules on success, or false on output buffering failure
 */
function workBudgetNoteRules(): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be between <?= LONG_TEXT_MIN ?> and <?= LONG_TEXT_MAX ?> characters.</li>
            <li>Must not contain three or more consecutive special characters.</li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Generates an HTML rules block describing valid start date/time constraints.
 *
 * This function captures and returns an HTML fragment containing a <div class="rules">
 * with an unordered list of validation rules appropriate to the given context mode.
 * The content varies by mode to reflect project-, phase-, or task-specific constraints.
 *
 * Modes:
 *  - 0 (project): basic rules (valid date, within allowed year range).
 *  - 1 (phase): includes project timeline constraint in addition to basic rules.
 *  - 2 (task): includes task timeline constraint.
 *
 * @param int $mode Context mode (0 = project, 1 = phase, 2 = task)
 *
 * @return string|bool HTML string containing the rules on success, or false on failure
 */
function workStartDateTimeRules(int $mode = 0): bool|string
{
    // Modes: 0 => project, 1 => phase, 2 => task
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a valid date.</li>
            <li>Must be between <?= YEAR_CURRENT ?> and <?= YEAR_MAX ?>.</li>
            <?php if ($mode === 1 || $mode === 2): ?>
                <li>Must be within the timeline of <?php echo $mode === 1 ? 'Project' : 'Task' ?>.</li>
            <?php endif; ?>
            <?php if ($mode === 1): ?>
                <li>Must not conflict with other Phases.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders work completion date/time validation rules as an HTML fragment.
 *
 * Builds and returns a string containing a <div class="rules"><ul>...</ul></div>
 * with list items describing the required date validity, the allowed year range
 * (using YEAR_CURRENT and YEAR_MAX), the requirement to be after the start date,
 * and additional mode-dependent rules about timeline scope and phase conflicts.
 *
 * @param int $mode Mode selector controlling additional rules:
 *      - 0: Default — basic rules (valid date, between YEAR_CURRENT and YEAR_MAX, after start date).
 *      - 1: Adds "Must be within the timeline of Project" and "Must not conflict with other Phases".
 *      - 2: Adds "Must be within the timeline of Task".
 *
 * @return string|bool HTML fragment containing the rules on success, or false on failure.
 */
function workCompletionDateTimeRules(int $mode = 0): bool|string
{
    ob_start();
    ?>
    <div class="rules">
        <ul>
            <li>Must be a valid date.</li>
            <li>Must be between <?= YEAR_CURRENT ?> and <?= YEAR_MAX ?>.</li>
            <li>Must be after the start date.</li>
            <?php if ($mode === 1 || $mode === 2): ?>
                <li>Must be within the timeline of <?php echo $mode === 1 ? 'Project' : 'Task' ?>.</li>
            <?php endif; ?>
            <?php if ($mode === 1): ?>
                <li>Must not conflict with other Phases.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}