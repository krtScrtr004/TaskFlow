import { workerInfoCard } from '../../render/worker-card.js'
import { Dialog } from '../../render/dialog.js'

const viewTaskInfo = document.querySelector('.view-task-info')
const workersGrid = viewTaskInfo.querySelector('.workers-grid')
if (workersGrid) {
    workersGrid.addEventListener('click', async e => {
        const workerCard = e.target.closest('.worker-grid-card')
        if (!workerCard) return

        const projectId = viewTaskInfo.dataset.projectid
        if (!projectId || projectId.trim() === '') {
            console.error('Project ID is missing.')
            Dialog.somethingWentWrong()
            return
        }

        const workerId = workerCard.dataset.workerid
        if (!workerId || workerId.trim() === '') {
            console.error('Worker ID is missing.')
            Dialog.somethingWentWrong()
            return
        }

        try {
            workerInfoCard(projectId, workerId)
        } catch (error) {
            console.error(`Error fetching worker info: ${error.message}`)
        }
    })
} else {
    console.error('Workers grid not found!')
}