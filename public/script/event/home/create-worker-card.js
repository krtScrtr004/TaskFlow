import { Dialog } from '../../render/dialog.js'
import { userInfoCard } from '../../render/user-card.js'
import { handleException } from '../../utility/handle-exception.js'
import { Http } from '../../utility/http.js'

let isLoading = false

const workerList = document.querySelector('.project-workers > .worker-list')
if (!workerList) {
    console.error('Worker list container not found!')
    Dialog.somethingWentWrong()
}

const projectContainer = document.querySelector('.project-container')
const projectId = projectContainer.dataset.projectid
if (!projectId || projectId.trim() === '') {
    console.error('Project ID is missing.')
    Dialog.somethingWentWrong()
} 

workerList?.addEventListener('click', e => {
    const workerCard = e.target.closest('.worker-list-card')
    if (!workerCard) { 
        return
    }

    const workerId = workerCard.getAttribute('data-id')
    try {
        // Render user info card
        userInfoCard(workerId, () => fetchUserInfo(projectId, workerId))
    } catch (error) {
        handleException(error, `Error fetching worker info: ${error}`)
    }
})


/**
 * Fetches user information for a specific worker in a project.
 *
 * This asynchronous function retrieves user data from the server using the provided
 * project and user IDs. It prevents concurrent requests by checking a loading flag,
 * and throws an error if the user ID is missing or invalid.
 *
 * @param {string|number} projectId - The unique identifier of the project.
 * @param {string|number} userId - The unique identifier of the user (worker) to fetch.
 * @returns {Promise<Object>} Resolves with the user data object if the request is successful.
 * @throws {Error} Throws if the userId is missing, if the request fails, or if a request is already in progress.
 */
async function fetchUserInfo(projectId, userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '')
            throw new Error('User ID is required.')

        const response = await Http.GET(`projects/${projectId}/workers/${userId}`)
        if (!response)
            throw error

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}