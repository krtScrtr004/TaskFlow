import { Http } from '../../utility/http.js'
import { debounceAsync } from '../../utility/debounce.js'
import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'

let isLoading = false
const viewTaskInfo = document.querySelector('.view-task-info')
const cancelTaskButton = viewTaskInfo.querySelector('#cancel_task_button')

if (cancelTaskButton) {
    cancelTaskButton.addEventListener('click', e => debounceAsync(submit(e), 3000))
} else {
    console.error('Cancel Task button not found.')
}

async function submit(e) {
    e.preventDefault()

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
        window.location.reload()
    } catch (error) {
        console.error('Error cancelling task:', error)
        Dialog.errorOccurred('Error cancelling task')
    } finally {
        Loader.delete()
    }
}

async function sendToBackend(projectId, taskId) {
    isLoading = true
    try {
        if (isLoading) {
            console.warn('Request is already in progress. Please wait.')
            return
        }

        if (!projectId) 
            throw new Error('Project ID is required.')

        if (!taskId)
            throw new Error('Task ID is required.')

        const response = await Http.PUT(`/project/${projectId}/task/${taskId}`, { status: 'cancelled' })
        if (!response) 
            throw new Error('No response from server.')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}