import { terminateWorker } from '../../utility/terminate-worker.js'
import { errorListDialog } from '../../render/error-list-dialog.js'

try {
    const projectContainer = document.querySelector('.project-container')
    const projectId = projectContainer?.dataset.projectid
    if (!projectId)
        throw new Error('Project ID not found.')

    const workerContainer = projectContainer?.querySelector('.worker-list')
    terminateWorker(projectId, workerContainer, '.worker-list-card')
} catch (error) {
    console.error(error.message)
    errorListDialog(error?.errors, error?.message)
}