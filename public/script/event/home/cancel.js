import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Http } from '../../utility/http.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const cancelProjectButton = document.querySelector('#cancel_project_button')
if (!cancelProjectButton) console.warn('Cancel Project button not found.')

cancelProjectButton?.addEventListener('click', async (e) => {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Cancel Project',
        'Are you sure you want to cancel this project? This action cannot be undone.',
    )) return

    try {
        const mainProjectContent = document.querySelector('.main-project-content')
        if (!mainProjectContent) throw new Error('Main project content element not found')

        const projectId = mainProjectContent.dataset.projectid
        if (!projectId) throw new Error('Project ID not found in data attributes.')

        await sendToBackend(projectId)
        window.location.reload()
    } catch (error) {
        handleException(error)
    }
})


/**
 * Sends a request to the backend to update the status of a project to "cancelled".
 *
 * This function performs the following:
 * - Checks if a request is already in progress and prevents duplicate submissions.
 * - Validates that the provided projectId is a non-empty string.
 * - Sends an HTTP PUT request to update the project's status to "cancelled".
 * - Handles errors and ensures the loading state is properly managed.
 *
 * @param {string} projectId The unique identifier of the project to be cancelled.
 * @throws {Error} If the projectId is missing or empty, or if the HTTP request fails.
 * @returns {Promise<void>} Resolves when the request completes successfully or throws on error.
 */
async function sendToBackend(projectId) {
    try {
        if (isLoading) {
            console.warn('Request is already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') throw new Error('Project ID is required')

        const response = await Http.PUT(`projects/${projectId}`, { project: {status: 'cancelled'} })
        if (!response) throw new Error('No response from server')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}