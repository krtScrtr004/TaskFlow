import { hideModal } from '../utility/hide-modal.js'

/**
 * Renders and displays a modal dialog listing error messages.
 *
 * This function creates a modal dialog with a given title and a list of error messages,
 * then inserts it into the specified parent element (defaults to document.body).
 * The dialog includes a close button and uses a standard error icon.
 *
 * @param {string} title The title to display at the top of the error dialog.
 * @param {string[]} errors An array of error message strings to display in the dialog.
 * @param {HTMLElement} [parent=document.body] The parent element to which the dialog will be appended.
 *
 * @returns {void}
 */
export function errorListDialog(
    title,
    errors,
    parent = document.body
) {
    const HTML = `
    <section id="error-list-dialog-wrapper" class="modal-wrapper flex-col">
        <div class="error-list-dialog modal dialog flex-col black-bg">
            <section class="heading">
                <div class="text-w-icon">
                    <img src="w/public/asset/image/icon/reject.svg" alt="Error" title="Error" height="28">
                    <h3 class="wrap-text red-text">${title}</h3>
                </div>
            </section>

            <hr>

            <section>
                <ul>
                    ${errors.map(error => `<li class="wrap-text">${error}</li>`).join('')}
                </ul>
            </section>

            <button type="button" class="close-button red-bg white-text">Close</button>
        </div>
    </section>
    `

    parent.insertAdjacentHTML('afterbegin', HTML)

    const modalWrapper = document.querySelector('#error-list-dialog-wrapper')
    hideModal(modalWrapper)
}