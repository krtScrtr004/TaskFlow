import { Http } from './http.js'
import { Dialog } from '../render/dialog.js'
import { Loader } from '../render/loader.js'
import { debounceAsync } from './debounce.js'
import { confirmationDialog } from '../render/confirmation-dialog.js'

let isLoading = false
let targetUser = null
let thisUserContainer = null
let endpoint = ''

let removeUserButton = null
let terminateUserButton = null

const userInfoCardTemplate = document.querySelector('#user_info_card_template')

/**
 * Attaches event handling to enable removing or terminating a worker within a project UI.
 *
 * This function wires up click listeners for worker cards and the associated action buttons.
 * It:
 * - Stores provided container and endpoint into module-level variables (thisUserContainer, endpoint)
 * - Listens for clicks inside the provided userContainer to determine the currently targeted user card
 *   (stores the matched element in the module-level variable `targetUser`)
 * - Locates the remove and terminate buttons inside an assumed `userInfoCardTemplate` and attaches
 *   debounced handlers to them so the corresponding async event handlers are invoked with a 300ms debounce
 *
 * Notes:
 * - The function expects a variable named `userInfoCardTemplate` to be available in the same scope and
 *   that it contains elements with IDs `#remove_worker_button` and `#terminate_worker_button`.
 * - Debouncing is performed using a `debounceAsync` utility; the callbacks invoked are the results of
 *   `removeButtonEvent(e, projectId, userCardSelector)` and `terminateButtonEvent(e, projectId, userCardSelector)`.
 * - Side effects: assigns to module-level variables `thisUserContainer`, `endpoint`, and `targetUser`.
 *
 * @param {string|number} projectId Identifier of the project the worker belongs to.
 * @param {Element|null} userContainer DOM element that contains user/worker cards. If falsy, the function returns early.
 * @param {string} userCardSelector CSS selector used to locate the individual user card element from click events.
 * @param {string} [localEndpoint] Optional local API endpoint or base URL used for subsequent actions.
 *
 * @throws {Error} If the remove worker button (#remove_worker_button) is not found in userInfoCardTemplate.
 * @throws {Error} If the terminate worker button (#terminate_worker_button) is not found in userInfoCardTemplate.
 *
 * @returns {void}
 */
export function removeTerminateWorker(projectId, userContainer, userCardSelector, localEndpoint) {
    if (!userContainer) {
        return
    }
    thisUserContainer = userContainer
    endpoint = localEndpoint

    userContainer.addEventListener('click', e => {
        targetUser = e.target.closest(userCardSelector)
    })

    removeUserButton = userInfoCardTemplate.querySelector('#remove_worker_button')
    if (!removeUserButton) {
        throw new Error('Remove user button not found.')
    }
    removeUserButton.addEventListener('click', e => debounceAsync(removeButtonEvent(e, projectId, userCardSelector), 300))

    terminateUserButton = userInfoCardTemplate.querySelector('#terminate_worker_button')
    if (!terminateUserButton) {
        throw new Error('Terminate user button not found.')
    }
    terminateUserButton.addEventListener('click', e => debounceAsync(terminateButtonEvent(e, projectId, userCardSelector), 300))
}

/**
 * Handles a "remove user" button click by confirming and performing user removal for a project.
 *
 * Prevents the default event action, obtains the selected user's ID from the global
 * `userInfoCardTemplate.dataset.userid`, prompts the user for confirmation, verifies the
 * presence of a project ID, sends the removal request to the backend, displays success/failure
 * dialogs, and performs local cleanup of the user's card on success.
 *
 * Behavior summary:
 * - Calls e.preventDefault().
 * - Reads userId from `userInfoCardTemplate.dataset.userid`; if missing logs an error and shows `Dialog.somethingWentWrong()`.
 * - Shows a confirmation dialog (`confirmationDialog('Terminate user', ...)`) and aborts if the user cancels.
 * - Verifies `projectId` exists; if missing logs an error and shows `Dialog.somethingWentWrong()`.
 * - Calls `await sendToBackendRemove(projectId, userId)`.
 * - On success, shows `Dialog.operationSuccess(...)` and calls `cleanup(userCardSelector)`.
 *
 * @async
 * @param {Event} e The click/event object (typically a MouseEvent). The function will call e.preventDefault().
 * @param {string|number} projectId The ID of the project from which the user should be removed. Required.
 * @param {string} userCardSelector CSS selector or identifier passed to cleanup() to remove the user's card from the DOM.
 *
 * @returns {Promise<void>} Resolves when the removal flow completes successfully. Rejects if the backend request or other awaited operations fail.
 * 
 * @throws {Error} Re-throws errors thrown by `sendToBackendRemove` or other unexpected runtime errors.
 */
