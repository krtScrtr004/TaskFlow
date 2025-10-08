import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'

let isLoading = false
let targetWorker = null

const workerInfoCardTemplate = document.querySelector('#worker_info_card_template')
const workerList = document.querySelector('.project-workers > .worker-list')
if (workerList) {
    workerList.addEventListener('click', e => {
        targetWorker = e.target.closest('.worker-list-card')
    })

    const terminateWorkerButton = workerInfoCardTemplate.querySelector('#terminate_worker_button')
    if (!terminateWorkerButton) {
        console.error('Terminate Worker button not found.')
        Dialog.somethingWentWrong()
    } else {
        terminateWorkerButton.addEventListener('click', e => debounceAsync(terminateButtonEvent(e), 300))
    }
} else {
    console.error('Worker list container not found.')
    Dialog.somethingWentWrong()
}

async function terminateButtonEvent(e) {
    e.preventDefault()

    const workerId = workerInfoCardTemplate.dataset.workerid
    if (!workerId) {
        console.error('Worker ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    if (!await confirmationDialog(
        'Terminate Worker',
        `Are you sure you want to terminate this worker?`,
    )) return

    const addWorkerButton = document.querySelector('#add_worker_button')
    const projectId = addWorkerButton.dataset.projectid
    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(projectId, workerId)
        Dialog.operationSuccess(
            'Worker terminated.',
            'The worker has been successfully terminated from the project.'
        )
        targetWorker.remove()
        const remainingWorkerListCard = workerList.querySelectorAll('.worker-list-card')
        if (remainingWorkerListCard.length === 0) {
            const noWorkersWall = workerList.querySelector('.no-workers-wall')
            noWorkersWall.classList.remove('no-display')
            noWorkersWall.classList.add('flex-col')
        }

        const closeButton = workerInfoCardTemplate.querySelector('#worker_info_card_close_button')
        closeButton.click()
    } catch (error) {
        console.error(error)
        Dialog.errorOccurred('An error occurred while terminating the worker. Please try again.')
    }
}

async function sendToBackend(projectId, workerId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        if (!workerId || workerId.trim() === '')
            throw new Error('Worker ID is required.')

        const response = await Http.PUT(`projects/${projectId}/workers/${workerId}`, { status: 'terminated' })
        if (!response) {
            throw new Error('Failed to terminate worker from project.')
        }
    } catch (error) {
        console.error('Error terminating worker:', error)
        throw error
    } finally {
        isLoading = false
    }
}