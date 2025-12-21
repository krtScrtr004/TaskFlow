import { compareDates, getValidationConstraints } from './utility.js'

const VALIDATION_CONSTANTS = await getValidationConstraints()

// Regex to detect three or more consecutive special characters
const CONSECUTIVE_SPECIAL_CHARS_REGEX = /[^a-zA-Z0-9\s]{3,}/

/**
 * Validates a work name input.
 * @param {string} value - The name value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateName(value) {
    const trimmed = (value ?? '').trim()
    const length = trimmed.length

    return {
        lengthValid: length >= VALIDATION_CONSTANTS.NAME_MIN && length <= VALIDATION_CONSTANTS.NAME_MAX,
        noConsecutiveSpecialChars: !CONSECUTIVE_SPECIAL_CHARS_REGEX.test(trimmed),
    }
}

/**
 * Validates a work description input.
 * @param {string} value - The description value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateDescription(value) {
    const trimmed = (value ?? '').trim()
    const length = trimmed.length

    // Description is optional, so empty is valid
    if (length === 0) {
        return {
            lengthValid: true,
            noConsecutiveSpecialChars: true,
        }
    }

    return {
        lengthValid: length >= VALIDATION_CONSTANTS.LONG_TEXT_MIN && length <= VALIDATION_CONSTANTS.LONG_TEXT_MAX,
        noConsecutiveSpecialChars: !CONSECUTIVE_SPECIAL_CHARS_REGEX.test(trimmed),
    }
}

/**
 * Validates a worker count input.
 * @param {string|number} value - The worker count value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateWorkerCount(value) {
    const num = parseInt(value, 10)
    const isPositiveInteger = Number.isInteger(num) && num > 0

    return {
        isPositiveInteger: isPositiveInteger,
        withinMax: isPositiveInteger && num <= VALIDATION_CONSTANTS.WORKER_COUNT_MAX,
    }
}

/**
 * Validates a budget input.
 * @param {string|number} value - The budget value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateBudget(value) {
    const strValue = String(value ?? '').trim()
    const num = parseFloat(strValue)
    const isPositive = !isNaN(num) && num >= 0

    // Check for up to 2 decimal places using regex
    const decimalPlacesRegex = /^\d+(\.\d{1,2})?$/
    const hasValidDecimals = decimalPlacesRegex.test(strValue) || /^\d+$/.test(strValue)

    return {
        isPositiveNumber: isPositive,
        withinMax: isPositive && num <= VALIDATION_CONSTANTS.BUDGET_MAX,
        validDecimalPlaces: hasValidDecimals,
    }
}

/**
 * Validates a contingency rate input.
 * @param {string|number} value - The contingency rate value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateContingencyRate(value) {
    const num = parseFloat(value)
    const isValid = !isNaN(num)

    return {
        withinRange: isValid &&
            num >= VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN &&
            num <= VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX,
    }
}

/**
 * Validates a budget note input.
 * @param {string} value - The budget note value to validate.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateBudgetNote(value) {
    const trimmed = (value ?? '').trim()
    const length = trimmed.length

    // Budget note is optional, so empty is valid
    if (length === 0) {
        return {
            lengthValid: true,
            noConsecutiveSpecialChars: true,
        }
    }

    return {
        lengthValid: length >= VALIDATION_CONSTANTS.LONG_TEXT_MIN && length <= VALIDATION_CONSTANTS.LONG_TEXT_MAX,
        noConsecutiveSpecialChars: !CONSECUTIVE_SPECIAL_CHARS_REGEX.test(trimmed),
    }
}

/**
 * Validate a proposed start date/time for a Task or Phase against basic rules.
 *
 * This function parses the provided value into a Date and runs several checks:
 *  - verifies the value is a valid date,
 *  - ensures the year falls within configured validation bounds (`VALIDATION_CONSTANTS.YEAR_CURRENT` .. `VALIDATION_CONSTANTS.YEAR_MAX`),
 *  - optionally checks that the date lies within provided project/phase bounds using `compareDates` (boundStart and boundCompletion),
 *  - optionally checks for conflicts with other phases in `phasesSchedule` (skips the phase with `ownId`).
 *
 * @param {Date|string|number} value The start date/time to validate (Date instance, ISO string, or timestamp).
 * @param {Object} [options] Optional validation flags and context.
 * @param {boolean} [options.isBounded=false] Whether to enforce boundStart/boundCompletion checks.
 * @param {Date|string|number|null} [options.boundStart=null] Lower bound to compare against (checked via compareDates: value <= boundStart).
 * @param {Date|string|number|null} [options.boundCompletion=null] Upper bound to compare against (checked via compareDates: value >= boundCompletion).
 * @param {boolean} [options.hasConflict=false] Whether to check for conflicts against other phases.
 * @param {string|number|null} [options.ownId=null] Identifier of the current phase/task to be ignored when checking conflicts.
 * @param {Map<{start: Date|string|number, completion: Date|string|number}>} [options.phasesSchedule=[]] Array or iterable of other phases to check for overlap.
 *
 * @returns {Object} Validation result with the following boolean properties:
 *      - isValidDate: true if `value` could be parsed into a valid Date.
 *      - yearWithinRange: true if the parsed year is between `VALIDATION_CONSTANTS.YEAR_CURRENT` and `VALIDATION_CONSTANTS.YEAR_MAX`.
 *      - withinBounds: true if either not bounded or the value satisfies provided boundStart/boundCompletion comparisons.
 *      - notConflicted: true if either not checking conflicts or the value does not fall within any other phase's start..completion range.
 */
