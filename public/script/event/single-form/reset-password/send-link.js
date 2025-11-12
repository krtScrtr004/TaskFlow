import { Dialog } from '../../../render/dialog.js'
import { Notification } from '../../../render/notification.js'
import { debounceAsync } from '../../../utility/debounce.js'
import { Http } from '../../../utility/http.js'
import { Loader } from '../../../render/loader.js'

let isLoading = false

const resetPasswordForm = document.querySelector('#reset_password_form')
if (!resetPasswordForm) {
    console.error('Reset Password form not found.')
    Dialog.somethingWentWrong()
}

const sendLinkButton = resetPasswordForm.querySelector('#send_link_button')
sendLinkButton?.addEventListener('click', sendLink)

resetPasswordForm?.addEventListener('submit', e => debounceAsync(sendLink(e), 300))

async function sendLink(e) {
    e.preventDefault()

    if (isLoading) {
        console.warn('Request already in progress. Please wait.')
        return
    }
    isLoading = true

    Loader.patch(sendLinkButton.firstChild)

    const email = resetPasswordForm.querySelector('#email')
    if (email.value.trim() === '') {
        Notification.error(
            'Please enter your email address.',
            3000,
            document.querySelector('body')
        )
        isLoading = false
        return
    }

    try {
        const response = await Http.POST('auth/reset-password', { email: email.value.trim() })
        if (!response) {
            throw new Error('No response from server.')
        }

        Dialog.sendLink(true)
    } catch (error) {
        console.error('Error sending password reset link:', error)
        if (error?.status === 422) {
            Dialog.errorOccurred('Invalid email address provided.')
        } else {
            Dialog.sendLink(false)
        }
    } finally {
        Loader.delete()
        isLoading = false
    }
}