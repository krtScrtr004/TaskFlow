import { userInfoCard } from '../../render/user-card.js'
import { Dialog } from '../../render/dialog.js'
import { Http } from '../../utility/http.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const viewTaskInfo = document.querySelector('.view-task-info')
const workerTable = viewTaskInfo.querySelector('.worker-table')
if (!workerTable) {
    console.error('Workers grid not found!')
}

workerTable?.addEventListener('click', async e => {
    const userRow = e.target.closest('.user-table-row')
    if (!userRow) {
        return
    }

    const projectId = viewTaskInfo.dataset.projectid
    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    const workerId = userRow.dataset.userid
    if (!workerId || workerId.trim() === '') {
        console.error('Worker ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        // Render user info card
        userInfoCard(workerId, () => fetchUserInfo(projectId, workerId))
    } catch (error) {
        handleException(error, 'Error displaying user info card:', error)
    }
})

/**
 * Fetches user information for a specific worker in a project.
 *
 * This asynchronous function retrieves detailed information about a user (worker)
 * associated with a given project by making an HTTP GET request. It prevents
 * concurrent requests using an `isLoading` flag and validates the provided user ID.
 *
 * @param {string|number} projectId - The unique identifier of the project.
 * @param {string|number} userId - The unique identifier of the user (worker) to fetch.
 * @returns {Promise<Object>} Resolves to the user data object if successful.
 * @throws {Error} Throws an error if the user ID is missing, the request fails, or the response is invalid.
 */
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