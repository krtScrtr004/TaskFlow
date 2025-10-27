import { userInfoCard } from '../../render/user-card.js'
import { Dialog } from '../../render/dialog.js'
import { Http } from '../../utility/http.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const viewTaskInfo = document.querySelector('.view-task-info')
const workerGrid = viewTaskInfo.querySelector('.worker-grid')
if (workerGrid) {
    workerGrid.addEventListener('click', async e => {
        const workerCard = e.target.closest('.user-grid-card')
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
            userInfoCard(workerId, () => fetchUserInfo(projectId, workerId))
        } catch (error) {
            handleException(error, 'Error displaying user info card:', error)
        }
    })
} else {
    console.error('Workers grid not found!')
}

async function fetchUserInfo(projectId, userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '') {
            throw new Error('User ID is required.')
        }

        const response = await Http.GET(`projects/${projectId}/workers/${userId}`)
        if (!response) {
            throw error
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}