export function validateStartDateTime(value,
    {
        isBounded = false,
        boundStart = null,
        boundCompletion = null,

        hasConflict = false,
        ownId = null,
        phasesSchedule = []
    } = {}
) {
    const dateObj = value instanceof Date ? value : new Date(value)
    const isValidDate = !isNaN(dateObj.getTime())
    if (!isValidDate) {
        return {
            isValidDate: false,
            yearWithinRange: false,
            withinBounds: false,
        }
    }

    let yearValid = false
    let withinBounds = true // Default to true if not bounded
    let notConflicted = true // Default to true if not checking conflicts

    const year = dateObj.getFullYear()
    yearValid = year >= VALIDATION_CONSTANTS.YEAR_CURRENT && year <= VALIDATION_CONSTANTS.YEAR_MAX

    // Check for Project / Phase timeline bounds (Phase / Task only)
    if (isBounded) {
        if (boundStart) {
            withinBounds = withinBounds && compareDates(value, boundStart) <= 0
        }
        if (boundCompletion) {
            withinBounds = withinBounds && compareDates(value, boundCompletion) >= 0
        }
    }

    // Check for conflicts with other Phases (Phase only)
    if (hasConflict) {
        for (const [id, phase] of phasesSchedule.entries()) {
            if (ownId === id) {
                continue
            }

            if (compareDates(value, phase.start) <= 0 && compareDates(value, phase.completion) >= 0) {
                notConflicted = false
                break
            }
        }
    }


    return {
        isValidDate: isValidDate,
        yearWithinRange: yearValid,
        withinBounds: withinBounds,
        notConflicted: notConflicted
    }
}

/**
 * Validate a proposed completion date/time against several business rules.
 *
 * Checks include: valid/parsible date, year within configured range, optionally being after a given
 * start date, optional bounded range checks, and optional conflict detection against other phases.
 * Uses VALIDATION_CONSTANTS.YEAR_CURRENT and VALIDATION_CONSTANTS.YEAR_MAX for year checks and
 * compareDates(...) for all date comparisons.
 *
 * @param {Date|string|number} value Completion date/time to validate (Date instance or parseable value)
 * @param {Date|string|number|null} [startDateTime] Optional start date/time; when valid, completion must be after it
 * @param {Object} [options] Optional configuration object
 * @param {boolean} [options.isBounded=false] When true, enforces boundStart/boundCompletion constraints
 * @param {Date|string|number|null} [options.boundStart=null] If provided and isBounded, completion must be on or before this value
 * @param {Date|string|number|null} [options.boundCompletion=null] If provided and isBounded, completion must be on or after this value
 * @param {boolean} [options.hasConflict=false] When true, checks phasesSchedule for overlapping phases
 * @param {string|number|null} [options.ownId=null] Identifier of the current phase to ignore when checking conflicts
 * @param {Iterable|Map|Array} [options.phasesSchedule=[]] Iterable whose entries are [id, phase], where each phase has `start` and `completion`
 *
 * Notes:
 * - If the provided value is not a valid date, the function returns isValidDate: false and all other flags false.
 * - Date comparisons are performed via compareDates(value, other).
 * - Conflict detection skips the entry whose id equals ownId and marks notConflicted = false if the completion
 *   falls between a phase's completion and start (inclusive per the comparison logic).
 *
 * @returns {Object} Validation result with boolean flags:
 *      - isValidDate: whether the provided value is a valid Date
 *      - yearWithinRange: whether the year is between VALIDATION_CONSTANTS.YEAR_CURRENT and VALIDATION_CONSTANTS.YEAR_MAX
 *      - afterStartDate: whether completion is after startDateTime (true if no valid start provided)
 *      - withinBounds: whether completion respects provided bounds when isBounded is true (true if unbounded)
 *      - notConflicted: true if no conflicting phase was found (true if hasConflict is false)
 */
