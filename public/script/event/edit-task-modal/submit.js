import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { validateInputs, workValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

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
const editTaskOpenButton = document.querySelector('#edit_task_button')
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
        startDateTime: startDateInput?.value || '',
        completionDateTime: completionDateInput?.value || '',
        priority: prioritySelect?.value || ''
    }
}

/**
 * Handles the submission of the Edit Task form, including validation, confirmation, and backend update.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Prompts the user for confirmation before proceeding.
 * - Retrieves and validates input fields from the Edit Task form.
 * - Extracts project and task IDs from the DOM.
 * - Shows a loading indicator and sends the updated task data to the backend.
 * - Handles errors and displays appropriate dialogs.
 * - Reloads the page after a successful update and notifies the user.
 *
 * @async
 * @param {Event} e The form submission event.
 * 
 * @throws {Error} If required DOM elements are not found or if the backend request fails.
 * 
 * @returns {Promise<void>} Resolves when the form submission process is complete.
 */
async function submitForm(e) {
    e.preventDefault()

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
        console.error('One or more input fields not found in the Edit Task form.')
        Dialog.somethingWentWrong()
        return
    }

    const currentValues = {
        name: nameInput ? nameInput.value : '',
        description: descriptionInput ? descriptionInput.value : '',
        startDateTime: startDateInput ? startDateInput.value : '',
        completionDateTime: completionDateInput ? completionDateInput.value : '',
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
        console.error('View Task Info main page not found.')
        Dialog.somethingWentWrong()
        return
    }

    const projectId = viewTaskInfo.dataset.projectid
    if (!projectId || projectId === '') {
        console.error('Project ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    const taskId = viewTaskInfo.dataset.taskid
    if (!taskId || taskId === '') {
        console.error('Task ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(editTaskButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(projectId, taskId, params)

        setInterval(() => window.location.reload(), 3000)
        Dialog.operationSuccess('Task Updated.', 'The task has been successfully updated.')
    } catch (error) {
        handleException(error, `Error submitting form: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends task update to the backend
 * @param {string} projectId - The project ID
 * @param {string} taskId - The task ID
 * @param {Object} inputs - Task data to update
 * @returns {Promise<Object|null>} - The response data or null if failed
 */
async function sendToBackend(projectId, taskId, inputs) {
    try {
        if (isLoading) {
            console.log('Request already in progress')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        if (!taskId || taskId.trim() === '') {
            throw new Error('Task ID is required.')
        }

        if (!inputs || Object.keys(inputs).length === 0) {
            throw new Error('No input data provided to send to backend.')
        }

        const response = await Http.PUT(`projects/${projectId}/tasks/${taskId}`, inputs)
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