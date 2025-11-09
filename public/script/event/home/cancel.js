import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Http } from '../../utility/http.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const cancelProjectButton = document.querySelector('#cancel_project_button')
if (!cancelProjectButton) {
    console.error('Cancel Project button not found.')
    Dialog.somethingWentWrong()
}

cancelProjectButton?.addEventListener('click', async (e) => {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Cancel Project',
        'Are you sure you want to cancel this project? This action cannot be undone.',
    )) return

    const mainProjectContent = document.querySelector('.main-project-content')
    if (!mainProjectContent) {
        console.error('Main project content element not found.')
        Dialog.somethingWentWrong()
        return
    }
    const projectId = mainProjectContent.dataset.projectid
    if (!projectId) {
        console.error('Project ID not found in data attributes.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(projectId)
        window.location.reload()
    } catch (error) {
        handleException(error, `Error cancelling project: ${error}`)
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

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        const response = await Http.PUT(`projects/${projectId}`, { project: {status: 'cancelled'} })
        if (!response) {
            throw error
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}