export function validateCompletionDateTime(value, startDateTime,
    {
        isBounded = false,
        boundStart = null,
        boundCompletion = null,

        hasConflict = false,
        ownId = null,
        phasesSchedule = []
    } = {}
) {
    const dateObj = value instanceof Date ? value : new Date(value)
    const isValidDate = !isNaN(dateObj.getTime())
    if (!isValidDate) {
        return {
            isValidDate: false,
            yearWithinRange: false,
            afterStartDate: false,
            withinBounds: false,
            notConflicted: false,
        }
    }

    let yearValid = false
    let afterStartDate = true // Default to true if no start date provided
    let withinBounds = true // Default to true if not bounded
    let notConflicted = true // Default to true if not checking conflicts

    const year = dateObj.getFullYear()
    yearValid = year >= VALIDATION_CONSTANTS.YEAR_CURRENT && year <= VALIDATION_CONSTANTS.YEAR_MAX

    if (startDateTime) {
        const startObj = startDateTime instanceof Date ? startDateTime : new Date(startDateTime)
        if (!isNaN(startObj.getTime())) {
            afterStartDate = dateObj > startObj
        }
    }

    if (isBounded) {
        if (boundStart) {
            withinBounds = withinBounds && compareDates(value, boundStart) <= 0
        }
        if (boundCompletion) {
            withinBounds = withinBounds && compareDates(value, boundCompletion) >= 0
        }
    }

    if (hasConflict) {
        for (const [id, phase] of phasesSchedule.entries()) {
            if (ownId === id) {
                continue
            }

            if (compareDates(value, phase.start) <= 0 && compareDates(value, phase.completion) >= 0) {
                notConflicted = false
                break
            }
        }
    }

    return {
        isValidDate: isValidDate,
        yearWithinRange: yearValid,
        afterStartDate: afterStartDate,
        withinBounds: withinBounds,
        notConflicted: notConflicted
    }
}

/**
 * Applies validation results to rule list items.
 * 
 * @param {HTMLElement} rulesContainer - The container element with the rules (e.g., .rules ul).
 * @param {Object} validationResults - Object with rule keys and boolean results.
 * @param {Object} ruleMapping - Maps rule keys to the index of the <li> element (0-based).
 * @param {string} validClass - CSS class to apply when rule passes (default: 'valid').
 * @param {string} invalidClass - CSS class to apply when rule fails (default: 'invalid').
 * 
 * @return {boolean} - Returns true if all rules passed, false if any failed.
 * 
 * @example
 * const results = validateName(input.value)
 * applyValidationToRules(rulesContainer, results, {
 *     lengthValid: 0,           // First <li>
 *     noConsecutiveSpecialChars: 1  // Second <li>
 * })
 */
export function applyValidationToRules(
    rulesContainer,
    validationResults,
    ruleMapping,
    validClass = 'valid',
    invalidClass = 'invalid'
) {
    if (!rulesContainer) {
        return
    }
    let hasInvalid = false

    const listItems = rulesContainer.querySelectorAll('li')

    for (const [ruleKey, liIndex] of Object.entries(ruleMapping)) {
        const li = listItems[liIndex]
        if (!li) {
            continue
        }

        const passed = validationResults[ruleKey]
        if (!passed) {
            hasInvalid = true
        }

        li.classList.remove(validClass, invalidClass)
        li.classList.add(passed ? validClass : invalidClass)
    }
    return !hasInvalid
}

// Pre-defined rule mappings for convenience
export const RULE_MAPPINGS = {
    workName: {
        lengthValid: 0,
        noConsecutiveSpecialChars: 1,
    },
    workDescription: {
        lengthValid: 0,
        noConsecutiveSpecialChars: 1,
    },
    workerCount: {
        isPositiveInteger: 0,
        withinMax: 1,
    },
    workBudget: {
        isPositiveNumber: 0,
        withinMax: 1,
        validDecimalPlaces: 2,
    },
    contingencyRate: {
        withinRange: 0,
    },
    budgetNote: {
        lengthValid: 0,
        noConsecutiveSpecialChars: 1,
    },
    startDateTime: {
        isValidDate: 0,
        yearWithinRange: 1,
        withinBounds: 2,
        notConflicted: 3,
    },
    completionDateTime: {
        isValidDate: 0,
        yearWithinRange: 1,
        afterStartDate: 2,
        withinBounds: 3,
        notConflicted: 4,
    },
}