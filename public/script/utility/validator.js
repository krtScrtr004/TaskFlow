import { getValidationConstraints, isValidDate } from './utility.js'
import { errorListDialog } from '../render/error-list-dialog.js'

const VALIDATION_CONSTANTS = await getValidationConstraints()

/**
 * Validates a date string in the format 'YYYY-MM-DD'.
 *
 * This helper function checks if the provided date string is valid by:
 * - Ensuring the input is a non-empty string
 * - Splitting the string into year, month, and day components
 * - Passing the components to the isValidDate function for validation
 *
 * @param {string} date Date string in the format 'YYYY-MM-DD'
 * @throws {Error} If the input is not a valid date string
 * @returns {boolean} True if the date is valid, false otherwise
 */
function isValidDateHelper(date) {
    if (!date || typeof date !== 'string') {
        throw new Error('A valid date string is required.')
    }

    const [year, month, day] = date.split('-').map(Number)
    return isValidDate(year, month, day)
}

/**
 * Checks if a string contains three or more consecutive special characters.
 *
 * The function iterates through the input string and counts consecutive occurrences
 * of special characters from the following set: $ % # & _ ! @ ' . * ( ) [ ] { } + -.
 * If three or more consecutive special characters are found, the function returns true.
 *
 * @param {string} str The string to be checked for consecutive special characters.
 * @returns {boolean} Returns true if the string contains three or more consecutive special characters; otherwise, false.
 */
function hasConsecutiveSpecialChars(str) {
    const MAX_COUNT = 3

    const specialChars = "$%#&_!@'.*()[]{}+-"
    for (let i = 0; i < str.length; i++) {
        let count = 0
        while (i < str.length && specialChars.includes(str[i])) {
            count++
            if (count >= MAX_COUNT) {
                return true
            }
            i++
        }
    }
    return false
}

/**
 * Returns validation rule definitions for user-related input fields.
 *
 * Each rule is an object with a `condition` function that accepts an `inputs` object
 * and returns an array of error messages (empty when valid). The rules reference
 * VALIDATION_CONSTANTS for length bounds and use helper functions/regexes such as
 * isValidDateHelper and hasConsecutiveSpecialChars where applicable.
 *
 * Fields and validation behavior:
 *  - firstName, lastName, fullName
 *      - Required; trimmed length must be within VALIDATION_CONSTANTS.NAME_MIN..NAME_MAX.
 *      - Allowed characters: letters, spaces, single quote ('), hyphen (-), dot (.).
 *      - Disallowed: three or more consecutive special characters (checked via hasConsecutiveSpecialChars).
 *  - middleName
 *      - Optional; when present (non-empty trimmed) must meet the same length and character constraints as name fields.
 *  - bio, message
 *      - Optional; when present trimmed length must be within VALIDATION_CONSTANTS.LONG_TEXT_MIN..VALIDATION_CONSTANTS.LONG_TEXT_MAX.
 *      - Disallowed: three or more consecutive special characters.
 *  - gender
 *      - Required; must be one of the allowed values (e.g. 'male', 'female' — case variations included).
 *  - birthDate
 *      - Required; must be a valid date (isValidDateHelper).
 *      - User must be at least 18 years old (age computed from current date versus parsed birth date).
 *  - jobTitles
 *      - Required; comma-separated list of titles.
 *      - Overall length expected between VALIDATION_CONSTANTS.LONG_TEXT_MIN..VALIDATION_CONSTANTS.LONG_TEXT_MAX.
 *      - Each title trimmed must be length 1..100.
 *      - Per-title allowed characters: alphanumeric, spaces, underscore, hyphen, apostrophe, backslash, forward slash (invalid other characters rejected).
 *  - contactNumber
 *      - Required; trimmed length must be within VALIDATION_CONSTANTS.CONTACT_NUMBER_MIN..VALIDATION_CONSTANTS.CONTACT_NUMBER_MAX.
 *      - Allowed characters: digits, plus, hyphen, spaces, parentheses. Validated with a length-aware regex.
 *  - email
 *      - Required; trimmed length must be within VALIDATION_CONSTANTS.URI_MIN..VALIDATION_CONSTANTS.URI_MAX.
 *      - Basic email format validated via regex (local@domain.tld).
 *  - password
 *      - Required; length must be within VALIDATION_CONSTANTS.PASSWORD_MIN..VALIDATION_CONSTANTS.PASSWORD_MAX.
 *      - Must contain at least one lowercase and one uppercase ASCII letter.
 *      - Allowed special characters: _ ! @ ' . - (other special characters are rejected).
 *  - role
 *      - Required; must be one of the allowed roles ('projectManager' or 'worker').
 *
 * @returns {Object<string, {condition: function(Object): string[]}>} An object mapping field names to validator objects.
 */
