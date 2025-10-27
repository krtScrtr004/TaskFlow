import { Http } from '../../utility/http.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const profile = document.querySelector('.profile')
const deleteMyAccountButton = profile?.querySelector('#delete_my_account_button')
const myId = profile?.dataset.myid

if (deleteMyAccountButton) {
    deleteMyAccountButton.addEventListener('click', e => debounceAsync(submit(e), 300))
} else {
    console.error('Delete My Account button not found.')
    Dialog.somethingWentWrong()
}

async function submit(e) {
    e.preventDefault()

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

async function sendToBackend() {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!myId || myId.trim() === '')
            throw new Error('User ID not found.')

        await Http.DELETE(`users/${myId}`)
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}
