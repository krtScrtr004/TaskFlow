import { hideModal } from '../utility/hide-modal.js'

export function errorListDialog(
    errors,
    title = 'Error',
    parent = document.body
) {
    const HTML = `
    <section id="error-list-dialog-wrapper" class="modal-wrapper flex-col">
        <div class="error-list-dialog modal dialog flex-col black-bg">
            <section class="heading">
                <div class="text-w-icon">
                    <img src="/TaskFlow/public/asset/image/icon/reject.svg" alt="Error" title="Error" height="28">
                    <h3 class="red-text">${title}</h3>
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