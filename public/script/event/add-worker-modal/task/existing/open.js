import { Loader } from '../../../../render/loader.js'
import { Dialog } from '../../../../render/dialog.js'
import { handleException } from '../../../../utility/handle-exception.js'
import { fetchWorkers } from '../../fetch.js'
import { createWorkerListCard } from '../../render.js'
import { selectWorker } from '../../select.js'
import { toggleNoWorkerWall } from '../../modal.js'
import { initializeAddWorkerModal } from '../../modal.js'

const viewTaskInfo = document.querySelector('.view-task-info')
const addWorkerButton = viewTaskInfo?.querySelector('#add_worker_button')
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

const projectId = viewTaskInfo.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.error('Project ID not found.')
    Dialog.somethingWentWrong()
}

const phaseId = viewTaskInfo.dataset.phaseid
if (!phaseId || phaseId.trim() === '') {
    console.error('Phase ID not found.')
    Dialog.somethingWentWrong()
}

const taskId = viewTaskInfo.dataset.taskid
if (!taskId || taskId.trim() === '') {
    console.error('Task ID not found.')
    Dialog.somethingWentWrong()
}

if (addWorkerModalTemplate) {
    addWorkerButton?.addEventListener('click', async () => {
        // Prepare URL parameters to fetch only unassigned and non-terminated workers
        const params = new URLSearchParams()
        params.append('status', 'unassigned')
        params.append('excludeTaskTerminated', true)

        const endpoint = `projects/${projectId}/phases/${phaseId}/tasks/${taskId}/workers?${params.toString()}`

        // Initialize the add worker modal with the current project and endpoint
        initializeAddWorkerModal(projectId, endpoint)

        // Show the modal
        addWorkerModalTemplate.classList.add('flex-col')
        addWorkerModalTemplate.classList.remove('no-display')

        try {
            const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
            Loader.full(workerList)

            if (!projectId || projectId.trim() === '') {
                throw new Error('Project ID is missing.')
            }

            // Fetch the list of eligible workers from the server
            const workers = await fetchWorkers(endpoint)
            // If no workers are found, show a "no workers" message and exit
            if (workers.length === 0) {
                toggleNoWorkerWall(true, addWorkerModalTemplate)
                return
            }

            // For each worker, render their card in the worker list
            workers.forEach(worker => createWorkerListCard(worker))
            // Enable selection functionality for the worker cards
            selectWorker()
        } catch (error) {
            handleException(error, `Error loading workers: ${error}`)
        } finally {
            Loader.delete()
        }
    })
} else {
    console.error('Add Worker modal template not found.')
}