export function userValidationRules() {
    return {
        'firstName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.firstName || inputs.firstName.trim() === '' || inputs.firstName.length < VALIDATION_CONSTANTS.NAME_MIN || inputs.firstName.length > VALIDATION_CONSTANTS.NAME_MAX) {
                    errors.push(`First name must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters long.`)
                } else if (!/^[a-zA-Z\s'-.]{1,255}$/.test(inputs.firstName)) {
                    errors.push('First name contains invalid characters.')
                } else if (hasConsecutiveSpecialChars(inputs.firstName.trim())) {
                    errors.push('First name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'middleName': {
            condition: (inputs) => {
                const errors = []
                if (inputs.middleName && inputs.middleName.trim() !== '' && (inputs.middleName.length < VALIDATION_CONSTANTS.NAME_MIN || inputs.middleName.length > VALIDATION_CONSTANTS.NAME_MAX)) {
                    errors.push(`Middle name must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters long.`)
                } else if (inputs.middleName && inputs.middleName.trim() !== '' && !/^[a-zA-Z\s'-.]{0,255}$/.test(inputs.middleName)) {
                    errors.push('Middle name contains invalid characters.')
                }
                
                if (inputs.middleName && inputs.middleName.trim() !== '' && hasConsecutiveSpecialChars(inputs.middleName.trim())) {
                    errors.push('Middle name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'lastName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.lastName || inputs.lastName.trim() === '' || inputs.lastName.length < VALIDATION_CONSTANTS.NAME_MIN || inputs.lastName.length > VALIDATION_CONSTANTS.NAME_MAX) {
                    errors.push(`Last name must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters long.`)
                } else if (!/^[a-zA-Z\s'-.]{1,255}$/.test(inputs.lastName)) {
                    errors.push('Last name contains invalid characters.')
                } else if (hasConsecutiveSpecialChars(inputs.lastName.trim())) {
                    errors.push('Last name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'fullName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.fullName || inputs.fullName.trim() === '' || inputs.fullName.length < VALIDATION_CONSTANTS.NAME_MIN || inputs.fullName.length > VALIDATION_CONSTANTS.NAME_MAX) {
                    errors.push(`Full name must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters long.`)
                } else if (!/^[a-zA-Z\s'-.]{1,255}$/.test(inputs.fullName)) {
                    errors.push('Full name contains invalid characters.')
                } else if (hasConsecutiveSpecialChars(inputs.fullName.trim())) {
                    errors.push('Full name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'bio': {
            condition: (inputs) => {
                const errors = []
                if (inputs.bio && (inputs.bio.trim().length < VALIDATION_CONSTANTS.LONG_TEXT_MIN || inputs.bio.trim().length > VALIDATION_CONSTANTS.LONG_TEXT_MAX)) {
                    errors.push(`Bio must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters long.`)
                }

                if (inputs.bio && hasConsecutiveSpecialChars(inputs.bio.trim())) {
                    errors.push('Bio contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'message': {
            condition: (inputs) => {
                const errors = []
                if (inputs.message && (inputs.message.trim().length < VALIDATION_CONSTANTS.LONG_TEXT_MIN || inputs.message.trim().length > VALIDATION_CONSTANTS.LONG_TEXT_MAX)) {
                    errors.push(`Message must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters long.`)
                }

                if (inputs.message && hasConsecutiveSpecialChars(inputs.message.trim())) {
                    errors.push('Message contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'gender': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.gender || inputs.gender.trim() === '' || !(['male', 'female', 'Male', 'Female'].includes(inputs.gender))) {
                    errors.push('Please select a valid gender.')
                }
                return errors
            }
        },

        'birthDate': {
            condition: (inputs) => {
                const errors = []
                const now = new Date()
                const birthDate = inputs.birthDate

                if (!birthDate || !isValidDateHelper(inputs.birthDate)) {
                    errors.push('Invalid birth date.')
                } else {
                    const parseBirthDate = new Date(birthDate.trim())
                    let age = now.getFullYear() - parseBirthDate.getFullYear()
                    const monthDiff = now.getMonth() - parseBirthDate.getMonth()
                    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < parseBirthDate.getDate())) {
                        age--
                    }

                    if (age < 18) {
                        errors.push('You must be at least 18 years old to register.')
                    }
                }
                return errors
            }
        },

        'jobTitles': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.jobTitles || inputs.jobTitles.trim() === '' || inputs.jobTitles.length < 1 || inputs.jobTitles.length > 500) {
                    errors.push(`Job titles must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters long.`)
                }

                if (inputs.jobTitles) {
                    const titles = inputs.jobTitles.split(',').map(title => title.trim())
                    for (const title of titles) {
                        if (title.length < 1 || title.length > 100) {
                            errors.push('Each job title must be between 1 and 100 characters long.')
                            break
                        }
                        
                        if (/[^a-zA-Z0-9\s\-_'\-\\\/]/.test(title)) {
                            errors.push(`Job title "${title}" contains invalid characters.`)
                            break
                        }
                    }
                }
                return errors
            }
        },

        'contactNumber': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.contactNumber || inputs.contactNumber.trim() === '' || inputs.contactNumber.length < VALIDATION_CONSTANTS.CONTACT_NUMBER_MIN || inputs.contactNumber.length > VALIDATION_CONSTANTS.CONTACT_NUMBER_MAX) {
                    errors.push(`Contact number must be between ${VALIDATION_CONSTANTS.CONTACT_NUMBER_MIN} and ${VALIDATION_CONSTANTS.CONTACT_NUMBER_MAX} characters long.`)
                } else if (!/^[0-9+\-\s()]{${VALIDATION_CONSTANTS.CONTACT_NUMBER_MIN},${VALIDATION_CONSTANTS.CONTACT_NUMBER_MAX}}$/.test(inputs.contactNumber)) {
                    errors.push('Contact number contains invalid characters.')
                }
                return errors
            }
        },

        'email': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.email || inputs.email.trim().length < VALIDATION_CONSTANTS.URI_MIN || inputs.email.trim().length > VALIDATION_CONSTANTS.URI_MAX) {
                    errors.push(`Email must be between ${VALIDATION_CONSTANTS.URI_MIN} and ${VALIDATION_CONSTANTS.URI_MAX} characters long.`)
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inputs.email)) {
                    errors.push('Invalid email address.')
                }
                return errors
            }
        },

        'password': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.password || inputs.password.length < VALIDATION_CONSTANTS.PASSWORD_MIN || inputs.password.length > VALIDATION_CONSTANTS.PASSWORD_MAX) {
                    errors.push(`Password must be between ${VALIDATION_CONSTANTS.PASSWORD_MIN} and ${VALIDATION_CONSTANTS.PASSWORD_MAX} characters long.`)
                }
                if (inputs.password && !/[a-z]/.test(inputs.password)) {
                    errors.push('Password must contain at least one lowercase letter.')
                }
                if (inputs.password && !/[A-Z]/.test(inputs.password)) {
                    errors.push('Password must contain at least one uppercase letter.')
                }
                if (inputs.password && /[^a-zA-Z0-9_!@'\.\-]/.test(inputs.password)) {
                    errors.push('Password contains invalid special characters. Only _ ! @ \' . - are allowed.')
                }
                return errors
            }
        },

        'role': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.role || !(['projectManager', 'worker'].includes(inputs.role.trim()))) {
                    errors.push('Please select a valid role.')
                }
                return errors
            }
        }
    }
}

