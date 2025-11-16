import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { validateInputs, workValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'
import { normalizeDateFormat } from '../../utility/utility.js'

let isLoading = false
let originalValues = {}

const editTaskModalTemplate = document.querySelector('#edit_task_modal_template')
const editTaskForm = editTaskModalTemplate?.querySelector('#edit_task_form')
const editTaskButton = editTaskModalTemplate?.querySelector('#edit_task_button')

if (!editTaskButton) {
    console.error('Edit Task button not found.')
    Dialog.somethingWentWrong()
}

// Store original values when modal is opened
const editTaskOpenButton = document.querySelector('.view-task-info #edit_task_button')
if (editTaskOpenButton) {
    editTaskOpenButton.addEventListener('click', () => {
        // Wait for modal to be visible and inputs to be populated
        setTimeout(() => {
            storeOriginalValues()
        }, 100)
    })
}

editTaskButton?.addEventListener('click', e => debounceAsync(submitForm(e), 300))

/**
 * Stores the original values from the form inputs for comparison later.
 * This allows us to detect which fields have actually changed.
 */
function storeOriginalValues() {
    const nameInput = editTaskForm?.querySelector('#task_name')
    const descriptionInput = editTaskForm?.querySelector('#task_description')
    const startDateInput = editTaskForm?.querySelector('#task_start_datetime')
    const completionDateInput = editTaskForm?.querySelector('#task_completion_datetime')
    const prioritySelect = editTaskForm?.querySelector('#task_priority')

    originalValues = {
        name: nameInput?.value || '',
        description: descriptionInput?.value || '',
        startDateTime: normalizeDateFormat(startDateInput?.value) || '',
        completionDateTime: normalizeDateFormat(completionDateInput?.value) || '',
        priority: prioritySelect?.value || ''
    }
}

/**
 * Handles the submission of the Edit Task form, including validation, change detection, and backend update.
 *
 * This function performs the following steps:
 * - Prevents default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Retrieves and checks all required input fields from the form.
 * - Compares current input values with original values to detect changes.
 * - Validates only the changed input fields using provided validation rules.
 * - Extracts project, phase, and task IDs from the DOM.
 * - Sends the changed data to the backend for updating the task.
 * - Displays success or error dialogs based on the outcome.
 * - Reloads the page after a successful update.
 *
 * @async
 * @function submitForm
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the form submission process is complete.
 *
 * @throws {Error} If required input fields or IDs are missing, or if the backend update fails.
 */
async function submitForm(e) {
    e.preventDefault()

    try {
        Loader.patch(editTaskButton.querySelector('.text-w-icon'))

        // Show confirmation dialog
        if (!await confirmationDialog(
            'Confirm Edit Task',
            'Are you sure you want to save these changes to the task?'
        )) return

        // Retrieve input fields from the form
        const nameInput = editTaskForm.querySelector('#task_name')
        const descriptionInput = editTaskForm.querySelector('#task_description')
        const startDateInput = editTaskForm.querySelector('#task_start_datetime')
        const completionDateInput = editTaskForm.querySelector('#task_completion_datetime')
        const prioritySelect = editTaskForm.querySelector('#task_priority')
        if (!nameInput || !descriptionInput || !startDateInput || !completionDateInput || !prioritySelect) {
            throw new Error('One or more form inputs not found.')
        }

        const currentValues = {
            name: nameInput ? nameInput.value : '',
            description: descriptionInput ? descriptionInput.value : '',
            startDateTime: normalizeDateFormat(startDateInput?.value) || '',
            completionDateTime: normalizeDateFormat(completionDateInput?.value) || '',
            priority: prioritySelect ? prioritySelect.value : '',
        }

        // Build params object with only changed values
        const params = {}
        let hasChanges = false

        for (const [key, value] of Object.entries(currentValues)) {
            if (value !== originalValues[key]) {
                params[key] = value
                hasChanges = true
            }
        }

        // Check if there are any changes
        if (!hasChanges) {
            Dialog.operationSuccess('No Changes', 'No changes were made to the task.')
            return
        }

        // Validate only the changed inputs
        if (!validateInputs(params, workValidationRules())) return

        const viewTaskInfo = document.querySelector('.view-task-info.main-page')
        if (!viewTaskInfo) {
            throw new Error('View Task Info main page not found.')
        }

        const projectId = viewTaskInfo.dataset.projectid
        if (!projectId || projectId === '') {
            throw new Error('Project ID not found.')
        }

        const phaseId = viewTaskInfo.dataset.phaseid
        if (!phaseId || phaseId === '') {
            throw new Error('Phase ID not found.')
        }

        const taskId = viewTaskInfo.dataset.taskid
        if (!taskId || taskId === '') {
            throw new Error('Task ID not found.')
        }

        await sendToBackend(projectId, phaseId, taskId, params)

        setTimeout(() => window.location.reload(), 1500)
        Dialog.operationSuccess('Task Updated.', 'The task has been successfully updated.')
    } catch (error) {
        handleException(error, `Error submitting form: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends updated task data to the backend for a specific project, phase, and task.
 *
 * This function performs validation on the provided identifiers and input data before making an HTTP PUT request.
 * It prevents concurrent requests using an isLoading flag and throws descriptive errors for invalid input.
 *
 * @param {string} projectId - The unique identifier of the project. Must be a non-empty string.
 * @param {string} phaseId - The unique identifier of the phase within the project. Must be a non-empty string.
 * @param {string} taskId - The unique identifier of the task within the phase. Must be a non-empty string.
 * @param {Object} inputs - An object containing the updated task data to send to the backend. Must not be empty.
 *      - [key: string]: any - Key-value pairs representing task fields and their updated values.
 *
 * @throws {Error} If any of the required identifiers are missing or empty, or if no input data is provided.
 * @throws {Error} If the backend request fails or returns no response.
 *
 * @return {Promise<Object>} The response object from the backend after updating the task.
 */
async function sendToBackend(projectId, phaseId, taskId, inputs) {
    try {
        if (isLoading) {
            console.log('Request already in progress')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        if (!phaseId || phaseId.trim() === '') {
            throw new Error('Phase ID is required.')
        }

        if (!taskId || taskId.trim() === '') {
            throw new Error('Task ID is required.')
        }

        if (!inputs || Object.keys(inputs).length === 0) {
            throw new Error('No input data provided to send to backend.')
        }

        const response = await Http.PUT(`projects/${projectId}/phases/${phaseId}/tasks/${taskId}`, inputs)
        if (!response) {
            throw new Error('Failed to update task.')
        }
        return response
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}