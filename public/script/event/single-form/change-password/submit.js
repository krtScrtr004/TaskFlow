import { confirmationDialog } from "../../../render/confirmation-dialog.js"
import { Dialog } from "../../../render/dialog.js"
import { Loader } from "../../../render/loader.js"
import { debounceAsync } from "../../../utility/debounce.js"
import { handleException } from "../../../utility/handle-exception.js"
import { Http } from "../../../utility/http.js"

let isLoading = false

const changePasswordForm = document.querySelector('#change_password_form')
if (!changePasswordForm) {
    console.warn('Change Password form not found.')
}

changePasswordForm?.addEventListener('submit', e => debounceAsync(submit(e), 300))

const changePasswordButton = changePasswordForm?.querySelector('#change_password_button')
if (!changePasswordButton) {
    console.warn('Change Password button not found.')
}

changePasswordButton?.addEventListener('click', e => debounceAsync(submit(e), 300))

/**
 * Handles the password change form submission event.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Checks if a password change request is already in progress.
 * - Displays a confirmation dialog to the user.
 * - Shows a loading indicator while processing the request.
 * - Sends a POST request to change the user's password.
 * - Displays a success dialog and redirects to the login page upon success.
 * - Handles any errors that occur during the process.
 * - Cleans up the loading indicator and resets the loading state.
 *
 * @param {Event} e The form submission event.
 * @returns {Promise<void>} Resolves when the password change process is complete.
 */
async function submit(e) {
    e.preventDefault()

    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }

    Loader.patch(changePasswordButton.firstChild)

    // Show confirmation dialog
    if (!await confirmationDialog(
        'Confirm Password Change',
        'Are you sure you want to change your password?'
    )) return

    try {
        const newPasswordInput = changePasswordForm?.querySelector('#password').value
        await Http.POST('auth/change-password', { 
            token: new URLSearchParams(window.location.search).get('token'),
            password: newPasswordInput 
        })

        Dialog.operationSuccess('Password Changed', 'Your password has been successfully changed.')
        setTimeout(() => window.location.href = '/TaskFlow/login', 1500)
    } catch (error) {
        handleException(error)
    } finally {
        isLoading = false
        Loader.delete()
    }
}

