import { hideModal } from '../utility/hide-modal.js'

const ICON_PATH = 'asset/image/icon/'
const icons = ['confirm.svg', 'reject.svg']

function render(
    status,
    id,
    title,
    message,
    parent = document.querySelector('body'),
    statusIcon = status ? icons[0] : icons[1]
) {
    const html = `
        <section id="${id}_wrapper" class="modal-wrapper flex-col">
            <section class="dialog white-bg flex-col">
                <img 
                    src="${ICON_PATH + statusIcon}" 
                    alt="Result icon" 
                    title="Result icon" 
                    height="69" 
                    width="69" />

                    <div>
                        <h1 class="center-text">${title}</h1>
                        <p class="center-text">${message}</p>
                    </div>

                <button class="okay-button ${status ? 'blue-bg' : 'red-bg'} white-text">OKAY</button>
            </section>
        </section>
        `

    parent.insertAdjacentHTML('afterbegin', html)

    const modalWrapper = document.querySelector(`#${id}_wrapper`)
    hideModal(modalWrapper)
}

export const Dialog = (() => {
    return {
        operationSuccess: function (title, message) {
            render(
                true,
                'operation_success_dialog',
                title,
                message
            )
        },

        errorOccurred: function (message) {
            render(
                false,
                'error_occurred_dialog',
                'Error Occurred',
                message
            )
        },

        somethingWentWrong: function () {
            render(
                false,
                'something_went_wrong_dialog',
                'Something Went Wrong',
                'An unexpected error occurred. Please try again later.'
            )
        },

        changePassword: function (status) {
            const id = 'change-password-' + ((status) ? 'success' : 'error') + '_dialog'
            const message = (status)
                ? 'Your password was changed successfully.'
                : 'There was a problem changing your password. Please try again.'

            render(
                status,
                id,
                'Password Reset',
                message
            )
        },

        reportResult: function (status) {
            const title = 'Report ' + ((status) ? 'Success' : 'Failed')
            const message = (status)
                ? 'There was a problem submitting your report. Please try again later.'
                : 'Your report was submitted successfully.'

            render(
                status,
                'report_result',
                title,
                message
            )
        },

        sendLink: function (status) {
            const title = (status) ? 'Link Sent' : 'Sending Error'
            const message = (status)
                ? 'Kindly check your SMS inbox. We\'ve sent you a link.'
                : 'There was an error sending a link to your email. Please try again later.'

            render(
                status,
                'send_link_result',
                title,
                message
            )
        },

        tooManyAttempt: function () {
            render(
                false,
                'too_many_attempt_dialog',
                'Too Many Attempt',
                'Access temporarily locked due to multiple failed attempts. Try again in 2 minutes.'
            )
        }
    }
})()
