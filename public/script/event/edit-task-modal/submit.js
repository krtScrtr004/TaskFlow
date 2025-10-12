import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { validateInputs, workValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'

let isLoading = false
const editTaskModalTemplate = document.querySelector('#edit_task_modal_template')
const editTaskForm = editTaskModalTemplate?.querySelector('#edit_task_form')
const editTaskButton = editTaskModalTemplate?.querySelector('#edit_task_button')

if (editTaskButton) {
    editTaskButton.addEventListener('click', e => debounceAsync(submitForm(e), 300))
} else {
    console.error('Edit Task button not found.')
    Dialog.somethingWentWrong()
}

async function submitForm(e) {
    e.preventDefault()

    if (!await confirmationDialog(
        'Confirm Edit Task',
        'Are you sure you want to save these changes to the task?'
    )) return

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

    const params = {
        name: nameInput ? nameInput.value : '',
        description: descriptionInput ? descriptionInput.value : '',
        startDateTime: startDateInput ? startDateInput.value : '',
        completionDateTime: completionDateInput ? completionDateInput.value : '',
        priority: prioritySelect ? prioritySelect.value : '',
    }
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
        isLoading = true
        await sendToBackend(projectId, taskId, params)

        setInterval(() => {
            window.location.reload()
        }, 3000)
    } catch (error) {
        console.error('Error submitting form:', error)
        errorListDialog(error?.errors, error?.message)
    } finally {
        Loader.delete()
        isLoading = false
    }

    Dialog.operationSuccess('Task Updated.', 'The task has been successfully updated.')
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


        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        if (!taskId || taskId.trim() === '')
            throw new Error('Task ID is required.')

        if (!inputs || Object.keys(inputs).length === 0)
            throw new Error('No input data provided to send to backend.')

        const response = await Http.PUT(`projects/${projectId}/tasks/${taskId}`, inputs)
        if (!response)
            throw error
        return response
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}