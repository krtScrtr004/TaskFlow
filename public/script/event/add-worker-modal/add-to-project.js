import { addWorker } from './shared.js'
import { Http } from '../../utility/http.js'

let isLoading = false

// Just add workers with default behavior
await addWorker(async (projectId, workerIds) => await sendToBackend(projectId, workerIds))

async function sendToBackend(projectId, workerIds) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST(`projects/${projectId}/workers`, { workerIds })
    if (!response) {
        throw new Error('Failed to add workers to project.')
    }

    isLoading = false
}