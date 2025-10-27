import { terminateWorker } from '../../utility/terminate-worker.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

try {
    const projectContainer = document.querySelector('.project-container')
    const projectId = projectContainer?.dataset.projectid
    if (!projectId)
        throw new Error('Project ID not found.')

    const workerContainer = projectContainer?.querySelector('.worker-list')
    if (workerContainer.querySelectorAll('.worker-list-card').length > 0) {
        terminateWorker(projectId, workerContainer, '.worker-list-card')
    }
} catch (error) {
    handleException(error, 'Error initializing terminate worker functionality:', error)
}