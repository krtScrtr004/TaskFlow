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

// On page load, check if the task was completed late and show dialog if so
document.addEventListener('DOMContentLoaded', () => {
    const status = viewTaskInfo?.dataset.status.toLowerCase() || ''
    if (status === 'completed' || status === 'cancelled') {
        return 
    }

    const now = new Date()
    const taskCompletionDateTime = viewTaskInfo?.querySelector('.task-completion-datetime')?.dataset.completiondatetime
    const taskActualCompletionDateTime = viewTaskInfo?.querySelector('.task-actual-completion-datetime')?.dataset.actualcompletiondatetime
    
    // Check if task deadline has passed (now is AFTER the deadline)
    if ((!taskActualCompletionDateTime || taskActualCompletionDateTime === '') &&
        (taskCompletionDateTime && compareDates(now.toISOString(), taskCompletionDateTime) < 0)) {
        // Show delayed task dialog - deadline has passed
        const taskName = viewTaskInfo?.querySelector('.task-name')?.textContent || 'This task'
        Dialog.taskDelayed(taskName)
    }
})


/**
 * Handles the submission of the "Complete Task" action in the edit task modal.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user.
 * - Retrieves project, phase, and task IDs from the viewTaskInfo dataset.
 * - Shows an error dialog if any required ID is missing.
 * - Shows a loading indicator on the complete task button.
 * - Sends a request to the backend to complete the task.
 * - Reloads the page upon successful completion.
 * - Handles exceptions by displaying an error dialog.
 * - Removes the loading indicator after the operation.
 *
 * @async
 * @param {Event} e The submit event triggered by the form or button.
 * 
 * @returns {Promise<void>} Resolves when the task completion process is finished.
 */
async function submit(e) {
    e.preventDefault()

    Loader.patch(completeTaskButton.querySelector('.text-w-icon'))

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
        handleException(error, `Error completing task: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Marks a task as completed by sending a PUT request to the backend.
 *
 * This function performs the following actions:
 * - Validates that projectId, phaseId, and taskId are provided.
 * - Prevents concurrent requests using an isLoading flag.
 * - Sends a PUT request to update the task status to 'completed'.
 * - Handles errors and ensures isLoading is reset after completion.
 *
 * @param {string} projectId - The unique identifier of the project.
 * @param {string} phaseId - The unique identifier of the phase within the project.
 * @param {string} taskId - The unique identifier of the task to be marked as completed.
 * 
 * @throws {Error} If any of the required parameters are missing or if the request fails.
 * @returns {Promise<void>} Resolves when the request completes successfully.
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

        const response = await Http.PUT(`projects/${projectId}/phases/${phaseId}/tasks/${taskId}`, { status: 'completed' })
        if (!response) {
            throw error
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}