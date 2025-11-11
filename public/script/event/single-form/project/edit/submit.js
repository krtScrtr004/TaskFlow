import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Loader } from '../../../../render/loader.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { phaseToCancel } from './cancel-phase.js'
import { validateInputs, workValidationRules } from '../../../../utility/validator.js'
import { handleException } from '../../../../utility/handle-exception.js'
import { normalizeDateFormat } from '../../../../utility/utility.js'

let isLoading = false

const phaseToAdd = [] // Tracks new phases to be added
const phaseToEdit = [] // Tracks existing phases that have been modified

const editableProjectDetailsForm = document.querySelector('.edit-project #editable_project_details')
if (!editableProjectDetailsForm) {
    console.error('Editable Project Details form not found.')
    Dialog.somethingWentWrong()
}

editableProjectDetailsForm?.addEventListener('submit', e => debounceAsync(submitForm(e), 300))

const saveProjectInfoButton = editableProjectDetailsForm?.querySelector('#save_project_info_button')
if (!saveProjectInfoButton) {
    console.error('Save Project Info button not found.')
    Dialog.somethingWentWrong()
}

saveProjectInfoButton?.addEventListener('click', e => debounceAsync(submitForm(e), 300))

// Capture original project state (normalize once on load)
const descriptionInput = document.querySelector('#project_description')
const budgetInput = document.querySelector('#project_budget')
const startDateInput = document.querySelector('#project_start_date')
const completionDateInput = document.querySelector('#project_completion_date')

const originalProject = {
    description: descriptionInput ? (descriptionInput.value?.trim() || null) : null,
    budget: budgetInput ? (budgetInput.value ? parseFloat(budgetInput.value) : null) : null,
    startDateTime: startDateInput ? normalizeDateFormat(startDateInput.value) || null : null,
    completionDateTime: completionDateInput ? normalizeDateFormat(completionDateInput.value) || null : null
}

// Capture original phases state
const originalPhases = {}
const phaseContainers = document.querySelectorAll('.phase') || []
phaseContainers.forEach(pc => {
    const pid = pc.dataset.phaseid
    if (!pid || pid === '') {
        return
    }

    const descInput = pc.querySelector('.phase-description')
    const startInput = pc.querySelector('.phase-start-datetime')
    const completionInput = pc.querySelector('.phase-completion-datetime')
    
    // Normalize and store original phase data
    originalPhases[pid] = {
        description: descInput ? (descInput.value?.trim() || null) : null,
        startDateTime: startInput ? normalizeDateFormat(startInput.value) || null : null,
        completionDateTime: completionInput ? normalizeDateFormat(completionInput.value) || null : null
    }
})

/**
 * Handles the submission of the editable project details form, including validation,
 * change detection, and communication with the backend to update project and phase data.
 *
 * This function performs the following steps:
 * - Prevents default form submission behavior.
 * - Prompts the user for confirmation before saving changes.
 * - Validates the presence and values of required input fields.
 * - Validates input values against project-specific validation rules.
 * - Collects and normalizes project and phase data from the form.
 * - Compares current form values to the original project data to detect changes.
 * - Constructs a payload containing only the changed project and phase data.
 * - Sends the payload to the backend if changes are detected.
 * - Handles server responses, displays success or error dialogs, and manages loading state.
 * - Resets phase tracking arrays only after successful submission.
 *
 * @async
 * @function submitForm
 * @param {Event} e The form submission event.
 * @throws Will display an error dialog if required fields are missing, validation fails, or the backend returns an error.
 * @returns {Promise<void>} Resolves when the form submission process is complete.
 */
