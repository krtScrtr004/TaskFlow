import { terminateWorker } from '../../utility/terminate-worker.js'
import { Dialog } from '../../render/dialog.js'

try {
    const viewTaskInfo = document.querySelector('.view-task-info')
    const projectId = viewTaskInfo?.dataset.projectid
    if (!projectId) throw new Error('Project ID not found.')

    const workerContainer = viewTaskInfo?.querySelector('.worker-grid')
    terminateWorker(projectId, workerContainer, '.worker-grid-card')
} catch (error) {
    console.error(error.message)
    if (error?.status === 401 || error?.status === 403) {
        const message = error.errorData.message || 'You do not have permission to perform this action.'
        Dialog.errorOccurred(message)
    } else {
        Dialog.errorOccurred('An error occurred while terminating the worker. Please try again.')
    }
}