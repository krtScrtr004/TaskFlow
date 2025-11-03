import { Http } from './http.js'
import { Dialog } from '../render/dialog.js'
import { debounceAsync } from './debounce.js'
import { confirmationDialog } from '../render/confirmation-dialog.js'

let isLoading = false
let targetUser = null
let thisUserContainer = null
let endpoint = ''

const userInfoCardTemplate = document.querySelector('#user_info_card_template')

/**
 * Attaches event listeners to handle terminating a worker in a project context.
 *
 * This function sets up click event listeners on a user container and a terminate button,
 * allowing the termination of a user (worker) from a project. It expects the presence of
 * a user card selector and a terminate button within a user info card template.
 *
 * @param {string|number} projectId The unique identifier of the project.
 * @param {HTMLElement} userContainer The DOM element containing user cards.
 * @param {string} userCardSelector CSS selector string for user card elements.
 * @param {string} localEndpoint The API endpoint for termination requests.
 *
 * @throws {Error} If the terminate user button cannot be found in the template.
 */
export function terminateWorker(projectId, userContainer, userCardSelector, localEndpoint) {
    if (!userContainer) {
        return
    }
    thisUserContainer = userContainer
    endpoint = localEndpoint

    userContainer.addEventListener('click', e => {
        targetUser = e.target.closest(userCardSelector)
    })

    const terminateUserButton = userInfoCardTemplate.querySelector('#terminate_worker_button')
    if (!terminateUserButton) {
        throw new Error('Terminate user button not found.')
    }
    terminateUserButton.addEventListener('click', e => debounceAsync(terminateButtonEvent(e, projectId, userCardSelector), 300))
}

/**
 * Handles the termination of a user (worker) from a project when the terminate button is clicked.
 *
 * This function performs the following steps:
 * - Prevents the default button event.
 * - Retrieves the user ID from the user info card template.
 * - Displays a confirmation dialog to the user.
 * - Validates the presence of the project ID.
 * - Sends a termination request to the backend.
 * - Shows a success dialog upon successful termination.
 * - Removes the terminated user's card from the UI.
 * - If no worker cards remain, displays a "no workers" message.
 * - Closes the user info card modal.
 *
 * @async
 * @param {Event} e The event object from the terminate button click.
 * @param {string} projectId The ID of the project from which the user is being terminated.
 * @param {string} userCardSelector CSS selector string for locating user card elements in the DOM.
 * 
 * @throws Will re-throw any error encountered during the termination process.
 */
async function terminateButtonEvent(e, projectId, userCardSelector) {
    e.preventDefault()

    const userId = userInfoCardTemplate.dataset.userid
    if (!userId) {
        console.error('User ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Terminate user',
        `Are you sure you want to terminate this user?`,
    )) return

    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(projectId, userId)
        Dialog.operationSuccess(
            'User Terminated.',
            'The user has been successfully terminated.'
        )

        // Remove worker card from UI
        targetUser.remove()

        const remainingWorkerCard = thisUserContainer.querySelectorAll(userCardSelector)
        // If no workers remain, show the no workers wall
        if (remainingWorkerCard.length === 0) {
            const noWorkersWall = thisUserContainer.querySelector('.no-workers-wall')
                || thisUserContainer.parentElement?.querySelector('.no-workers-wall')
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')
        }

        const closeButton = userInfoCardTemplate.querySelector('#user_info_card_close_button')
        closeButton?.click()
    } catch (error) {
        throw error
    }
}

/**
 * Sends a request to the backend to terminate a worker from a project.
 *
 * This function performs the following:
 * - Validates that both projectId and userId are provided and non-empty strings.
 * - Prevents concurrent requests by checking and setting an isLoading flag.
 * - Sends a PATCH request to the backend to update the worker's status to 'terminated'.
 * - Handles errors and ensures the loading state is reset after the operation.
 *
 * @param {string} projectId The unique identifier of the project.
 * @param {string} userId The unique identifier of the user (worker) to be terminated.
 * 
 * @throws {Error} If projectId or userId is missing or empty, or if the backend request fails.
 * 
 * @returns {Promise<void>} Resolves when the operation is complete.
 */
async function sendToBackend(projectId, userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        if (!userId || userId.trim() === '') {
            throw new Error('User ID is required.')
        }

        const response = await Http.PATCH(`${endpoint}/workers/${userId}`, { status: 'terminated' })
        if (!response) {
            throw new Error('Failed to terminate worker from project.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}