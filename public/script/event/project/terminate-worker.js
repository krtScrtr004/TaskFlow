import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'

let isLoading = false
async function sendToBackend(projectId, workerIds) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST('terminate-worker', { projectId, workerIds })
    if (!response) {
        throw new Error('Failed to terminate worker from project.')
    }

    isLoading = false
}

const workerInfoCardTemplate = document.querySelector('#worker_info_card_template')
if (workerInfoCardTemplate) {
    let targetWorker = null
    const workerList = document.querySelector('.project-workers > .worker-list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
    } else {
        workerList.addEventListener('click', e => {
            targetWorker = e.target.closest('.worker-list-card')
        })

        const terminateWorkerButton = workerInfoCardTemplate.querySelector('#terminate_worker_button')
        terminateWorkerButton.addEventListener('click', async () => {
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
                await sendToBackend(projectId, [workerId])
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
        })
    }
} else {
    console.error('Worker info card template not found.')
    Dialog.somethingWentWrong()
}