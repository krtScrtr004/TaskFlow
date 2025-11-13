import { terminateWorker } from '../../utility/terminate-worker.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

(() => {
    const terminateWorkerButton = document.querySelector('#terminate_worker_button')
    if (!terminateWorkerButton) {
        return
    }

    try {
        const projectContainer = document.querySelector('.project-container')
        const projectId = projectContainer?.dataset.projectid
        if (!projectId) {
            throw new Error('Project ID not found.')
        }

        const endpoint = `projects/${projectId}`
        const workerContainer = projectContainer?.querySelector('.worker-list')
        // Initialize terminate worker functionality
        terminateWorker(projectId, workerContainer, '.user-list-card', endpoint)
    } catch (error) {
        handleException(error, 'Error initializing terminate worker functionality:', error)
    }
})()

