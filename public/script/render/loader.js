/**
 * Loader utility for rendering and managing loading indicators within DOM elements.
 *
 * Provides methods to display different types of loaders (full, lead, trail) and to patch
 * existing elements or text nodes with a loader, restoring the original content when done.
 *
 * @namespace Loader
 *
 * @function full
 * Renders a full overlay loader inside the specified parent element, covering its entire area.
 * 
 * @param {HTMLElement} parentElem The parent element to overlay the loader on.
 *
 * @function lead
 * Renders a loader at the leading (top) position of the specified parent element.
 * 
 * @param {HTMLElement} parentElem The parent element to display the loader in.
 *
 * @function trail
 * Renders a loader at the trailing (bottom) position of the specified parent element.
 * 
 * @param {HTMLElement} parentElem The parent element to display the loader in.
 *
 * @function patch
 * Replaces an element or text node with a loader, preserving the original content and restoring it when the loader is removed.
 * 
 * @param {HTMLElement|Text} elementToPatch The element or text node to patch with a loader.
 *   - If patching a text node, use the parent element's firstChild as the argument.
 *   - If patching an element, pass the element directly.
 *
 * @function delete
 * Removes the loader from the parent element and restores any patched content to its original state.
 *
 * @private
 * @function render
 * Internal method to insert loader HTML into the parent element at the specified position.
 * 
 * @param {HTMLElement} parentElem The parent element to render the loader in.
 * @param {string} loaderHtml The HTML string for the loader.
 * @param {InsertPosition} position The position relative to the parent element where the loader should be inserted.
 */
export const Loader = (() => {
    let parent;
    let patchedElem = null

    function render(parentElem, loaderHtml, position) {
        if (!parentElem) {
            return
        }
        parentElem.style.position = 'relative'
        parentElem.insertAdjacentHTML(position, loaderHtml)
        parent = parentElem
    }

    return {
        full: function (parentElem) {
            const parentHeight = parentElem.offsetHeight
            const loaderElem = `
                <div 
                    class="loader-wrapper padded center-child absolute transparent-bg" 
                    style="height:${parentHeight}; top:0; left:0; right:0; bottom:0;">
                        <span class="loader"></span>
                </div>`
            render(parentElem, loaderElem, 'afterbegin')
        },

        lead: function (parentElem) {
            const loaderElem = `
                <div 
                    class="loader-wrapper padded center-child transparent-bg" 
                    style="height:fit-content; top:0;">
                        <span class="loader"></span>
                </div>`
            render(parentElem, loaderElem, 'afterbegin')
        },

        trail: function (parentElem) {
            const loaderElem = `
                <div 
                    class="loader-wrapper padded center-child transparent-bg" 
                    style="height:fit-content; bottom:0;">
                        <span class="loader"></span>
                </div>`
            render(parentElem, loaderElem, 'beforeend')
        },

        /**
         * 
         * @param {HTMLElement} elementToPatch - If the elementToPatch is a plain text, 
         * use the elem.firstChild as the argument Otherwise, use the element inside 
         * the element as the argument
         */
        patch: function (elementToPatch) {
            // If patching a text node, use the parent element's firstChild as the argument.
            patchedElem = {
                type: elementToPatch instanceof Element ? 'element' : 'text',
                elem: elementToPatch,
                parent: elementToPatch.parentElement,
                originalText: null,
                style: null,
            }

            let elemHeight = 0
            // If patching an element, use the element directly.
            if (patchedElem.type === 'element') {
                elemHeight = elementToPatch.clientHeight
            } else {
                // Getting computed styles to properly handle padding & border values
                const computedStyle = window.getComputedStyle(patchedElem.parent)

                const paddingTop = parseFloat(computedStyle.paddingTop) || 0
                const paddingBottom = parseFloat(computedStyle.paddingBottom) || 0
                const paddingWidth = paddingTop + paddingBottom

                const borderTop = parseFloat(computedStyle.borderTopWidth) || 0
                const borderBottom = parseFloat(computedStyle.borderBottomWidth) || 0
                const borderWidth = borderTop + borderBottom

                const parentRec = patchedElem.parent.getBoundingClientRect()

                elemHeight = parentRec.height - (paddingWidth + borderWidth)
            }
            const parentElemWidth = patchedElem.parent.clientWidth

            const loaderElem = `
                <div 
                    class="loader-wrapper center-child transparent-bg" 
                    style="height:fit-content">
                        <span class="loader" style="height:${elemHeight}px; width:${elemHeight}px"></span>
                </div>`

            // Patch the element with loader
            if (patchedElem.type === 'element') {
                patchedElem.style = elementToPatch.style.display
                elementToPatch.style.display = 'none'
            } else {
                patchedElem.originalText = elementToPatch.textContent
                patchedElem.parent.innerHTML = ''
            }
            render(patchedElem.parent, loaderElem, 'afterbegin')
        },

        delete: function () {
            const createdLoader = parent?.querySelector('.loader-wrapper')
            createdLoader?.remove()

            if (patchedElem) {
                // Restore the patched element
                if (patchedElem.type === 'element') {
                    patchedElem.elem.style.display = patchedElem.style
                } else {
                    patchedElem.parent.innerHTML = patchedElem.originalText
                }
                patchedElem = null
            }
        }
    }
})()
