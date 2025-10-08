import { addWorker } from '../shared.js'
import { Http } from '../../../utility/http.js'
import { Dialog } from '../../../render/dialog.js'

let isLoading = false
const projectContainer = document.querySelector('.project-container')
const thisProjectId = projectContainer ? projectContainer.dataset.projectid : null
if (!thisProjectId || thisProjectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

// Just add workers with default behavior
await addWorker(async (projectId, workerIds) => await sendToBackend(projectId, workerIds))

async function sendToBackend(projectId, workerIds) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        if (!workerIds || workerIds.length === 0)
            throw new Error('No worker IDs provided.')

        const response = await Http.POST(`projects/${projectId}/workers`, { workerIds })
        if (!response)
            throw new Error('Failed to add workers to project.')

        return response
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}