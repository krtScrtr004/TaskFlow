import { Notification } from '../../../../render/notification.js'

/**
 * Validates the input fields for adding a new phase.
 * @param {Object} inputs - Object containing form input values
 * @param {string} inputs.name - Phase name
 * @param {string} inputs.description - Phase description
 * @param {string} inputs.startDateTime - Phase start date and time (ISO string)
 * @param {string} inputs.completionDateTime - Phase completion date and time (ISO string)
 * @return {boolean} - Returns true if all inputs are valid, otherwise false
 */
export function validateInputs(inputs = {}) {
    const {
        name,
        description,
        startDateTime,
        completionDateTime,
    } = inputs

    // Validation rules
    const validations = [
        {
            condition: !name || name.trim().length < 3 || name.trim().length > 255,
            message: 'Task name must be between 3 and 255 characters long.'
        },
        {
            condition: description && (description.trim().length < 5 || description.trim().length > 500),
            message: 'Task description must be between 5 and 500 characters long.'
        }
    ]

    // Date validations
    const startDate = new Date(startDateTime)
    const completionDate = new Date(completionDateTime)
    const currentDate = new Date()

    validations.push(
        {
            condition: !startDateTime || isNaN(startDate.getTime()),
            message: 'Invalid start date and time.'
        },
        {
            condition: startDate.getDate() < currentDate.getDate(),
            message: 'Start date cannot be in the past.'
        },
        {
            condition: !completionDateTime || isNaN(completionDate.getTime()),
            message: 'Invalid completion date and time.'
        },
        {
            condition: completionDate.getDate() <= startDate.getDate(),
            message: 'Completion date must be after the start date.'
        }
    )

    // Check all validations
    for (const validation of validations) {
        if (validation.condition) {
            Notification.error(validation.message, 3000)
            console.error('Validation failed:', validation.message)
            return false
        }
    }
    return true
}