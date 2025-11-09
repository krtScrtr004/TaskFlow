import { Dialog } from '../../../render/dialog.js'
import { Http } from '../../../utility/http.js'
import { Loader } from '../../../render/loader.js'
import { errorListDialog } from '../../../render/error-list-dialog.js'
import { confirmationDialog } from '../../../render/confirmation-dialog.js'
import { workerIds } from '../../add-worker-modal/task/new/add.js'
import { validateInputs, workValidationRules } from '../../../utility/validator.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { handleException } from '../../../utility/handle-exception.js'

let isLoading = false

const addTaskForm = document.querySelector('#add_task_form')
const addTaskButton = addTaskForm?.querySelector('#add_new_task_button')
if (!addTaskButton) {
    console.error('Add Task button not found.')
    Dialog.somethingWentWrong()
}

const add = debounceAsync(e => submitForm(e), 300)
addTaskButton?.addEventListener('click', e => add(e))
addTaskForm?.addEventListener('submit', e => add(e))

/**
 * Handles the submission of the "Add Task" form, including validation, confirmation, and backend communication.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Shows a loading indicator on the submit button.
 * - Retrieves and validates form input values.
 * - Sends the task data to the backend for creation.
 * - Handles success and error responses, including user feedback and redirection.
 * - Cleans up the loading indicator after completion.
 *
 * @async
 * @function
 * @param {Event} e The form submission event.
 * 
 * @throws {Error} If required form inputs are not found, project ID is missing, or backend response is invalid.
 * 
 * @returns {Promise<void>} Resolves when the form submission process is complete.
 */
async function submitForm(e) {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Confirm Add Task',
        'Are you sure you want to add this task?'
    )) return

    Loader.patch(addTaskButton.querySelector('.text-w-icon'))
    try {
        // Retrieve input fields from the form
        const nameInput = addTaskForm.querySelector('#task_name')
        const startDateInput = addTaskForm.querySelector('#task_start_date')
        const completionDateInput = addTaskForm.querySelector('#task_completion_date')
        const descriptionInput = addTaskForm.querySelector('#task_description')
        const prioritySelect = addTaskForm.querySelector('#task_priority')
        if (!nameInput || !startDateInput || !completionDateInput || !descriptionInput || !prioritySelect) {
            throw new Error('One or more form inputs not found.')
        }

        const params = {
            name: nameInput ? nameInput.value : '',
            startDateTime: startDateInput ? startDateInput.value : '',
            completionDateTime: completionDateInput ? completionDateInput.value : '',
            description: descriptionInput ? descriptionInput.value : '',
            priority: prioritySelect ? prioritySelect.value : '',
            workerIds: workerIds ? workerIds : {}
        }

        // Validate inputs
        if (!validateInputs(params, workValidationRules())) {
            return
        }

        const projectId = addTaskForm.dataset.projectid
        if (!projectId) {
            throw new Error('Project ID not found in form dataset.')
        }
        const response = await sendToBackend(params, projectId)
        if (!response) {
            throw new Error('No response from server.')
        }

        setTimeout(() => window.location.href = `/TaskFlow/project/${projectId}/task/${response.id}`, 1500)
        Dialog.operationSuccess('Task Added.', 'The task has been added to the project.')
    } catch (error) {
        handleException(error, 'Error submitting form:', error)
    } finally {
        Loader.delete()
    }
}

/**
 * 
 * @param {*} inputs - Object containing form input values
 * @param {string} inputs.name - Task name
 * @param {string} inputs.startDateTime - Task start date and time (ISO string)
 * @param {string} inputs.completionDateTime - Task completion date and time (ISO string)
 * @param {string} inputs.description - Task description
 * @param {string} inputs.priority - Task priority ('low', 'medium', 'high')
 * @param {Object} inputs.workerIds - Object of assigned workers
 * @returns {Promise<void>} - Resolves when the task is successfully added
 */
async function sendToBackend(inputs = {}, projectId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!inputs) {
            throw new Error('No input data provided to send to backend.')
        }

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        const {
            name,
            startDateTime,
            completionDateTime,
            description,
            priority,
            workerIds
        } = inputs

        const response = await Http.POST(`projects/${projectId}/tasks`, {
            name: name.trim(),
            description: description.trim(),
            startDateTime,
            completionDateTime,
            priority,
            workerIds: Object.keys(workerIds)
        })
        if (!response) {
            throw error
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}