import { Notification } from '../render/notification.js'

/**
 * Default validation rules for phase inputs
 * @returns {Array} Array of validation rule objects
 */
export function defaultValidationRules() {
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
                return startDate.getDate() < currentDate.getDate();
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
                return completionDate.getDate() <= startDate.getDate();
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
export function validateInputs(inputs = {}, validationRules = defaultValidationRules()) {
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