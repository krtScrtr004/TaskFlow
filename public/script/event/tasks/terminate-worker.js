import { terminateWorker } from '../../utility/terminate-worker.js'
import { errorListDialog } from '../../render/error-list-dialog.js'

try {
    const viewTaskInfo = document.querySelector('.view-task-info')
    const projectId = viewTaskInfo?.dataset.projectid
    if (!projectId) throw new Error('Project ID not found.')

    const workerContainer = viewTaskInfo?.querySelector('.worker-grid')
    terminateWorker(projectId, workerContainer, '.worker-grid-card')
} catch (error) {
    console.error('Error terminating worker:', error)
    errorListDialog(error?.errors, error?.message)
}