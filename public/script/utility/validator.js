import { Notification } from '../render/notification.js'

export function userValidationRules() {
    return {
        'firstName': {
            condition: (inputs) => !inputs.firstName || inputs.firstName.trim() === '' || inputs.firstName.length < 1 || inputs.firstName.length > 255,
            message: 'First name must be between 1 and 255 characters long.'
        },
        'firstNameFormat': {
            condition: (inputs) => inputs.firstName && !/^[a-zA-Z\s'-]{1,255}$/.test(inputs.firstName),
            message: 'First name contains invalid characters.'
        },

        'middleName': {
            condition: (inputs) => !inputs.middleName || inputs.middleName.trim() === '' || inputs.middleName.length < 1 || inputs.middleName.length > 255,
            message: 'Middle name must be between 1 and 255 characters long.'
        },
        'middleNameFormat': {
            condition: (inputs) => inputs.middleName && !/^[a-zA-Z\s'-]{1,255}$/.test(inputs.middleName),
            message: 'Middle name contains invalid characters.'
        },

        'lastName': {
            condition: (inputs) => !inputs.lastName || inputs.lastName.trim() === '' || inputs.lastName.length < 1 || inputs.lastName.length > 255,
            message: 'Last name must be between 1 and 255 characters long.'
        },
        'lastNameFormat': {
            condition: (inputs) => inputs.lastName && !/^[a-zA-Z\s'-]{1,255}$/.test(inputs.lastName),
            message: 'Last name contains invalid characters.'
        },

        'gender': {
            condition: (inputs) => !inputs.gender || inputs.gender.trim() === '' || !(['male', 'female', 'Male', 'Female'].includes(inputs.gender)),
            message: 'Please select a valid gender.'
        },

        // Date of Birth 
        'dateOfBirth': {
            condition: (inputs) => {
                const now = new Date()
                const dateOfBirth = inputs.dateOfBirth

                // Check if date is valid and in the past
                if (dateOfBirth >= now) return true

                // Calculate age
                let age = now.getFullYear() - dateOfBirth.getFullYear()
                const monthDiff = now.getMonth() - dateOfBirth.getMonth()
                if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < dateOfBirth.getDate())) {
                    age--
                }

                // Check if age is at least 18
                return age <= 18
            },
            message: 'You must be at least 18 years old to register.'
        },

        'jobTitles': {
            condition: (inputs) => !inputs.jobTitles || inputs.jobTitles.trim() === '' || inputs.jobTitles.length < 1 || inputs.jobTitles.length > 500,
            message: 'Job titles must be between 1 and 500 characters long.'
        },

        'contact': {
            condition: (inputs) => !inputs.contact || inputs.contact.trim() === '' || inputs.contact.length < 11 || inputs.contact.length > 15,
            message: 'Contact number must be between 11 and 15 characters long.'
        },

        'email': {
            // Email length validation
            condition: (inputs) => !inputs.email || inputs.email.trim().length < 3 || inputs.email.trim().length > 255,
            message: 'Email must be between 3 and 255 characters long.'
        }, 
        'emailFormat': {
            // Email format validation 
            condition: (inputs) => inputs.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(inputs.email),
            message: 'Invalid email address.'
        },

        'password': {
            // Password validation
            condition: (inputs) => !inputs.password || inputs.password.length < 8 || inputs.password.length > 128,
            message: 'Password must be between 8 and 128 characters long.'
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
                const startDate = new Date(inputs.startDateTime);
                return !inputs.startDateTime || isNaN(startDate.getTime());
            },
            message: 'Invalid start date and time.'
        },
        'startDateTime': {
            // Start date cannot be in the past
            condition: (inputs) => {
                const startDate = new Date(inputs.startDateTime);
                const currentDate = new Date();
                return startDate < currentDate;
            },
            message: 'Start date cannot be in the past.'
        },

        'completionDateTime': {
            // Completion date validation
            condition: (inputs) => {
                const completionDate = new Date(inputs.completionDateTime);
                return !inputs.completionDateTime || isNaN(completionDate.getTime());
            },
            message: 'Invalid completion date and time.'
        },
        'completionDateTime': {
            // Completion date must be after start date
            condition: (inputs) => {
                const startDate = new Date(inputs.startDateTime);
                const completionDate = new Date(inputs.completionDateTime);
                return completionDate <= startDate;
            },
            message: 'Completion date must be after the start date.'
        }
    }
}

/**
 * Validates inputs based on provided validation rules
 * @param {Object} inputs - Object containing form input values
 * @param {Array} validationRules - Array of validation rule objects
 * @param {Function} validationRules[].condition - Function that takes inputs and returns true if invalid
 * @param {string} validationRules[].message - Error message to display if condition is true
 * @return {boolean} - Returns true if all inputs are valid, otherwise false
 */
export function validateInputs(inputs = {}, validationRules) {
    // Check all validations
    for (const inputKey in inputs) {
        const validation = validationRules[inputKey];
        if (validation && validation.condition(inputs)) {
            Notification.error(validation.message, 3000);
            console.error('Validation failed:', validation.message);
            return false;
        }
    }
    return true;
}