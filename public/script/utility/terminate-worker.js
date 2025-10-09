import { Http } from './http.js'
import { Dialog } from '../render/dialog.js'
import { debounceAsync } from './debounce.js'
import { confirmationDialog } from '../render/confirmation-dialog.js'

let isLoading = false
let targetWorker = null
const workerInfoCardTemplate = document.querySelector('#worker_info_card_template')
let thisWorkerContainer = null

export function terminateWorker(projectId, workerContainer, workerCardSelector) {
    if (!workerContainer)
        throw new Error('Worker container element is required.')

    thisWorkerContainer = workerContainer

    const workerCard = workerContainer.querySelector(workerCardSelector)
    if (!workerCard)
        throw new Error('Worker card element is required.')

    workerContainer.addEventListener('click', e => {
        targetWorker = e.target.closest(workerCardSelector)
    })

    const terminateWorkerButton = workerInfoCardTemplate.querySelector('#terminate_worker_button')
    if (!terminateWorkerButton)
        throw new Error('Terminate Worker button not found.')
    terminateWorkerButton.addEventListener('click', e => debounceAsync(terminateButtonEvent(e, projectId, workerCardSelector), 300))
}

async function terminateButtonEvent(e, projectId, workerCardSelector) {
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

        // TODO
        const remainingWorkerCard = thisWorkerContainer.querySelectorAll(workerCardSelector)
        if (remainingWorkerCard.length === 0) {
            const noWorkersWall = thisWorkerContainer.querySelector('.no-workers-wall')
                || thisWorkerContainer.parentElement?.querySelector('.no-workers-wall')
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')            
        }
        const closeButton = workerInfoCardTemplate.querySelector('#worker_info_card_close_button')
        closeButton?.click()
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