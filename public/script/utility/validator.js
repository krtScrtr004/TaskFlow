import { isValidDate } from './utility.js'
import { errorListDialog } from '../render/error-list-dialog.js'

const LENGTH_VALIDATION = {
    'name':             { min: 1, max: 255 },
    'uri':              { min: 3, max: 255 },
    'contactNumber':    { min: 11, max: 20 },
    'password':         { min: 8, max: 255 },
    'longText':         { min: 10, max: 500 },
    'budget':           { min: 0, max: 999999999999 }
}

function isValidDateHelper(date) {
    if (!date || typeof date !== 'string') {
        throw new Error('A valid date string is required.')
    }

    const [year, month, day] = date.split('-').map(Number)
    return isValidDate(year, month, day)
}

export function userValidationRules() {
    return {
        'firstName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.firstName || inputs.firstName.trim() === '' || inputs.firstName.length < LENGTH_VALIDATION.name.min || inputs.firstName.length > LENGTH_VALIDATION.name.max) {
                    errors.push(`First name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
                } else if (!/^[a-zA-Z\s'-]{1,255}$/.test(inputs.firstName)) {
                    errors.push('First name contains invalid characters.')
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
                return errors
            }
        },

        'contactNumber': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.contactNumber || inputs.contactNumber.trim() === '' || inputs.contactNumber.length < LENGTH_VALIDATION.contactNumber.min || inputs.contactNumber.length > LENGTH_VALIDATION.contactNumber.max) {
                    errors.push(`Contact number must be between ${LENGTH_VALIDATION.contactNumber.min} and ${LENGTH_VALIDATION.contactNumber.max} characters long.`)
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
 * Default validation rules for work (project, phase, task) inputs
 * @returns {Array} Array of validation rule objects
 */
export function workValidationRules() {
    return {
        'name': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.name || inputs.name.trim().length < LENGTH_VALIDATION.name.min || inputs.name.trim().length > LENGTH_VALIDATION.name.max) {
                    errors.push(`Name must be between ${LENGTH_VALIDATION.name.min} and ${LENGTH_VALIDATION.name.max} characters long.`)
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

    if (errors.length > 0) {
        errorListDialog('Validation Errors', errors)
        return false
    }
    return true
}
