import { Dialog } from '../../render/dialog.js'
import { terminateWorker } from '../../utility/terminate-worker.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { handleException } from '../../utility/handle-exception.js'

try {
    const viewTaskInfo = document.querySelector('.view-task-info')
    const projectId = viewTaskInfo?.dataset.projectid
    if (!projectId) throw new Error('Project ID not found.')

    const workerContainer = viewTaskInfo?.querySelector('.worker-grid')
    terminateWorker(projectId, workerContainer, '.user-grid-card')
} catch (error) {
    handleException(error, 'Error terminating worker:', error)
}