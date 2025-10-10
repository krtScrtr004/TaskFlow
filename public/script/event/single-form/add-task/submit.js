import { Dialog } from '../../../render/dialog.js'
import { Http } from '../../../utility/http.js'
import { Loader } from '../../../render/loader.js'
import { confirmationDialog } from '../../../render/confirmation-dialog.js'
import { assignedWorkers } from '../../add-worker-modal/task/new/add.js'
import { validateInputs } from '../../../utility/validator.js'
import { debounceAsync } from '../../../utility/debounce.js'

let isLoading = false

const addTaskForm = document.querySelector('#add_task_form')
const addTaskButton = addTaskForm?.querySelector('#add_new_task_button')
if (addTaskButton) {
    const add = debounceAsync(e => submitForm(e), 300)
    addTaskButton.addEventListener('click', e => add(e))
    addTaskForm.addEventListener('submit', e => add(e))
} else {
    console.error('Add Task button not found.')
    Dialog.somethingWentWrong()
}

async function submitForm(e) {
    e.preventDefault()

    if (!await confirmationDialog(
        'Confirm Add Task',
        'Are you sure you want to add this task?'
    )) return

    Loader.patch(addTaskButton.querySelector('.text-w-icon'))
    try {
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
            assignedWorkers: assignedWorkers ? assignedWorkers : {}
        }

        if (!validateInputs(params)) return

        const projectId = addTaskForm.dataset.projectid
        if (!projectId) {
            throw new Error('Project ID not found in form dataset.')
        }
        const response = await sendToBackend(params, projectId)
        Dialog.operationSuccess('Task Added.', 'The task has been added to the project.')

        if (response)
            setTimeout(() => window.location.href = `/TaskFlow/project/${projectId}/task/${response.id}`, 1500)
    } catch (error) {
        console.error('Error submitting form:', error)
        Dialog.somethingWentWrong()
        return
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
 * @param {Object} inputs.assignedWorkers - Object of assigned workers
 * @returns {Promise<void>} - Resolves when the task is successfully added
 */
async function sendToBackend(inputs = {}, projectId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!inputs)
            throw new Error('No input data provided to send to backend.')

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        const {
            name,
            startDateTime,
            completionDateTime,
            description,
            priority,
            assignedWorkers
        } = inputs

        const response = await Http.POST(`projects/${projectId}/tasks`, {
            name: name.trim(),
            description: description.trim(),
            startDateTime,
            completionDateTime,
            priority,
            assignedWorkers: Object.keys(assignedWorkers).map(key => assignedWorkers[key])
        })
        if (!response)
            throw new Error('No response from server.')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}