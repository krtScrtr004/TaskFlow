/**
 * Displays a confirmation dialog modal with a custom title and message, and returns the user's choice.
 *
 * This function creates and inserts a modal dialog into the DOM, preventing body scrolling while active.
 * The dialog presents a title, message, and two buttons: "Cancel" and "Confirm".
 * Resolves a Promise with `true` if the user confirms, or `false` if the user cancels or if the dialog fails to render.
 * Cleans up the modal and restores body scrolling after the user makes a choice.
 *
 * @param {string} title The title text displayed at the top of the confirmation dialog.
 * @param {string} message The message or description shown below the title in the dialog.
 * @param {string} [id='confirmation_dialog'] The HTML id attribute for the modal wrapper element.
 * @param {HTMLElement} [parent=document.body] The parent element to which the modal will be appended.
 *
 * @returns {Promise<boolean>} Promise that resolves to `true` if the user confirms, or `false` if canceled or on error.
 */
export async function confirmationDialog(
    title,
    message,
    id = 'confirmation_dialog',
    parent = document.body
) {
    return new Promise((resolve) => {
        const ICON_PATH = '/public/asset/image/icon/'

        // Prevent body scrolling
        const originalOverflow = document.body.style.overflow
        document.body.style.overflow = 'hidden'

        const HTML = `
            <section id="${id}" class="modal-wrapper flex-col">
                <div class="confirmation-dialog dialog black-bg flex-col flex-child-center-h">
                    <img src="${ICON_PATH}info_w.svg" alt="Confirm" title="Confirm" height="69" width="69">

                    <div>
                        <h1 class="center-text">${title}</h1>
                        <p class="center-text">${message}</p>
                    </div>

                    <div class="buttons flex-row">
                        <button id="cancel_confirmation_button" class="red-bg">
                            <div class="text-w-icon ">
                                <img src="${ICON_PATH}close_w.svg" alt="" title="" height="20">

                                <h3 class="white-text">Cancel</h3>
                            </div>
                        </button>

                        <button id="confirm_confirmation_button" class="blue-bg">
                            <div class="text-w-icon ">
                                <img src="${ICON_PATH}complete_w.svg" alt="" title="" height="20">

                                <h3 class="white-text">Confirm</h3>
                            </div>
                        </button>
                    </div>
                </div>
            </section>`
        
        parent.insertAdjacentHTML('afterbegin', HTML)

        const modalWrapper = parent.querySelector(`#${id}`)
        if (!modalWrapper) {
            console.error('Modal wrapper not found!')
            document.body.style.overflow = originalOverflow
            resolve(false)
            return
        }

        const cancelButton = modalWrapper.querySelector('#cancel_confirmation_button')
        const confirmButton = modalWrapper.querySelector('#confirm_confirmation_button')

        // Function to clean up and resolve
        const cleanup = (result) => {
            modalWrapper.remove()
            document.body.style.overflow = originalOverflow
            resolve(result)
        }

        // Event listeners that resolve the promise
        cancelButton.addEventListener('click', () => {
            cleanup(false)
        })

        confirmButton.addEventListener('click', () => {
            cleanup(true)
        })
    })
}