
/**
 * Creates a hide-modal helper for a given modal wrapper element.
 *
 * The returned object exposes a `create` method that attaches a click listener
 * to a trigger button. When the button is clicked, the modal wrapper will be
 * animated out by adding the 'fade-out' class, and once the animation ends the
 * helper will remove the 'fade-out', 'flex-col', and 'flex-row' classes and
 * add the 'no-display' class. An optional callback will be invoked after the
 * animation completes.
 *
 * @param {Element} modalWrapper The modal wrapper element to be hidden. Must be a valid DOM Element.
 *
 * @returns {{ create: function(button: Element, callback?: function): object }}
 *          An object with a `create` method. `create` attaches the click handler
 *          to the provided button and returns the same object (for chaining).
 *
 * @throws {Error} If `modalWrapper` is not provided or is not an instance of Element.
 *
 * @example
 * // const helper = hideModal(document.querySelector('.modal-wrapper'));
 * // helper.create(document.querySelector('.close-btn'), () => console.log('hidden'));
 */
export function hideModal(modalWrapper) {
    if (!modalWrapper || !(modalWrapper instanceof Element))
        throw new Error('Modal wrapper must be a valid element')

    return {
        create: (button, callback = null) => {
            if (callback && typeof callback !== 'function')
                throw new Error('Callback must be a function')

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

            return this
        }
    }
}