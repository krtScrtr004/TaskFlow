import { renderSelectedTaskWorkerCard } from '../render.js'
import { addWorker } from './add-worker-event.js'
import { Http } from '../../../utility/http.js'
import { handleException } from '../../../utility/handle-exception.js'
import { createFullName, die, toggleElementClass } from '../../../utility/utility.js'
import { addedWorkerInfo, oldWorkerInfo, removedWorkerInfo } from '../../form/task/record-changes.js'

let isLoading = false

const taskForm = document.querySelector('#task_form')
const noAssignedWorkerWall = taskForm?.querySelector('.no-workers-wall')

const thisProjectId = taskForm?.parentElement.dataset.projectid
if (!thisProjectId || thisProjectId.trim() === '')
    die('Project ID not found')

try {
    await addWorker(
        thisProjectId,
        async (projectId, workersId) => sendToBackend(projectId, workersId),
        (workersData) => action(workersData),
    )
} catch (error) {
    handleException(error, `Error adding worker: ${error}`)
}

/**
 *  Sends a request to the backend to get the information of selected workers to be added to the task.
 * 
 * @param {string} projectId - Project ID to see if selected workers are part of the project
 * @param {string[]} workerIds - Array of worker IDs to add to the project
 * @returns {Promise<Object[]>} Resolves with array of worker data objects on success
 */
async function sendToBackend(projectId, workerIds) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') throw new Error('Project ID is required.')

        if (!workerIds || workerIds.length === 0) throw new Error('No worker IDs provided.')

        const idParams = workerIds.map(id => `${id}`).join(',')
        const response = await Http.GET(`projects/${projectId}/workers?ids=${idParams}`)
        if (!response) throw new Error('No response from server.')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

/**
 * Adds worker cards to the task worker list in the UI for each worker in the provided data array.
 *
 * Iterates through the given array of worker data objects, and for each worker:
 * - Skips the worker if their ID already exists in the `workerIds` map.
 * - Creates a task worker card element using the worker's details.
 * - Finds the task worker list container in the DOM and throws an error if not found.
 * - Appends the created worker card to the task worker list.
 * - Updates the UI to hide the "no assigned worker" wall if present.
 * - Adds the worker's data to the `workerIds` map to prevent duplicates.
 *
 * @param {Array<Object>} workersData - Array of worker data objects to be added.
 * @throws {Error} If the task worker list container is not found in the DOM.
 */
function action(workersData) {
    workersData.forEach(workerData => {
        if (oldWorkerInfo.has(workerData.id) || addedWorkerInfo.has(workerData.id)) return

        const card = renderSelectedTaskWorkerCard({
            id: workerData.id,
            fullName: createFullName(workerData.firstName, workerData.middleName, workerData.lastName),
            unitRate: workerData.defaultRate ?? 0.00,
            estimatedHoursAssigned: workerData.estimatedHoursAssigned ?? 0.00,
            iconPath: '/public/asset/image/icon/'
        })
        const selectedWorkerList = document.querySelector('.selected-worker-list')
        if (!selectedWorkerList)
            throw new Error('Selected worker list container not found')

        // Append the worker card to the task worker list
        selectedWorkerList.appendChild(card)

        // Record worker info 
        addedWorkerInfo.set(workerData.id, {
            unitRate: workerData.defaultRate ?? 0.00,
            hoursAssigned: workerData.estimatedHoursAssigned ?? 0.00
        })
        removedWorkerInfo.clear(workerData.id)

        // Hide the "no assigned worker" wall if it exists
        toggleElementClass(noAssignedWorkerWall, ['no-display'], ['flex-col'])
    })
}

