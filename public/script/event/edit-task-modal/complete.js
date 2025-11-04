import { Http } from '../../utility/http.js'
import { debounceAsync } from '../../utility/debounce.js'
import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { handleException } from '../../utility/handle-exception.js'
import { compareDates } from '../../utility/utility.js'

let isLoading = false

const viewTaskInfo = document.querySelector('.view-task-info')
const completeTaskButton = viewTaskInfo.querySelector('#complete_task_button')

if (!completeTaskButton) {
    console.error('Complete Task button not found.')
}

completeTaskButton?.addEventListener('click', e => debounceAsync(submit(e), 3000))

document.addEventListener('DOMContentLoaded', () => {
    const taskCompletionDateTime = viewTaskInfo?.querySelector('.task-completion-datetime')?.dataset.completiondatetime
    const taskActualCompletionDateTime = viewTaskInfo?.querySelector('.task-actual-completion-datetime')?.dataset.actualcompletiondatetime

    // Compare the dates and show dialog if delayed
    if ((taskActualCompletionDateTime && taskActualCompletionDateTime !== '') && 
        (compareDates(taskCompletionDateTime, taskActualCompletionDateTime) > 0)) {
        // Show delayed task dialog
        const taskName = viewTaskInfo?.querySelector('.task-name')?.textContent || 'This task'
        Dialog.taskDelayed(taskName)
    }
})

/**
 * Handles the submission process for completing a task.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Retrieves the project and task IDs from the viewTaskInfo element's dataset.
 * - Shows an error dialog if required IDs are missing.
 * - Shows a loading indicator on the cancel task button.
 * - Sends a request to the backend to complete the task.
 * - Reloads the page upon successful completion to reflect changes.
 * - Handles any errors by displaying an appropriate error message.
 * - Removes the loading indicator after the process is complete.
 *
 * @async
 * @param {Event} e The event object from the form submission.
 * @returns {Promise<void>} Resolves when the submission process is complete.
 */
async function submit(e) {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Complete Task',
        'Are you sure you want to complete this task?'
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
        handleException(error, `Error completing task: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends a request to the backend to mark a specific task as completed within a project.
 *
 * This function performs the following:
 * - Validates that both projectId and taskId are provided.
 * - Prevents concurrent requests by checking and setting a loading state.
 * - Sends an HTTP PUT request to update the task's status to 'completed'.
 * - Handles errors and ensures the loading state is reset after the request.
 *
 * @param {string} projectId The unique identifier of the project containing the task.
 * @param {string} taskId The unique identifier of the task to be marked as completed.
 * @throws {Error} If projectId or taskId is not provided, or if the HTTP request fails.
 * @returns {Promise<void>} Resolves when the task status is successfully updated.
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

        const response = await Http.PUT(`projects/${projectId}/tasks/${taskId}`, { status: 'completed' })
        if (!response) {
            throw error
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}