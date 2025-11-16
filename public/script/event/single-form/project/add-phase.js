import { validateInputs, workValidationRules } from '../../../utility/validator.js'
import { clonePhaseListCard } from './clone-phase-list-card.js'
import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { errorListDialog } from '../../../render/error-list-dialog.js'
import { normalizeDateFormat } from '../../../utility/utility.js'

const addPhaseModal = document.querySelector('#add_phase_modal')
const addPhaseForm = addPhaseModal.querySelector('#add_phase_form')
const addNewPhaseButton = addPhaseModal.querySelector('#add_new_phase_button')

/**
 * @param {Object} params 
 * @param {Function} params.action - Function to process form data before sending to backend. Receives form data object and should return modified form data object.
 * @param {boolean} params.allowDisable - Whether to allow disabling of fields based on phase status.
 */
export function addPhase(params = {}) {
    if (!addPhaseModal) {
        throw new Error('Add Phase Modal not found.')
    }

    if (!addNewPhaseButton) {
        throw new Error('Add New Phase Button not found.')
    }

    if (!addPhaseForm) {
        throw new Error('Add Phase Form not found.')
    }

    const submit = debounceAsync(e => submitForm(e, params), 300)

    addNewPhaseButton.addEventListener('click', e => submit(e))
    addPhaseForm.addEventListener('submit', e => submit(e))
}

/**
 * Handles the submission of the "Add Phase" form for a project.
 *
 * This function performs the following operations:
 * - Prevents the default form submission behavior.
 * - Validates the presence of the form element.
 * - Retrieves and trims values from the form fields: name, description, start date/time, and completion date/time.
 * - Validates required fields using custom validation rules.
 * - Checks for schedule conflicts and phase overlaps within the project.
 * - Shows error dialogs if validation fails.
 * - Calls a custom action if provided in params.
 * - Closes the modal dialog upon successful submission.
 * - Shows a success dialog and updates the phase list UI.
 * - Resets the form fields after successful submission.
 *
 * @param {Event} e The form submission event.
 * @param {Object} params Additional parameters for submission.
 * @param {Function} [params.action] Optional callback function to handle the form data object.
 * @param {boolean} [params.allowDisable=true] Whether to allow disabling the phase card after addition.
 *
 * @throws {Error} If the form element is not found or if an error occurs during submission.
 */
function submitForm(e, params) {
    e.preventDefault()

    try {
        Loader.patch(addNewPhaseButton.querySelector('.text-w-icon'))
        
        if (!addPhaseForm) {
            throw new Error('Add Phase Form not found.')
        }

        const nameInput = addPhaseForm.querySelector('#phase_name')
        const descriptionInput = addPhaseForm.querySelector('#phase_description')
        const startDateTimeInput = addPhaseForm.querySelector('#phase_start_datetime')
        const completionDateTimeInput = addPhaseForm.querySelector('#phase_completion_datetime')

        const name = nameInput.value.trim()
        const description = descriptionInput.value.trim()
        const startDateTime = normalizeDateFormat(startDateTimeInput.value)
        const completionDateTime = normalizeDateFormat(completionDateTimeInput.value)

        // Check required fields
        if (!validateInputs({
            name,
            description,
            startDateTime,
            completionDateTime
        }, workValidationRules())) return

        // Validate against project schedule and phase overlaps
        const phaseValidationErrors = validatePhaseSchedule(startDateTime, completionDateTime)
        if (phaseValidationErrors.length > 0) {
            errorListDialog('Phase Validation Failed', phaseValidationErrors)
            return
        }

        let body = {
            'name': name,
            'description': description,
            'startDateTime': startDateTime,
            'completionDateTime': completionDateTime,
        }
        if (params.action && typeof params.action === 'function') {
            params.action(body)
        }

        // Simulate a click on the close button to close the modal
        const closeButton = addPhaseModal.querySelector('#add_phase_close_button')
        closeButton?.click()

        Dialog.operationSuccess('Phase Added', 'New phase added successfully.')

        const allowDisable = params.allowDisable ?? true
        clonePhaseListCard(body, allowDisable)

        // Reset form fields
        addPhaseForm.reset()
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
    }
}

