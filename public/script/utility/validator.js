import { isValidDate } from './utility.js'
import { errorListDialog } from '../render/error-list-dialog.js'

const LENGTH_VALIDATION = {
    'name':             { min: 1, max: 50 },
    'uri':              { min: 3, max: 255 },
    'contactNumber':    { min: 11, max: 20 },
    'password':         { min: 8, max: 255 },
    'longText':         { min: 10, max: 1000 },
    'budget':           { min: 0, max: 999999999999 }
}

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
 * Returns an object containing validation rules for user input fields.
 *
 * Each field has a `condition` function that takes an `inputs` object and returns an array of error messages
 * if the input does not meet the validation criteria. The rules cover the following fields:
 * - firstName: Required, length between LENGTH_VALIDATION.name.min and LENGTH_VALIDATION.name.max, valid characters, no three or more consecutive special characters.
 * - middleName: Optional, length between LENGTH_VALIDATION.name.min and LENGTH_VALIDATION.name.max if present, valid characters, no three or more consecutive special characters.
 * - lastName: Required, length between LENGTH_VALIDATION.name.min and LENGTH_VALIDATION.name.max, valid characters, no three or more consecutive special characters.
 * - bio: Optional, length between LENGTH_VALIDATION.longText.min and LENGTH_VALIDATION.longText.max if present, no three or more consecutive special characters.
 * - gender: Required, must be one of ['male', 'female', 'Male', 'Female'].
 * - birthDate: Required, must be a valid date, user must be at least 18 years old.
 * - jobTitles: Required, length between LENGTH_VALIDATION.longText.min and LENGTH_VALIDATION.longText.max, each title between 1 and 20 characters, valid characters.
 * - contactNumber: Required, length between LENGTH_VALIDATION.contactNumber.min and LENGTH_VALIDATION.contactNumber.max, valid characters.
 * - email: Required, length between LENGTH_VALIDATION.uri.min and LENGTH_VALIDATION.uri.max, must be a valid email format.
 * - password: Required, length between LENGTH_VALIDATION.password.min and LENGTH_VALIDATION.password.max, must contain at least one lowercase and one uppercase letter, only allowed special characters (_ ! @ ' . -).
 * - role: Required, must be one of ['projectManager', 'worker'].
 *
 * @function
 * @returns {Object} Validation rules for user input fields, where each key is a field name and each value is an object with a `condition(inputs)` function returning an array of error messages.
 */
export function userValidationRules() {
    return {
        'firstName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.firstName || inputs.firstName.trim() === '' || inputs.firstName.length < LENGTH_VALIDATION.name.min || inputs.firstName.length > LENGTH_VALIDATION.name.max) {
                    errors.push(`First name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
                } else if (!/^[a-zA-Z\s'-]{1,255}$/.test(inputs.firstName)) {
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
                if (inputs.middleName && inputs.middleName.trim() !== '' && (inputs.middleName.length < LENGTH_VALIDATION.name.min || inputs.middleName.length > LENGTH_VALIDATION.name.max)) {
                    errors.push(`Middle name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
                } else if (!/^[a-zA-Z\s'-]{0,255}$/.test(inputs.middleName)) {
                    errors.push('Middle name contains invalid characters.')
                }
                
                if (inputs.middleName && hasConsecutiveSpecialChars(inputs.middleName.trim())) {
                    errors.push('Middle name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'lastName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.lastName || inputs.lastName.trim() === '' || inputs.lastName.length < LENGTH_VALIDATION.name.min || inputs.lastName.length > LENGTH_VALIDATION.name.max) {
                    errors.push(`Last name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
                } else if (!/^[a-zA-Z\s'-]{1,255}$/.test(inputs.lastName)) {
                    errors.push('Last name contains invalid characters.')
                } else if (hasConsecutiveSpecialChars(inputs.lastName.trim())) {
                    errors.push('Last name contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'bio': {
            condition: (inputs) => {
                const errors = []
                if (inputs.bio && (inputs.bio.trim().length < LENGTH_VALIDATION.longText.min || inputs.bio.trim().length > LENGTH_VALIDATION.longText.max)) {
                    errors.push(`Bio must be between ${LENGTH_VALIDATION.longText.min} and ${LENGTH_VALIDATION.longText.max} characters long.`)
                }

                if (inputs.bio && hasConsecutiveSpecialChars(inputs.bio.trim())) {
                    errors.push('Bio contains three or more consecutive special characters.')
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
                    errors.push(`Job titles must be between ${LENGTH_VALIDATION.longText.min} and ${LENGTH_VALIDATION.longText.max} characters long.`)
                }

                if (inputs.jobTitles) {
                    const titles = inputs.jobTitles.split(',').map(title => title.trim())
                    for (const title of titles) {
                        if (title.length < 1 || title.length > 20) {
                            errors.push('Each job title must be between 1 and 20 characters long.')
                            break
                        }
                        
                        if (/[^a-zA-Z0-9\s'\-]/.test(title)) {
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
                if (!inputs.contactNumber || inputs.contactNumber.trim() === '' || inputs.contactNumber.length < LENGTH_VALIDATION.contactNumber.min || inputs.contactNumber.length > LENGTH_VALIDATION.contactNumber.max) {
                    errors.push(`Contact number must be between ${LENGTH_VALIDATION.contactNumber.min} and ${LENGTH_VALIDATION.contactNumber.max} characters long.`)
                } else if (!/^[0-9+\-\s()]{11,20}$/.test(inputs.contactNumber)) {
                    errors.push('Contact number contains invalid characters.')
                }
                return errors
            }
        },

        'email': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.email || inputs.email.trim().length < LENGTH_VALIDATION.uri.min || inputs.email.trim().length > LENGTH_VALIDATION.uri.max) {
                    errors.push(`Email must be between ${LENGTH_VALIDATION.uri.min} and ${LENGTH_VALIDATION.uri.max} characters long.`)
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inputs.email)) {
                    errors.push('Invalid email address.')
                }
                return errors
            }
        },

        'password': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.password || inputs.password.length < LENGTH_VALIDATION.password.min || inputs.password.length > LENGTH_VALIDATION.password.max) {
                    errors.push(`Password must be between ${LENGTH_VALIDATION.password.min} and ${LENGTH_VALIDATION.password.max} characters long.`)
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
 * Returns a set of validation rules for work-related form inputs.
 *
 * Each rule contains a `condition` function that validates the corresponding input field and returns an array of error messages if validation fails.
 * The validation covers the following fields:
 * - name: Checks length constraints and consecutive special characters.
 * - description: Checks length constraints and consecutive special characters.
 * - budget: Ensures the value is a number within a specified range.
 * - startDateTime: Validates date format and ensures the date is not in the past.
 * - completionDateTime: Validates date format and ensures the date is after the start date.
 *
 * @function
 * @returns {Object} Validation rules object with the following keys:
 *      - name: {
 *            condition: function(inputs: Object): string[]
 *              Validates the 'name' field. Returns error messages if:
 *                  - Name is missing or not within allowed length.
 *                  - Name contains three or more consecutive special characters.
 *        }
 *      - description: {
 *            condition: function(inputs: Object): string[]
 *              Validates the 'description' field. Returns error messages if:
 *                  - Description is not within allowed length.
 *                  - Description contains three or more consecutive special characters.
 *        }
 *      - budget: {
 *            condition: function(inputs: Object): string[]
 *              Validates the 'budget' field. Returns error messages if:
 *                  - Budget is missing, not a number, or outside allowed range.
 *        }
 *      - startDateTime: {
 *            condition: function(inputs: Object): string[]
 *              Validates the 'startDateTime' field. Returns error messages if:
 *                  - Date is missing, invalid, or in the past.
 *        }
 *      - completionDateTime: {
 *            condition: function(inputs: Object): string[]
 *              Validates the 'completionDateTime' field. Returns error messages if:
 *                  - Date is missing, invalid, or not after the start date.
 *        }
 */
export function workValidationRules() {
    return {
        'name': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.name || inputs.name.trim().length < LENGTH_VALIDATION.name.min || inputs.name.trim().length > LENGTH_VALIDATION.name.max) {
                    errors.push(`Name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
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
                if (inputs.description && (inputs.description.trim().length < LENGTH_VALIDATION.longText.min || inputs.description.trim().length > LENGTH_VALIDATION.longText.max)) {
                    errors.push(`Description must be between ${LENGTH_VALIDATION.longText.min} and ${LENGTH_VALIDATION.longText.max} characters long.`)
                }
                
                if (inputs.description && hasConsecutiveSpecialChars(inputs.description.trim())) {
                    errors.push('Description contains three or more consecutive special characters.')
                }
                return errors
            }
        },

        'budget': {
            condition: (inputs) => {
                const errors = []
                const value = inputs.budget
                if (value === undefined || value === null || value === '') {
                    errors.push(`Budget must be a number between ${LENGTH_VALIDATION.budget.min} and ${LENGTH_VALIDATION.budget.max}.`)
                } else if (isNaN(Number(value)) || Number(value) < LENGTH_VALIDATION.budget.min || Number(value) > LENGTH_VALIDATION.budget.max) {
                    errors.push(`Budget must be a number between ${LENGTH_VALIDATION.budget.min} and ${LENGTH_VALIDATION.budget.max}.`)
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
