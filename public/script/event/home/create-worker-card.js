import { Dialog } from '../../render/dialog.js'
import { userInfoCard } from '../../render/user-card.js'
import { Http } from '../../utility/http.js'

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
                userInfoCard(workerId)
            } catch (error) {
                console.error(`Error fetching worker info: ${error.message}`)
                if (error?.status === 401 || error?.status === 403) {
                    const message = error.errorData.message || 'You do not have permission to perform this action.'
                    Dialog.errorOccurred(message)
                } else {
                    Dialog.somethingWentWrong()
                }
            }
        })
    }
} else {
    console.error('Worker list container not found!')
    Dialog.somethingWentWrong()
}