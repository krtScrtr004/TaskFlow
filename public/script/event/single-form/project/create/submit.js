import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Loader } from '../../../../render/loader.js'
import { errorListDialog } from '../../../../render/error-list-dialog.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { validateInputs, workValidationRules } from '../../../../utility/validator.js'
import { handleException } from '../../../../utility/handle-exception.js'
import { normalizeDateFormat } from '../../../../utility/utility.js'

let isLoading = false
const phaseToAdd = []

const createProjectForm = document.querySelector('#create_project_form')
if (!createProjectForm) {
    console.error('Create Project form not found.')
    Dialog.somethingWentWrong()
}

createProjectForm?.addEventListener('submit', e => debounceAsync(submitForm(e), 300))

const submitProjectButton = createProjectForm?.querySelector('#submit_project_button')
if (!submitProjectButton) {
    console.error('Submit Project button not found.')
    Dialog.somethingWentWrong()
}

submitProjectButton?.addEventListener('click', e => debounceAsync(submitForm(e), 300))


/**
 * Handles the submission of the Create Project form.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Retrieves and validates all required input fields from the form.
 * - Validates the input values using custom validation rules.
 * - Processes all phase containers and adds their data to the submission payload.
 * - Shows a loading indicator while submitting the data to the backend.
 * - Sends the project and phase data to the backend API.
 * - Handles the backend response, displaying a success dialog and redirecting on success.
 * - Handles errors by displaying an error dialog and logging the exception.
 * - Cleans up the loader and resets the phase data after submission.
 *
 * @async
 * @function
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the form submission process is complete.
 */
async function submitForm(e) {
    e.preventDefault()

    Loader.patch(submitProjectButton.querySelector('.text-w-icon'))

    if (!await confirmationDialog(
        'Add Project',
        'Are you sure you want to add this project?',
    )) return

    // Retrieve input fields from the form
    const nameInput = createProjectForm.querySelector('#project_name')
    const descriptionInput = createProjectForm.querySelector('#project_description')
    const budgetInput = createProjectForm.querySelector('#project_budget')
    const startDateInput = createProjectForm.querySelector('#project_start_date')
    const completionDateInput = createProjectForm.querySelector('#project_completion_date')
    if (!nameInput || !descriptionInput || !budgetInput || !startDateInput || !completionDateInput) {
        console.error('One or more input fields not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    // Validate inputs
    if (!validateInputs({
        name: nameInput.value.trim() ?? null,
        description: descriptionInput.value.trim() ?? null,
        budget: parseFloat(budgetInput.value) ?? null,
        startDateTime: startDateInput.value ?? null,
        completionDateTime: completionDateInput.value ?? null
    }, workValidationRules())) return

    const phaseContainers = createProjectForm.querySelectorAll('.phase')
    phaseContainers.forEach(phaseContainer => addPhaseForm(phaseContainer))

    try {
        const response = await sendToBackend({
            project: {
                name: nameInput.value.trim(),
                description: descriptionInput.value.trim(),
                budget: parseFloat(budgetInput.value),
                startDateTime: normalizeDateFormat(startDateInput.value),
                completionDateTime: normalizeDateFormat(completionDateInput.value),
            },
            phases: phaseToAdd,
        })
        if (!response) {
            throw new Error('No response from server.')
        }

        setTimeout(() => window.location.href = `/TaskFlow/home`, 1500)
        Dialog.operationSuccess('Project Created.', 'The project has been successfully created.')
    } catch (error) {
        handleException(error, 'Error submitting form:', error)
    } finally {
        Loader.delete()
        phaseToAdd.length = 0
    }
}

/**
 * Adds a phase form's data to the phaseToAdd array.
 *
 * This function extracts and validates input values from the provided phase container element:
 * - Retrieves the phase name from a contenteditable element with class `.phase-name`
 * - Retrieves the phase description from an input or textarea with class `.phase-description`
 * - Retrieves the phase start date/time from an input with class `.phase-start-datetime`
 * - Retrieves the phase completion date/time from an input with class `.phase-completion-datetime`
 * - Trims string values and sets null if not present
 * - Throws an error if the phase container is not found
 * - Logs an error and shows a dialog if any required input field is missing
 * - Pushes the collected data as an object to the global phaseToAdd array
 *
 * @param {HTMLElement} phaseContainer The DOM element containing the phase form fields. Must contain:
 *      - .phase-name: HTMLElement (contenteditable, phase name)
 *      - .phase-description: HTMLInputElement|HTMLTextAreaElement (phase description)
 *      - .phase-start-datetime: HTMLInputElement (start date/time)
 *      - .phase-completion-datetime: HTMLInputElement (completion date/time)
 * @throws {Error} If the phase container is not found.
 * @returns {void}
 */
function addPhaseForm(phaseContainer) {
    if (!phaseContainer) {
        throw new Error('Phase container not found.')
    }

    const nameInput = phaseContainer.querySelector(`.phase-name`)
    const descriptionInput = phaseContainer.querySelector(`.phase-description`)
    const startDateInput = phaseContainer.querySelector(`.phase-start-datetime`)
    const completionDateInput = phaseContainer.querySelector(`.phase-completion-datetime`)
    if (!nameInput || !descriptionInput || !startDateInput || !completionDateInput) {
        console.error(`One or more input fields not found.`)
        Dialog.somethingWentWrong()
        return
    }

    // Collect phase data
    const data = {
        name: nameInput.textContent ? nameInput.textContent.trim() : null,
        description: descriptionInput.value ? descriptionInput.value.trim() : null,
        startDateTime: startDateInput.value ? startDateInput.value : null,
        completionDateTime: completionDateInput.value ? completionDateInput.value : null,
    }
    // Add phase data to the array
    phaseToAdd.push(data)
}

/**
 * Sends project creation data to the backend API.
 *
 * This function manages the request state to prevent duplicate submissions,
 * validates the input data, and handles server responses and errors.
 * It uses an HTTP POST request to send the provided data to the 'projects' endpoint.
 *
 * @param {Object} data The project data to be sent to the backend. Should contain all required fields for project creation.
 * @throws {Error} Throws an error if no input data is provided, if the server does not respond, or if an HTTP/network error occurs.
 * @returns {Promise<Object>} Resolves with the response data from the backend if the request is successful.
 */
async function sendToBackend(data) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!data) {
            throw new Error('No input data provided.')
        }

        const response = await Http.POST(`projects`, data)
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