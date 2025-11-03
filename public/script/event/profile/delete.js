import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const profile = document.querySelector('.profile')

const deleteMyAccountButton = profile?.querySelector('#delete_my_account_button')
if (!deleteMyAccountButton) {
    console.error('Delete My Account button not found.')
    Dialog.somethingWentWrong()
}

deleteMyAccountButton?.addEventListener('click', e => debounceAsync(submit(e), 300))

/**
 * Handles the submission of the account deletion form.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Displays a confirmation dialog to the user, warning about the irreversibility of account deletion.
 * - If the user confirms, sends a request to the backend to delete the account.
 * - On successful deletion, shows a success dialog and redirects the user to the login page after a short delay.
 * - If an error occurs during the process, handles the exception and displays an error message.
 *
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the operation is complete.
 */
async function submit(e) {
    e.preventDefault()

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Confirm Delete Account',
        'Are you sure you want to delete your account? This action cannot be undone.'
    )) return

    try {
        await sendToBackend()
        Dialog.operationSuccess(
            'Account Deleted',
            'Your account has been successfully deleted. You will be redirected to the login page.'
        )
        setTimeout(() => window.location.href = '/TaskFlow/login', 1500)
    } catch (error) {
        handleException(error, `Error during account deletion: ${error}`)
    }
}

/**
 * Sends a DELETE request to the backend to remove the current user's profile.
 *
 * This function checks if a request is already in progress using the `isLoading` flag.
 * It retrieves the current user's ID from the `profile` element's `myid` dataset attribute.
 * If the user ID is not found or is empty, it throws an error.
 * Upon successful retrieval of the user ID, it sends an HTTP DELETE request to the backend endpoint `users/{myId}`.
 * The loading state is managed to prevent concurrent requests.
 *
 * @async
 * @function
 * @throws {Error} If the user ID is not found or if the HTTP request fails.
 * @returns {Promise<void>} Resolves when the request completes successfully.
 */
async function sendToBackend() {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        const myId = profile?.dataset.myid
        if (!myId || myId.trim() === '') {
            throw new Error('User ID not found.')
        }

        await Http.DELETE(`users/${myId}`)
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
