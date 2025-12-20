import { getValidationConstraints } from './utility.js'
/**
 *  Input Validators
 * 
 * JavaScript validation functions and regex patterns that correspond to PHP validation rules.
 * Each validator returns an object with individual rule results (true = passed, false = failed).
 * Use these to highlight rules in red (failed) or green (passed).
 */

export const VALIDATION_CONSTANTS = await getValidationConstraints()

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
 * Validates a start date/time input.
 * @param {string|Date} value - The date value to validate (YYYY-MM-DD string or Date object).
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateStartDateTime(value) {
    const dateObj = value instanceof Date ? value : new Date(value)
    const isValidDate = !isNaN(dateObj.getTime())
    
    let yearValid = false
    if (isValidDate) {
        const year = dateObj.getFullYear()
        yearValid = year >= VALIDATION_CONSTANTS.YEAR_MIN && year <= VALIDATION_CONSTANTS.YEAR_MAX
    }

    return {
        isValidDate: isValidDate,
        yearWithinRange: yearValid,
    }
}

/**
 * Validates a completion date/time input.
 * @param {string|Date} value - The completion date value to validate.
 * @param {string|Date|null} startDateTime - Optional start date to compare against.
 * @returns {Object} Object with rule keys and boolean results.
 */
export function validateCompletionDateTime(value, startDateTime = null) {
    const dateObj = value instanceof Date ? value : new Date(value)
    const isValidDate = !isNaN(dateObj.getTime())
    
    let yearValid = false
    let afterStartDate = true // Default to true if no start date provided

    if (isValidDate) {
        const year = dateObj.getFullYear()
        yearValid = year >= VALIDATION_CONSTANTS.YEAR_MIN && year <= VALIDATION_CONSTANTS.YEAR_MAX

        if (startDateTime) {
            const startObj = startDateTime instanceof Date ? startDateTime : new Date(startDateTime)
            if (!isNaN(startObj.getTime())) {
                afterStartDate = dateObj > startObj
            }
        }
    }

    return {
        isValidDate: isValidDate,
        yearWithinRange: yearValid,
        afterStartDate: afterStartDate,
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
    if (!rulesContainer) return

    const listItems = rulesContainer.querySelectorAll('li')

    for (const [ruleKey, liIndex] of Object.entries(ruleMapping)) {
        const li = listItems[liIndex]
        if (!li) continue

        const passed = validationResults[ruleKey]

        li.classList.remove(validClass, invalidClass)
        li.classList.add(passed ? validClass : invalidClass)
    }
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
        // Note: second rule is informational, not a validation
    },
    budgetNote: {
        lengthValid: 0,
        noConsecutiveSpecialChars: 1,
    },
    startDateTime: {
        isValidDate: 0,
        yearWithinRange: 1,
    },
    completionDateTime: {
        isValidDate: 0,
        yearWithinRange: 1,
        afterStartDate: 2,
    },
}

// EX:
// import { 
//     validateName, 
//     applyValidationToRules, 
//     RULE_MAPPINGS 
// } from '../utility/work-input-validators.js'

// const nameInput = document.querySelector('#name')
// const rulesContainer = document.querySelector('.rules ul')

// nameInput.addEventListener('input', () => {
//     const results = validateName(nameInput.value)
//     applyValidationToRules(rulesContainer, results, RULE_MAPPINGS.workName)
// })