async function removeButtonEvent(e, projectId, userCardSelector) {
    e.preventDefault()

    try {
        Loader.patch(removeUserButton.querySelector('.text-w-icon'))

        const userId = userInfoCardTemplate.dataset.userid
        if (!userId) {
            console.error('User ID not found.')
            Dialog.somethingWentWrong()
            return
        }

        // Show confirmation dialog
        if (!await confirmationDialog(
            'Remove User',
            `Are you sure you want to remove this user?`,
        )) return

        if (!projectId) {
            console.error('Project ID not found in modal dataset.')
            Dialog.somethingWentWrong()
            return
        }

        await sendToBackendRemove(projectId, userId)
        Dialog.operationSuccess(
            'User Removed.',
            'The user has been successfully removed.'
        )

        cleanup(userCardSelector)
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
    }
}

/**
 * Sends a request to the backend to remove a worker (user) from a project.
 *
 * This method:
 * - Guards against concurrent requests using a shared `isLoading` flag (logs a warning and returns early if a request is already in progress).
 * - Validates that both `projectId` and `userId` are provided and non-empty strings.
 * - Performs an HTTP DELETE to `${endpoint}/workers/${userId}`.
 * - Treats a falsy response as a failure and throws an error.
 * - Ensures the `isLoading` flag is reset in a finally block so the loading state is cleared regardless of success or failure.
 *
 * @param {string} projectId The project identifier. Must be a non-empty string.
 * @param {string} userId The user/worker identifier to remove. Must be a non-empty string.
 *
 * @returns {Promise<void>} A promise that resolves when the removal completes successfully. No response payload is returned.
 *
 * @throws {Error} If `projectId` is missing or an empty string (message: "Project ID is required.").
 * @throws {Error} If `userId` is missing or an empty string (message: "User ID is required.").
 * @throws {Error} If the backend returns a falsy response indicating removal failed (message: "Failed to remove worker from project.").
 * @throws {Error} Rethrows any errors originating from the HTTP layer or other unexpected failures.
 */
async function sendToBackendRemove(projectId, userId) {
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

        const response = await Http.DELETE(`${endpoint}/workers/${userId}`)
        if (!response) {
            throw new Error('Failed to remove worker from project.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
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
 * @param {Event} e The event object from the button click.
 * @param {string} projectId The ID of the project from which the user is being terminated.
 * @param {string} userCardSelector CSS selector string for locating user card elements in the DOM.
 * 
 * @throws Will re-throw any error encountered during the termination process.
 */
async function terminateButtonEvent(e, projectId, userCardSelector) {
    e.preventDefault()

    try {
        Loader.patch(terminateUserButton.querySelector('.text-w-icon'))

        const userId = userInfoCardTemplate.dataset.userid
        if (!userId) {
            console.error('User ID not found.')
            Dialog.somethingWentWrong()
            return
        }

        // Show confirmation dialog
        if (!await confirmationDialog(
            'Terminate User',
            `Are you sure you want to terminate this user?`,
        )) return

        if (!projectId) {
            console.error('Project ID not found in modal dataset.')
            Dialog.somethingWentWrong()
            return
        }

        await sendToBackendTerminate(projectId, userId)
        Dialog.operationSuccess(
            'User Terminated.',
            'The user has been successfully terminated.'
        )

        cleanup(userCardSelector)
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
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
async function sendToBackendTerminate(projectId, userId) {
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

/**
 * Cleans up UI state after removing a worker card.
 *
 * This function updates the DOM to reflect the termination/removal of a worker:
 * - Removes the currently targeted worker card element from the DOM (expects global `targetUser`)
 * - Queries remaining worker cards inside `thisUserContainer` using the provided selector
 * - If no worker cards remain, finds a `.no-workers-wall` (first inside `thisUserContainer`, then in its parent)
 *   and makes it visible by adding `flex-col` and removing `no-display`
 * - Finds the close button inside `userInfoCardTemplate` and triggers a click to close the info card
 *
 * Note: This function relies on the presence of the following globals in scope:
 * - targetUser: Element representing the worker card being removed
 * - thisUserContainer: Element serving as the container for user/worker cards
 * - userInfoCardTemplate: Element containing the user info card template and its close button
 *
 * @param {string} userCardSelector CSS selector used to find remaining user/worker cards within `thisUserContainer`
 * @throws {TypeError} If required globals (`targetUser`, `thisUserContainer`, `userInfoCardTemplate`) are not present or not DOM elements
 * @returns {void}
 */
function cleanup(userCardSelector) {
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
}