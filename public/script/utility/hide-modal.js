/**
 * Hides a modal when its close/okay buttons are clicked.
 *
 * This function attaches click event listeners to any buttons within the provided
 * modal wrapper that match the selector 'button.close-button, button.okay-button'.
 * On click it prevents the default action, adds a 'fade-out' class to start an
 * exit animation, and listens for the 'animationend' event (registered with
 * { once: true }) to remove the 'fade-out', 'flex-col', and 'flex-row' classes
 * and add the 'no-display' class. When the animation completes the optional
 * callback is invoked.
 *
 * @param {Element} modalWrapper The modal wrapper element containing the buttons
 * @param {Function|null} [callback=null] Optional callback invoked after the modal
 *                                        has finished its hide animation
 *
 * @throws {Error} If modalWrapper is not provided or not an instance of Element
 * @throws {Error} If callback is provided but is not a function
 *
 * @return {void}
 */
export function hideModal(modalWrapper, callback = null) {
    if (!modalWrapper || !(modalWrapper instanceof Element))
        throw new Error('Modal wrapper must be a valid element')

    if (callback && typeof callback !== 'function')
        throw new Error('Callback must be a function')

    const buttons = modalWrapper.querySelectorAll('button.close-button, button.okay-button')
    buttons.forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault()

            modalWrapper.classList.add('fade-out')
            modalWrapper.addEventListener('animationend', () => {
                modalWrapper.classList.remove(
                    'fade-out', 
                    'flex-col', 
                    'flex-row',
                )
                modalWrapper.classList.add('no-display')

                callback()
            }, { once: true })
        })
    })
}