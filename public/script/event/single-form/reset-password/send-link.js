import { Dialog } from '../../../render/dialog.js'
import { Notification } from '../../../render/notification.js'

const resetPasswordForm = document.querySelector('#reset_password_form')
if (resetPasswordForm) {
    let isLoading = false

    function sendLink() {
        if (isLoading) return
        isLoading = true

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
            // TODO: Send link

            Dialog.sendLink(true)
        } catch (error) {
            console.error('Error sending password reset link:', error)
            Dialog.sendLink(false)
        } finally {
            isLoading = false
        }

    }

    const sendLinkButton = resetPasswordForm.querySelector('#send_link_button')
    sendLinkButton.addEventListener('click', sendLink)

    resetPasswordForm.addEventListener('submit', e => { e.preventDefault(); sendLink(); })
} else {
    console.error('Reset Password form not found.')
    Dialog.somethingWentWrong()
}