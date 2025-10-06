import { Dialog } from '../../../render/dialog.js'
import { Http } from '../../../utility/http.js'
import { Loader } from '../../../render/loader.js'
import { confirmationDialog } from '../../../render/confirmation-dialog.js'
import { Notification } from '../../../render/notification.js'
import { assignedWorkers } from '../../add-worker-modal/add-to-task.js'

/**
 * 
 * @param {*} inputs - Object containing form input values
 * @param {string} inputs.name - Task name
 * @param {string} inputs.startDateTime - Task start date and time (ISO string)
 * @param {string} inputs.completionDateTime - Task completion date and time (ISO string)
 * @param {string} inputs.description - Task description
 * @param {string} inputs.priority - Task priority ('low', 'medium', 'high')
 * @param {Object} inputs.assignedWorkers - Object of assigned workers
 * @return {boolean} - Returns true if all inputs are valid, otherwise false
 */
function validateInputs(inputs = {}) {
    const {
        name,
        startDateTime,
        completionDateTime,
        description,
        priority,
        assignedWorkers
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
        },
        {
            condition: !priority || !['low', 'medium', 'high'].includes(priority),
            message: 'Invalid task priority selected.'
        },
        {
            condition: !assignedWorkers || Object.keys(assignedWorkers).length < 1 || Object.keys(assignedWorkers).length > 10,
            message: 'Please assign between 1 and 10 workers to the task.'
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
let isLoading = false
async function sendToBackend(inputs = {}, projectId) {
    if (isLoading) return
    isLoading = true

    const {
        name,
        startDateTime,
        completionDateTime,
        description,
        priority,
        assignedWorkers
    } = inputs

    const response = await Http.POST(`add-task/${projectId}`, {
        name: name.trim(),
        description: description.trim(),
        startDateTime,
        completionDateTime,
        priority,
        assignedWorkers: Object.keys(assignedWorkers).map(key => assignedWorkers[key])
    })
    if (!response)
        throw new Error('No response from server.')

    isLoading = false
}

const addTaskForm = document.querySelector('#add_task_form')
if (addTaskForm) {
    const addTaskButton = addTaskForm.querySelector('#add_new_task_button')
    if (!addTaskButton) {
        console.error('Add Task button not found.')
        Dialog.somethingWentWrong()
    } else {
        async function submitForm() {
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
                await sendToBackend(params, projectId)
                Dialog.operationSuccess('Task Added.', 'The task has been added to the project.')

                // TODO:
            } catch (error) {
                console.error('Error submitting form:', error)
                Dialog.somethingWentWrong()
                return                
            } finally {
                Loader.delete()
            }
        }

        addTaskButton.addEventListener('click', async e => {
            e.preventDefault()
            await submitForm()
        })
        addTaskForm.addEventListener('submit', async e => {
            e.preventDefault()
            await submitForm()
        })
    }
} else {
    console.error('Add Task form not found.')
    Dialog.somethingWentWrong()
}