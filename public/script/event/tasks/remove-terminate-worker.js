import { removeTerminateWorker } from '../../utility/remove-terminate-worker.js'
import { handleException } from '../../utility/handle-exception.js'

(() => {
    const removeWorkerButton = document.querySelector('#remove_worker_button')
    if (!removeWorkerButton) {
        return
    }

    const terminateWorkerButton = document.querySelector('#terminate_worker_button')
    if (!terminateWorkerButton) {
        return
    }

    try {
        const viewTaskInfo = document.querySelector('.view-task-info')
        
        const projectId = viewTaskInfo?.dataset.projectid
        if (!projectId) {
            throw new Error('Project ID not found.')
        }

        const phaseId = viewTaskInfo?.dataset.phaseid
        if (!phaseId) {
            throw new Error('Phase ID not found.')
        }

        const taskId = viewTaskInfo?.dataset.taskid
        if (!taskId) {
            throw new Error('Task ID not found.')
        }

        const endpoint = `projects/${projectId}/phases/${phaseId}/tasks/${taskId}`
        const workerContainer = viewTaskInfo?.querySelector('.worker-table')
        // Initialize worker termination functionality
        removeTerminateWorker(projectId, workerContainer, '.user-table-row', endpoint)
    } catch (error) {
        handleException(error, 'Error terminating worker:', error)
    }
})()

