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
 * Handles the submission event for cancelling a task.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Prompts the user with a confirmation dialog to ensure they want to cancel the task.
 * - Retrieves the project and task IDs from the `viewTaskInfo` dataset.
 * - Displays a loading indicator on the cancel button.
 * - Sends a cancellation request to the backend.
 * - Reloads the page upon successful cancellation.
 * - Handles and logs any errors that occur during the process.
 * - Removes the loading indicator after the operation completes.
 *
 * @async
 * @param {Event} e The event object from the form submission.
 * @returns {Promise<void>} Resolves when the cancellation process is complete.
 */
async function submit(e) {
    e.preventDefault()

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
    
    const taskId = viewTaskInfo.dataset.taskid
    if (!taskId) {
        console.error('Task ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(cancelTaskButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(projectId, taskId)
        // Reload the page to reflect changes
        window.location.reload()
    } catch (error) {
        handleException(error, `Error cancelling task: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends a request to the backend to cancel a specific task within a project.
 *
 * This function performs the following actions:
 * - Checks if a request is already in progress and prevents duplicate requests.
 * - Validates that both projectId and taskId are provided.
 * - Sends an HTTP PUT request to update the task status to 'cancelled'.
 * - Handles errors and ensures the loading state is properly managed.
 *
 * @param {string} projectId The unique identifier of the project containing the task.
 * @param {string} taskId The unique identifier of the task to be cancelled.
 * @throws {Error} Throws an error if projectId or taskId is missing, or if the HTTP request fails.
 * @returns {Promise<void>} Resolves when the request completes successfully; rejects if an error occurs.
 */
async function sendToBackend(projectId, taskId) {
    try {
        if (isLoading) {
            console.warn('Request is already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId) {
            throw new Error('Project ID is required.')
        }

        if (!taskId) {
            throw new Error('Task ID is required.')
        }

        const response = await Http.PUT(`projects/${projectId}/tasks/${taskId}`, { status: 'cancelled' })
        if (!response) {
            throw error
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}