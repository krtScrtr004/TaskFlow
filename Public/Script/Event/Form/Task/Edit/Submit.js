import { Dialog } from '../../../../Render/Dialog.js'
import { Loader } from '../../../../Render/Loader.js'
import { debounceAsync } from '../../../../Utility/Debounce.js'
import { handleException } from '../../../../Utility/HandleException.js'
import { Http } from '../../../../Utility/Http.js'
import { die } from '../../../../Utility/Utility.js'
import { 
    addedWorkerInfo, 
    changedTaskInfo, 
    changedWorkerInfo, 
    removedWorkerInfo 
} from '../RecordChanges.js'

let isLoading = false

const taskFormMainPage = document.querySelector('.task-form.main-page')

const projectId = taskFormMainPage?.dataset.projectid
if (!projectId) die('Project ID not found')

const phaseId = taskFormMainPage?.dataset.phaseid
if (!phaseId) die('Phase ID not found')

const taskId = taskFormMainPage?.dataset.taskid
if (!taskId) die('Task ID not found')

const taskForm = taskFormMainPage?.querySelector('#task_form')
if (!taskForm) die('Task form element not found')

const submitTaskButton = taskForm.querySelector('.submit-task-button')
if (!submitTaskButton) die('Submit task button not found')

const handler = e => debounceAsync(submit(e), 300)
taskForm.addEventListener('submit', handler)
submitTaskButton.addEventListener('click', handler)

/**
 * Handles the submission of a task editing form.
 *
 * This asynchronous function prevents the default form submission behavior, gathers changed task and worker
 * information, sends the data to the backend, and handles the response. On success, it displays a
 * success dialog and redirects the user to the updated task's page. On failure, it handles
 * the exception appropriately.
 * 
 * Behavior and side effects:
 * - Prevents the default form submission event using e.preventDefault().
 * - Retrieves changed task information via getTaskInfo() and worker information via getWorkersInfo().
 * - Displays a loading indicator on the submit button using Loader.patch().
 * - Sends a PATCH request to the backend with combined task and worker data via sendToBackend().
 * - On successful response, displays a success dialog using Dialog.operationSuccess().
 * - Redirects to the task detail page after a 1.5 second delay using window.location.href.
 * - On error, invokes handleException() to process and display the error.
 * - Always removes the loading indicator using Loader.delete() in the finally block.
 * - Relies on external variables: submitTaskButton, projectId, phaseId, and taskId.
 *
 * @param {Event} e - The form submission event object
 *
 * @throws {Error} Propagates any errors from getTaskInfo(), getWorkersInfo(), or sendToBackend() after handling them via handleException()
 * @return {Promise<void>} A promise that resolves when the submission process completes
 */
async function submit(e) {
    e.preventDefault()

    const taskInfo = getTaskInfo()
    const workersInfo = getWorkersInfo()

    Loader.patch(submitTaskButton.querySelector('.text-w-icon'))
    try {
        const data = await sendToBackend({
            ...taskInfo,
            workers: workersInfo
        })

        Dialog.operationSuccess('Task Edited', 'Task has been successfully updated')
        setTimeout(() => window.location.href = `/project/${projectId}/phase/${phaseId}/task/${taskId}`, 1500) 
    } catch (error) {
        handleException(error)
    } finally {
        Loader.delete()
    }
}

/**
 * Gathers changed task information.
 * 
 * @returns {Object} An object containing the changed task information.
 */
function getTaskInfo() {
    const info = {}
    for (const [key, value] of changedTaskInfo) {
        info[key] = value
    }
    return info
}

/**
 * Gathers information about workers to be added, edited, or removed.
 * 
 * @returns {Object} An object containing arrays of workers to add, edit, and remove.
 */
function getWorkersInfo() {
    const toAdd = []
    const toEdit = []
    const toRemove = []

    // Gather added workers
    for (const [workerId, workerData] of addedWorkerInfo) {
        toAdd.push({
            id: workerId,
            ...workerData
        })
    }

    for (const [workerId, workerData] of changedWorkerInfo) {
        toEdit.push({
            id: workerId,
            ...workerData
        })
    }

    // Gather removed workers
    for (const [workerId, workerData] of removedWorkerInfo) {
        toRemove.push({
            id: workerId,
            ...workerData
        })
    }

    return {
        toAdd,
        toEdit,
        toRemove
    }
}

/**
 * Sends the task data to the backend via a PATCH request.
 * 
 * @param {Object} data - The task data to be sent to the backend.
 * @returns {Promise<Object>} The response data from the backend.
 */
async function sendToBackend(data) {
    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    try {
        const endpoint = `projects/${projectId}/phases/${phaseId}/tasks/${taskId}`
        const response = await Http.PATCH(endpoint, data) 
        if (!response) throw new Error('No response from the server')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
