import { Dialog } from '../../render/dialog.js'
import { terminateWorker } from '../../utility/terminate-worker.js'
import { handleException } from '../../utility/handle-exception.js'

try {
    const viewTaskInfo = document.querySelector('.view-task-info')
    
    const projectId = viewTaskInfo?.dataset.projectid
    if (!projectId) {
        throw new Error('Project ID not found.')
    }

    const taskId = viewTaskInfo?.dataset.taskid
    if (!taskId) {
        throw new Error('Task ID not found.')
    }

    const endpoint = `projects/${projectId}/tasks/${taskId}`
    const workerContainer = viewTaskInfo?.querySelector('.worker-grid')
    // Initialize worker termination functionality
    terminateWorker(projectId, workerContainer, '.user-grid-card', endpoint)
} catch (error) {
    handleException(error, 'Error terminating worker:', error)
}