import { hideModal } from '../utility/hide-modal.js'

const ICON_PATH = '/public/asset/image/icon/'
const icons = ['confirm.svg', 'reject.svg']

/**
 * Renders a modal dialog with a status icon, title, message, and an OKAY button.
 *
 * This function creates and inserts a modal dialog element into the DOM. The dialog displays
 * a status icon (success or error), a title, a message, and an OKAY button. The modal is
 * inserted as the first child of the specified parent element.
 *
 * @param {boolean} status Indicates the status of the dialog (true for success, false for error).
 * @param {string} id Unique identifier for the modal dialog instance.
 * @param {string} title The title text to display in the dialog.
 * @param {string} message The message text to display in the dialog.
 * @param {Object} [option={}] Optional configuration object for additional settings.
 * @param {HTMLElement} [option.parent=document.querySelector('body')] The parent element to which the modal will be appended.
 * @param {string} [option.statusIcon] The filename of the status icon to display (overrides default based on status).
 * @param {string} [option.okayButtonClass] The CSS class to apply to the OKAY button (overrides default based on status).
 *
 * @returns {void}
 */
function render(
    status,
    id,
    title,
    message,
    option = {}
) {
    option.parent = option.parent ?? document.querySelector('body'),
    option.statusIcon = option.statusIcon ?? (status ? icons[0] : icons[1])
    option.okayButtonClass = option.okayButtonClass ?? (status ? 'blue-bg' : 'red-bg')

    const html = `
        <section id="${id}_wrapper" class="modal-wrapper flex-col">
            <section class="dialog black-bg flex-col">
                <img 
                    src="${ICON_PATH + option.statusIcon}" 
                    alt="Result icon" 
                    title="Result icon" 
                    height="75" 
                    width="75" />

                    <div>
                        <h1 class="center-text">${title}</h1>
                        <p class="center-text">${message}</p>
                    </div>

                <button class="okay-button ${option.okayButtonClass} white-text">OKAY</button>
            </section>
        </section>
        `

    option.parent.insertAdjacentHTML('afterbegin', html)

    const modalWrapper = document.querySelector(`#${id}_wrapper`)
    hideModal(modalWrapper)
}

/**
 * Dialog utility for rendering various modal dialogs in the application.
 *
 * Provides methods to display dialogs for different scenarios such as operation success, errors,
 * password changes, report submissions, link sending, and too many attempts.
 *
 * Each method internally calls the `render` function with appropriate parameters to display the dialog.
 *
 * @namespace Dialog
 * 
 * @function operationSuccess
 * @memberof Dialog
 * @description Renders a dialog indicating a successful operation.
 * @param {string} title - The title of the dialog.
 * @param {string} message - The message to display in the dialog.
 * 
 * @function errorOccurred
 * @memberof Dialog
 * @description Renders a dialog indicating an error has occurred.
 * @param {string} message - The error message to display.
 * 
 * @function somethingWentWrong
 * @memberof Dialog
 * @description Renders a dialog for unexpected errors with a generic message.
 * 
 * @function changePassword
 * @memberof Dialog
 * @description Renders a dialog indicating the result of a password change attempt.
 * @param {boolean} status - The status of the password change (true for success, false for error).
 * 
 * @function reportResult
 * @memberof Dialog
 * @description Renders a dialog indicating the result of a report submission.
 * @param {boolean} status - The status of the report submission (true for success, false for failure).
 * 
 * @function sendLink
 * @memberof Dialog
 * @description Renders a dialog indicating the result of sending a link.
 * @param {boolean} status - The status of the link sending operation (true for success, false for error).
 * 
 * @function tooManyAttempt
 * @memberof Dialog
 * @description Renders a dialog indicating that there have been too many attempts.
 * 
 */
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
                ? 'Your report was submitted successfully.'
                : 'There was a problem submitting your report. Please try again later.'

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
                'Access temporarily locked due to multiple failed attempts. Try again later.'
            )
        },

        taskDelayed: function (name) {
            render(
                false,
                'task_delayed_dialog',
                'Task Delayed',
                `The task "${name}" is delayed. Please take necessary actions to address the delay.`,
                {
                    statusIcon: 'warning.svg',
                    okayButtonClass: 'orange-bg'
                }
            )
        }
    }
})()
