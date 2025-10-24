import { errorListDialog } from '../render/error-list-dialog.js'

export function userValidationRules() {
    return {
        'firstName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.firstName || inputs.firstName.trim() === '' || inputs.firstName.length < 1 || inputs.firstName.length > 255) {
                    errors.push('First name must be between 1 and 255 characters long.')
                } else if (!/^[a-zA-Z\s'-]{1,255}$/.test(inputs.firstName)) {
                    errors.push('First name contains invalid characters.')
                }
                return errors
            }
        },

        'middleName': {
            condition: (inputs) => {
                const errors = []
                if (inputs.middleName && inputs.middleName.trim() !== '' && (inputs.middleName.length < 1 || inputs.middleName.length > 255)) {
                    errors.push('Middle name must be between 1 and 255 characters long.')
                } else if (!/^[a-zA-Z\s'-]{0,255}$/.test(inputs.middleName)) {
                    errors.push('Middle name contains invalid characters.')
                }
                return errors
            }
        },

        'lastName': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.lastName || inputs.lastName.trim() === '' || inputs.lastName.length < 1 || inputs.lastName.length > 255) {
                    errors.push('Last name must be between 1 and 255 characters long.')
                } else if (!/^[a-zA-Z\s'-]{1,255}$/.test(inputs.lastName)) {
                    errors.push('Last name contains invalid characters.')
                }
                return errors
            }
        },

        'bio': {
            condition: (inputs) => {
                const errors = []
                if (inputs.bio && (inputs.bio.trim().length < 10 || inputs.bio.trim().length > 500)) {
                    errors.push('Bio must be between 10 and 500 characters long.')
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

                if (!birthDate || birthDate >= now) {
                    errors.push('You must be at least 18 years old to register.')
                } else {
                    let age = now.getFullYear() - birthDate.getFullYear()
                    const monthDiff = now.getMonth() - birthDate.getMonth()
                    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birthDate.getDate())) {
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
                    errors.push('Job titles must be between 1 and 500 characters long.')
                }
                return errors
            }
        },

        'contactNumber': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.contactNumber || inputs.contactNumber.trim() === '' || inputs.contactNumber.length < 11 || inputs.contactNumber.length > 20) {
                    errors.push('Contact number must be between 11 and 20 characters long.')
                }
                return errors
            }
        },

        'email': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.email || inputs.email.trim().length < 3 || inputs.email.trim().length > 255) {
                    errors.push('Email must be between 3 and 255 characters long.')
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inputs.email)) {
                    errors.push('Invalid email address.')
                }
                return errors
            }
        },

        'password': {
            condition: (inputs) => {
                const errors = []
                if (!inputs.password || inputs.password.length < 8 || inputs.password.length > 128) {
                    errors.push('Password must be between 8 and 128 characters long.')
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
            // Name validation
            condition: (inputs) => !inputs.name || inputs.name.trim().length < 3 || inputs.name.trim().length > 255,
            message: 'Name must be between 3 and 255 characters long.'
        },

        'description': {
            // Description validation (optional)
            condition: (inputs) => inputs.description && (inputs.description.trim().length < 5 || inputs.description.trim().length > 500),
            message: 'Description must be between 5 and 500 characters long.'
        },

        'budget': {
            // Budget validation
            condition: (inputs) => !inputs.budget && (isNaN(inputs.budget) || inputs.budget < 0 || inputs.budget > 1000000),
            message: 'Budget must be a number between 0 and 1,000,000.'
        },

        'startDateTime': {
            // Priority validation
            condition: (inputs) => {
                const startDate = new Date(inputs.startDateTime)
                return !inputs.startDateTime || isNaN(startDate.getTime())
            },
            message: 'Invalid start date and time.'
        },
        'startDateTime': {
            // Start date cannot be in the past
            condition: (inputs) => {
                const startDate = new Date(inputs.startDateTime)
                const currentDate = new Date()
                return startDate < currentDate
            },
            message: 'Start date cannot be in the past.'
        },

        'completionDateTime': {
            // Completion date validation
            condition: (inputs) => {
                const completionDate = new Date(inputs.completionDateTime)
                return !inputs.completionDateTime || isNaN(completionDate.getTime())
            },
            message: 'Invalid completion date and time.'
        },
        'completionDateTime': {
            // Completion date must be after start date
            condition: (inputs) => {
                const startDate = new Date(inputs.startDateTime)
                const completionDate = new Date(inputs.completionDateTime)
                return completionDate <= startDate
            },
            message: 'Completion date must be after the start date.'
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