/**
 * Validates phase schedule against project timeline and existing phases.
 * Checks:
 * 1. Phase dates are within project start and completion dates
 * 2. Phase does not overlap with existing phases
 * 
 * @param {string} startDateTime - Phase start date/time (ISO format)
 * @param {string} completionDateTime - Phase completion date/time (ISO format)
 * @returns {Array<string>} Array of error messages (empty if valid)
 */
function validatePhaseSchedule(startDateTime, completionDateTime) {
    const errors = []
    
    // Get project schedule
    const projectStartInput = document.querySelector('#project_start_date')
    const projectEndInput = document.querySelector('#project_completion_date')
    
    if (!projectStartInput || !projectEndInput) {
        console.warn('Project date inputs not found. Skipping project schedule validation.')
    } else {
        const projectStart = new Date(projectStartInput.value)
        const projectEnd = new Date(projectEndInput.value)
        const phaseStart = new Date(startDateTime)
        const phaseEnd = new Date(completionDateTime)
        
        // Validate phase is within project timeline
        if (phaseStart < projectStart) {
            errors.push(`Phase start date (${formatDate(phaseStart)}) cannot be before project start date (${formatDate(projectStart)}).`)
        }
        
        if (phaseEnd > projectEnd) {
            errors.push(`Phase completion date (${formatDate(phaseEnd)}) cannot be after project completion date (${formatDate(projectEnd)}).`)
        }

        if (phaseEnd <= phaseStart) {
            errors.push(`Phase completion date (${formatDate(phaseEnd)}) must be after phase start date (${formatDate(phaseStart)}).`)
        }
        
        if (phaseStart < projectStart && phaseEnd > projectEnd) {
            errors.push(`Phase timeline (${formatDate(phaseStart)} - ${formatDate(phaseEnd)}) must be within project timeline (${formatDate(projectStart)} - ${formatDate(projectEnd)}).`)
        }
    }
    
    // Check for overlapping phases
    const existingPhases = getAllExistingPhases()
    const phaseStart = new Date(startDateTime)
    const phaseEnd = new Date(completionDateTime)
    
    for (const existingPhase of existingPhases) {
        const status = existingPhase.status?.toLowerCase() || 'pending'
        const existingStart = new Date(existingPhase.startDateTime)
        const existingEnd = new Date(existingPhase.completionDateTime)

        if (status === 'cancelled') {
            continue;
        }
        
        // Check if phases overlap
        // Overlap occurs if: (StartA <= EndB) AND (EndA >= StartB)
        if (phaseStart <= existingEnd && phaseEnd >= existingStart) {
            errors.push(
                `Phase overlaps with existing phase "${existingPhase.name}" ` +
                `(${formatDate(existingStart)} - ${formatDate(existingEnd)}). ` +
                `Please adjust the dates to avoid overlap.`
            )
        }
    }
    
    return errors
}

/**
 * Retrieves all existing phases from the phase list in the DOM.
 * 
 * @returns {Array<Object>} Array of phase objects with name, startDateTime, and completionDateTime
 */
function getAllExistingPhases() {
    const phases = []
    const phaseCards = document.querySelectorAll('.phase-details .phase')
    
    phaseCards.forEach(card => {
        const nameElement = card.querySelector('.phase-name')
        const startElement = card.querySelector('.phase-start-datetime')
        const endElement = card.querySelector('.phase-completion-datetime')
        const statusElement = card.querySelector('.status-badge > p')

        if (nameElement && startElement && endElement) {
            phases.push({
                name: nameElement.textContent.trim(),
                startDateTime: startElement.value.trim(),
                completionDateTime: endElement.value.trim(),
                status: statusElement 
                    ? statusElement.textContent.trim() 
                    : 'pending'
            })
        }
    })
    
    return phases
}

/**
 * Formats a Date object to a readable string (e.g., "Jan 15, 2025").
 * 
 * @param {Date} date - Date to format
 * @returns {string} Formatted date string
 */
function formatDate(date) {
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    })
}
