import { userInfoCard } from '../../Render/UserCard.js'
import { handleException } from '../../Utility/HandleException.js'
import { Http } from '../../Utility/Http.js'
import { die } from '../../Utility/Utility.js'

let isLoading = false

const managerContainer = document.querySelector('.project-manager')
if (!managerContainer) die('Manager container not found')

managerContainer?.addEventListener('click', e => {
    const managerCard = e.target.closest('.user-list-card')
    if (!managerCard) return

    const managerId = managerCard.getAttribute('data-id')
    try {
        userInfoCard(managerId, () => fetchManagerInfo(managerId))
    } catch (error) {
        handleException(error)
    }
})

const workerList = document.querySelector('.project-workers > .worker-list')
if (!workerList) die('Worker list container not found')

const projectContainer = document.querySelector('.project-container')
const projectId = projectContainer.dataset.projectid
if (!projectId || projectId.trim() === '') die('Project ID is missing')

workerList?.addEventListener('click', e => {
    const workerCard = e.target.closest('.user-list-card')
    if (!workerCard) return

    const workerId = workerCard.getAttribute('data-id')
    try {
        userInfoCard(workerId, () => fetchWorkerInfo(projectId, workerId))
    } catch (error) {
        handleException(error)
    }
})

/**
 * Fetches user information for a specific worker in a project.
 */
async function fetchWorkerInfo(projectId, userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '') throw new Error('User ID is required')

        const response = await Http.GET(`projects/${projectId}/workers/${userId}`)
        if (!response) throw new Error('No response from server')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

/**
 * Fetches user information for a manager.
 */
async function fetchManagerInfo(userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '') throw new Error('User ID is required')

        const response = await Http.GET(`projects/${projectId}/manager`)
        if (!response) throw new Error('No response from server')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}