/**
 * Returns validation rules for a "work" entity used by forms or APIs.
 *
 * Each rule is an object with a `condition` function that receives an `inputs`
 * object and returns an array of error messages (empty array if no errors).
 *
 * Field rules included:
 *  - name
 *      - Required (non-empty) and trimmed length must be between
 *        VALIDATION_CONSTANTS.NAME_MIN and VALIDATION_CONSTANTS.NAME_MAX.
 *      - Rejects names containing three or more consecutive special characters
 *        (uses hasConsecutiveSpecialChars).
 *  - description
 *      - Optional; when present trimmed length must be between
 *        VALIDATION_CONSTANTS.LONG_TEXT_MIN and VALIDATION_CONSTANTS.LONG_TEXT_MAX.
 *      - Rejects three or more consecutive special characters.
 *  - maxWWorkers
 *      - Parsed as integer; must be a number > VALIDATION_CONSTANTS.WORKER_COUNT_MIN
 *        and <= VALIDATION_CONSTANTS.WORKER_COUNT_MAX.
 *  - budget
 *      - Parsed as Number; must be between VALIDATION_CONSTANTS.BUDGET_MIN and
 *        VALIDATION_CONSTANTS.BUDGET_MAX (inclusive).
 *  - contingencyRate
 *      - Parsed as Number; must be between
 *        VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN and VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX.
 *  - budgetNote
 *      - Optional; when present trimmed length must be between
 *        VALIDATION_CONSTANTS.LONG_TEXT_MIN and VALIDATION_CONSTANTS.LONG_TEXT_MAX.
 *      - Rejects three or more consecutive special characters.
 *  - startDateTime
 *      - Required; must be a valid date (isValidDateHelper).
 *      - Date (date-only comparison) must not be in the past.
 *  - completionDateTime
 *      - Required; must be a valid date (isValidDateHelper).
 *      - When startDateTime is provided and valid, completion must be strictly
 *        after the start date/time.
 *
 * Notes:
 *  - Validation messages are returned as human-readable strings.
 *  - This function references globals: VALIDATION_CONSTANTS, hasConsecutiveSpecialChars, isValidDateHelper.
 *
 * @returns {Object.<string, {condition: function(Object): string[]}>} Map of field names to validation rule objects.
 */