async function submitForm(e) {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Save Changes',
        'Are you sure you want to save these changes to the project?'
    )) return

    // Retrieve input fields from the form
    const descriptionInput = document.querySelector('#project_description')
    const budgetInput = document.querySelector('#project_budget')
    const startDateInput = document.querySelector('#project_start_date')
    const completionDateInput = document.querySelector('#project_completion_date')
    if (!descriptionInput || !budgetInput || !startDateInput || !completionDateInput) {
        console.error('One or more input fields not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    // Validate inputs
    if (!validateInputs({
        description: descriptionInput.value.trim() ?? null,
        budget: parseFloat(budgetInput.value) ?? null,
        startDateTime: startDateInput.value ?? null,
        completionDateTime: completionDateInput.value ?? null
    }, workValidationRules())) return

    // Clear before re-collecting to prevent duplication
    phaseToAdd.length = 0
    phaseToEdit.length = 0

    const phaseContainers = editableProjectDetailsForm.querySelectorAll('.phase')
    phaseContainers.forEach(phaseContainer => addPhaseForm(phaseContainer)) // Collect phase data

    const projectId = editableProjectDetailsForm.dataset.projectid
    if (!projectId || projectId === 'null') {
        console.error('Project ID not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(saveProjectInfoButton.querySelector('.text-w-icon'))
    try {
        // Normalize current project
        const currentProject = {
            description: descriptionInput.value?.trim() || null,
            budget: budgetInput.value ? parseFloat(budgetInput.value) : null,
            startDateTime: normalizeDateFormat(startDateInput.value) || null,
            completionDateTime: normalizeDateFormat(completionDateInput.value) || null
        }

        // Build changedProject by comparing to originalProject captured on load
        const changedProject = {}
        const orig = originalProject || {}
        Object.keys(currentProject).forEach(key => {
            // Special handling for budget (number comparison)
            if (key === 'budget') {
                const origNum = orig[key] === null || orig[key] === undefined ? null : Number(orig[key])
                const curNum = currentProject[key] === null || currentProject[key] === undefined ? null : Number(currentProject[key])
                if (origNum !== curNum) changedProject[key] = curNum
                return
            }

            const origVal = orig[key] ?? null
            const curVal = currentProject[key] ?? null
            // Normalize values for comparison
            if (origVal !== curVal) { 
                changedProject[key] = curVal
            }
        })

        const phasePayload = {
            toAdd: phaseToAdd,
            toEdit: phaseToEdit,
            toCancel: phaseToCancel.size > 0 
                ? Array.from(phaseToCancel.values()).map(phase => ({ id: phase }))
                : null
        }

        const payload = {}
        if (Object.keys(changedProject).length > 0) { 
            payload.project = changedProject
        }
        // Check if there are any phase changes to include
        const hasPhaseChanges = 
            (phasePayload.toAdd && phasePayload.toAdd.length > 0) || 
            (phasePayload.toEdit && phasePayload.toEdit.length > 0) || 
            (phasePayload.toCancel && phasePayload.toCancel.length > 0)
        if (hasPhaseChanges) { 
            payload.phase = phasePayload
        }

        if (Object.keys(payload).length === 0) {
            // Nothing changed — no backend call required
            Dialog.operationSuccess('No changes', 'No changes detected to save.')
            Loader.delete()
            return
        }

        const response = await sendToBackend(projectId, payload)
        if (!response) {
            throw new Error('No response from server.')
        }

        // Clear phase tracking only after successful submission to prevent data loss on retry
        phaseToAdd.length = 0
        phaseToEdit.length = 0
        phaseToCancel.clear()

        setTimeout(() => window.location.href = `/TaskFlow/home`, 1500)
        Dialog.operationSuccess('Project Edited.', 'The project has been successfully edited.')
    } catch (error) {
        handleException(error, `Error submitting form: ${error}`)
    } finally {
        Loader.delete()
        isLoading = false
    }
}

/**
 * Processes a phase form container and tracks changes or additions for submission.
 *
 * This function determines whether a phase is being edited or added, compares current input values
 * with original data for existing phases, and updates the appropriate tracking arrays for later submission.
 * It normalizes date values and ensures only actual changes are tracked for edits.
 *
 * @param {HTMLElement} phaseContainer The DOM element containing the phase form fields. Must have:
 *      - data-phaseid: string Phase identifier (empty for new phases)
 *      - .phase-name: HTMLElement (for new phases) containing the phase name as textContent
 *      - .phase-description: HTMLInputElement for the phase description
 *      - .phase-start-datetime: HTMLInputElement for the phase start date/time
 *      - .phase-completion-datetime: HTMLInputElement for the phase completion date/time
 *
 * @throws Will log an error and show a dialog if required input fields are missing.
 *
 * @global {Set<string>} phaseToCancel Set of phase IDs marked for cancellation (skipped if present)
 * @global {Object} originalPhases Map of original phase data, keyed by phase ID
 * @global {Array<Object>} phaseToEdit Array to collect edited phase objects with changed fields
 * @global {Array<Object>} phaseToAdd Array to collect new phase objects to be added
 * @global {Function} normalizeDate Function to normalize date/time input values
 * @global {Object} Dialog Dialog utility for error handling
 */
function addPhaseForm(phaseContainer) {
    const phaseId = phaseContainer.dataset.phaseid
    if (phaseToCancel.has(phaseId)) return

    const nameInput = phaseContainer.querySelector(`.phase-name`)
    const descriptionInput = phaseContainer.querySelector(`.phase-description`)
    const startDateInput = phaseContainer.querySelector(`.phase-start-datetime`)
    const completionDateInput = phaseContainer.querySelector(`.phase-completion-datetime`)
    if (!descriptionInput || !startDateInput || !completionDateInput) {
        console.error(`One or more input fields not found for phase ID: ${phaseId}`)
        Dialog.somethingWentWrong()
        return
    }

    // Normalize current values
    const cur = {
        description: descriptionInput.value ? descriptionInput.value.trim() : null,
        startDateTime: normalizeDateFormat(startDateInput.value) || null,
        completionDateTime: normalizeDateFormat(completionDateInput.value) || null
    }

    // Only track existing phases that have been edited
    if (phaseId && phaseId !== '') {
        const orig = (originalPhases && originalPhases[phaseId]) ? originalPhases[phaseId] : {}
        const changed = {}

        if ((orig.description ?? null) !== (cur.description ?? null)) {
            changed.description = cur.description
        }
        if ((orig.startDateTime ?? null) !== (cur.startDateTime ?? null)) {
            changed.startDateTime = cur.startDateTime
        }
        if ((orig.completionDateTime ?? null) !== (cur.completionDateTime ?? null)) {
            changed.completionDateTime = cur.completionDateTime
        }

        // Only push when there are actual changes
        if (Object.keys(changed).length > 0) {
            phaseToEdit.push({ id: phaseId, ...changed })
        }
    } else {
        phaseToAdd.push({ name: nameInput.textContent.trim(), ...cur })
    }
}

/**
 * Sends updated project data to the backend via a PATCH request.
 *
 * This function performs the following:
 * - Checks if a request is already in progress and prevents duplicate submissions.
 * - Validates the presence and format of the projectId.
 * - Validates that data to be sent is provided.
 * - Sends a PATCH request to update the project with the specified ID.
 * - Handles errors and ensures loading state is properly managed.
 *
 * @param {string} projectId The unique identifier of the project to update. Must be a non-empty string.
 * @param {Object} data The data object containing fields to update for the project.
 * @throws {Error} If projectId is missing or empty, if data is not provided, or if the server response is invalid.
 * @returns {Promise<Object>} The response data from the backend if the request is successful.
 */
async function sendToBackend(projectId, data) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true


        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        if (!data) {
            throw new Error('No data provided.')
        }

    const response = await Http.PATCH(`projects/${projectId}`, data)
        if (!response) {
            throw new Error('No response from server.')
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}