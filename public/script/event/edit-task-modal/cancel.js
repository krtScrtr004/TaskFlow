import { Http } from '../../utility/http.js'
import { debounceAsync } from '../../utility/debounce.js'
import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const viewTaskInfo = document.querySelector('.view-task-info')
const cancelTaskButton = viewTaskInfo.querySelector('#cancel_task_button')
if (!cancelTaskButton) {
    console.error('Cancel Task button not found.')
}

cancelTaskButton?.addEventListener('click', e => debounceAsync(submit(e), 3000))

/**
 * Handles the cancellation of a task via a modal form submission.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Validates the presence of required dataset attributes: projectId, phaseId, and taskId.
 * - Shows a loading indicator while processing the cancellation.
 * - Sends a cancellation request to the backend.
 * - Reloads the page upon successful cancellation to reflect changes.
 * - Handles errors and displays appropriate error dialogs.
 * - Removes the loading indicator after completion.
 *
 * @async
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the cancellation process is complete.
 */
async function submit(e) {
    e.preventDefault()

    Loader.patch(cancelTaskButton.querySelector('.text-w-icon'))

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Cancel Task',
        'Are you sure you want to cancel this task?'
    )) return

    const projectId = viewTaskInfo.dataset.projectid
    if (!projectId) {
        console.error('Project ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    const phaseId = viewTaskInfo.dataset.phaseid
    if (!phaseId) {
        console.error('Phase ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    const taskId = viewTaskInfo.dataset.taskid
    if (!taskId) {
        console.error('Task ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(projectId, phaseId, taskId)
        // Reload the page to reflect changes
        window.location.reload()
    } catch (error) {
        handleException(error, `Error cancelling task: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends a request to the backend to cancel a specific task within a project phase.
 *
 * This function performs the following actions:
 * - Validates that projectId, phaseId, and taskId are provided.
 * - Prevents concurrent requests using an isLoading flag.
 * - Sends an HTTP PUT request to update the task status to 'cancelled'.
 * - Handles errors and ensures the loading state is reset.
 *
 * @param {string} projectId The unique identifier of the project.
 * @param {string} phaseId The unique identifier of the phase within the project.
 * @param {string} taskId The unique identifier of the task to be cancelled.
 * 
 * @throws {Error} If any of the required parameters are missing or if the request fails.
 * @returns {Promise<void>} Resolves when the backend request completes.
 */
async function sendToBackend(projectId, phaseId, taskId) {
    try {
        if (isLoading) {
            console.warn('Request is already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId) {
            throw new Error('Project ID is required.')
        }

        if (!phaseId) {
            throw new Error('Phase ID is required.')
        }

        if (!taskId) {
            throw new Error('Task ID is required.')
        }

        const response = await Http.PUT(`projects/${projectId}/phases/${phaseId}/tasks/${taskId}`, { status: 'cancelled' })
        if (!response) {
            throw error
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}