export function workValidationRules() {
    return {
        'name': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.name || inputs.name.trim().length < VALIDATION_CONSTANTS.NAME_MIN || inputs.name.trim().length > VALIDATION_CONSTANTS.NAME_MAX) {
                    errors.push(`Name must be between ${VALIDATION_CONSTANTS.NAME_MIN} and ${VALIDATION_CONSTANTS.NAME_MAX} characters long.`)
                } 
                
                if (inputs.name && hasConsecutiveSpecialChars(inputs.name.trim())) {
                    errors.push('Name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'description': {
            condition: (inputs) => {
                const errors = []
                if (inputs.description && (inputs.description.trim().length < VALIDATION_CONSTANTS.LONG_TEXT_MIN || inputs.description.trim().length > VALIDATION_CONSTANTS.LONG_TEXT_MAX)) {
                    errors.push(`Description must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters long.`)
                }
                
                if (inputs.description && hasConsecutiveSpecialChars(inputs.description.trim())) {
                    errors.push('Description contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'maxWWorkers': {
            condition: (inputs) => {
                const errors = []
                const value = parseInt(inputs.maxWWorkers, 10)
                if (!value || isNaN(value) || value <= VALIDATION_CONSTANTS.WORKER_COUNT_MIN || value > VALIDATION_CONSTANTS.WORKER_COUNT_MAX) {
                    errors.push(`Max number of workers must be a number between ${VALIDATION_CONSTANTS.WORKER_COUNT_MIN} and ${VALIDATION_CONSTANTS.WORKER_COUNT_MAX}.`)
                }
                return errors
            }
        },

        'budget': {
            condition: (inputs) => {
                const errors = []
                const value = inputs.budget                
                if (!value || isNaN(Number(value)) || Number(value) < VALIDATION_CONSTANTS.BUDGET_MIN || Number(value) > VALIDATION_CONSTANTS.BUDGET_MAX) {
                    errors.push(`Budget must be a number between ${VALIDATION_CONSTANTS.BUDGET_MIN} and ${VALIDATION_CONSTANTS.BUDGET_MAX}.`)
                }
                return errors
            }
        },

        'contingencyRate': {
            condition: (inputs) => {
                const errors = []
                const value = inputs.contingencyRate
                if (!value || isNaN(Number(value)) || Number(value) < VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN || Number(value) > VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX) {
                    errors.push(`Contingency rate must be a number between ${VALIDATION_CONSTANTS.CONTINGENCY_RATE_MIN} and ${VALIDATION_CONSTANTS.CONTINGENCY_RATE_MAX}.`)
                }
                return errors
            }
        },

        'budgetNote': {
            condition: (inputs) => {
                const errors = []
                if (inputs.budgetNote && (inputs.budgetNote.trim().length < VALIDATION_CONSTANTS.LONG_TEXT_MIN || inputs.budgetNote.trim().length > VALIDATION_CONSTANTS.LONG_TEXT_MAX)) {
                    errors.push(`Budget note must be between ${VALIDATION_CONSTANTS.LONG_TEXT_MIN} and ${VALIDATION_CONSTANTS.LONG_TEXT_MAX} characters long.`)
                }
                
                if (inputs.budgetNote && hasConsecutiveSpecialChars(inputs.budgetNote.trim())) {
                    errors.push('Budget note contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'startDateTime': {
            condition: (inputs) => {
                const errors = []
                const val = inputs.startDateTime
                if (!val) {
                    errors.push('Invalid start date and time.')
                    return errors
                }
                if (!isValidDateHelper(val)) {
                    errors.push('Invalid start date and time.')
                    return errors
                }
                const startDate = new Date(val)
                const now = new Date()
                // Compare only the date part (ignore time)
                const startDateOnly = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate())
                const nowDateOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate())
                if (startDateOnly < nowDateOnly) {
                    errors.push('Start date cannot be in the past.')
                }
                return errors
            }
        },

        'completionDateTime': {
            condition: (inputs) => {
                const errors = []
                const val = inputs.completionDateTime
                if (!val) {
                    errors.push('Invalid completion date and time.')
                    return errors
                }
                if (!isValidDateHelper(val)) {
                    errors.push('Invalid completion date and time.')
                    return errors
                }
                const completionDate = new Date(val)
                const startVal = inputs.startDateTime
                if (startVal) {
                    const startDate = new Date(startVal)
                    if (!isNaN(startDate.getTime()) && completionDate <= startDate) {
                        errors.push('Completion date must be after the start date.')
                    }
                }
                return errors
            }
        }
    }
}

/**
 * Validates inputs based on provided validation rules
 * @param {Object} inputs - Object containing form input values
 * @param {Object} validationRules - Object of validation rule objects
 * @param {Function} validationRules[].condition - Function that takes inputs and returns array of error messages
 * @return {boolean} - Returns true if all inputs are valid, otherwise false
 */
export function validateInputs(inputs = {}, validationRules) {
    const errors = []

    // Check validations for provided inputs only
    for (const inputKey in inputs) {
        const validation = validationRules[inputKey]
        if (validation) {
            const fieldErrors = validation.condition(inputs)
            if (fieldErrors && fieldErrors.length > 0) {
                fieldErrors.forEach(errorMessage => {
                    console.error('Validation failed:', errorMessage)
                    errors.push(errorMessage)
                })
            }
        }
    }

    // If there are validation errors, show them in a dialog
    if (errors.length > 0) {
        errorListDialog('Validation Errors', errors)
        return false
    }
    return true
}
