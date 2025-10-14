import { userInfoCard } from '../../render/user-card.js'
import { Dialog } from '../../render/dialog.js'

const viewTaskInfo = document.querySelector('.view-task-info')
const workerGrid = viewTaskInfo.querySelector('.worker-grid')
if (workerGrid) {
    workerGrid.addEventListener('click', async e => {
        const workerCard = e.target.closest('.worker-grid-card')
        if (!workerCard) return

            const projectId = viewTaskInfo.dataset.projectid
            if (!projectId || projectId.trim() === '') {
                console.error('Project ID is missing.')
                Dialog.somethingWentWrong()
                return
            }

            const workerId = workerCard.dataset.userid
            if (!workerId || workerId.trim() === '') {
                console.error('Worker ID is missing.')
                Dialog.somethingWentWrong()
                return
            }

            try {
            userInfoCard(workerId)
        } catch (error) {
            console.error(`Error fetching worker info: ${error.message}`)
        }
    })
} else {
    console.error('Workers grid not found!')
}
    