import { addWorker } from '../modal.js' 
import { Http } from '../../../utility/http.js' 
import { Dialog } from '../../../render/dialog.js' 
import { Notification } from '../../../render/notification.js' 

let isLoading = false // Flag to prevent duplicate requests

const projectContainer = document.querySelector('.project-container') 
const thisProjectId =  projectContainer?.dataset.projectid ?? null 
if (!thisProjectId || thisProjectId.trim() === '') { // Check if project ID is missing or empty
    console.error('Project ID not found.')
    Dialog.somethingWentWrong() // Show error dialog if project ID is invalid
}

// Call addWorker function event handler
await addWorker(
    thisProjectId, 
    async (projectId, workerIds) => await sendToBackend(projectId, workerIds), // Callback to send data workers to add to a project
    () => {
        const delay = 1500
        setTimeout(() => window.location.reload(), delay)
        Notification.success('Workers added to project successfully.', delay)
    }
)

/**
 * Sends a list of worker IDs to the backend to associate them with a specific project.
 *
 * This function performs the following:
 * - Prevents concurrent requests using an isLoading flag.
 * - Validates that a non-empty projectId is provided.
 * - Validates that at least one workerId is provided.
 * - Sends a POST request to the backend endpoint `projects/{projectId}/workers` with the worker IDs.
 * - Handles errors and ensures the loading state is reset.
 *
 * @param {string} projectId The unique identifier of the project to which workers will be added.
 * @param {string[]} workerIds An array of worker IDs to associate with the project.
 * 
 * @throws {Error} If projectId is missing or empty.
 * @throws {Error} If workerIds is missing or empty.
 * @throws {Error} If the server does not respond or another error occurs during the request.
 * 
 * @returns {Promise<any>} The response from the backend after adding the workers to the project.
 */
async function sendToBackend(projectId, workerIds) {
    try {
        // Prevent concurrent requests
        if (isLoading) { 
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true 

        if (!projectId || projectId.trim() === '') { 
            throw new Error('Project ID is required.')
        }

        if (!workerIds || workerIds.length === 0) { 
            throw new Error('No worker IDs provided.')
        }

        const response = await Http.POST(`projects/${projectId}/workers`, { workerIds }) 
        if (!response) { 
            throw new Error('No response from server.')
        }

        return response 
    } catch (error) {
        throw error 
    } finally {
        isLoading = false 
    }
}