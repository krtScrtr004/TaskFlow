import { Dialog } from '../../render/dialog.js'
import { workerInfoCard } from '../../render/worker-card.js'

let isLoading = false

const workerList = document.querySelector('.project-workers > .worker-list')
if (workerList) {
    const projectContainer = document.querySelector('.project-container')
    const projectId = projectContainer.dataset.projectid
    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
    } else {
        workerList.addEventListener('click', e => {
            const workerCard = e.target.closest('.worker-list-card')
            if (!workerCard) return

            const workerId = workerCard.getAttribute('data-id')
            try {
                workerInfoCard(projectId, workerId)
            } catch (error) {
                console.error(`Error fetching worker info: ${error.message}`)
            }
        })
    }
} else {
    console.error('Worker list container not found!')
    Dialog.somethingWentWrong()
}