/**
 * Hides a modal dialog when any close or okay button inside the modal is clicked.
 *
 * This function attaches click event listeners to all elements matching
 * 'button.close-button' or 'button.okay-button' within the provided modal wrapper.
 * When any of these buttons are clicked, the modal's display style is set to 'none',
 * effectively hiding the modal from view.
 *
 * @param {HTMLElement} modalWrapper The DOM element representing the modal wrapper.
 *      Should contain buttons with classes 'close-button' or 'okay-button' to trigger hiding.
 *      - modalWrapper: HTMLElement The modal container to be hidden.
 *      - button.close-button: HTMLButtonElement Button to close the modal.
 *      - button.okay-button: HTMLButtonElement Button to confirm and close the modal.
 *
 * @returns {void} Does not return a value.
 */
export function hideModal(modalWrapper) {
    const buttons = modalWrapper.querySelectorAll('button.close-button, button.okay-button')
    buttons.forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault()
            if (modalWrapper.style.display !== 'none') {
                modalWrapper.style.display = 'none'
            }
        })
    })
}