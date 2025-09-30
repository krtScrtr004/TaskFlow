import { Dialog } from '../../../render/dialog.js'
import { Notification } from '../../../render/notification.js'

const forgetPasswordForm = document.querySelector('#forget_password_form')
if (forgetPasswordForm) {
    let isLoading = false

    function sendLink() {
        if (isLoading) return
        isLoading = true

        const email = forgetPasswordForm.querySelector('#email')
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
            Dialog.sendLink(false)
            console.error(error)
        } finally {
            isLoading = false
        }

    }

    const sendLinkButton = forgetPasswordForm.querySelector('#send_link_button')
    sendLinkButton.addEventListener('click', sendLink)
    forgetPasswordForm.addEventListener('submit', e => { e.preventDefault(); sendLink(); })
}