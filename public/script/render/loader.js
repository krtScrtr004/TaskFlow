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
                    class="loader-wrapper padded center-child absolute white-bg" 
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
         * use the button.firstChild as the argument Otherwise, use the element inside 
         * the button as the argument
         */
        patch: function (elementToPatch) {
            patchedElem = {
                type: elementToPatch instanceof Element ? 'element' : 'text',
                elem: elementToPatch,
                parent: elementToPatch.parentElement,
                originalText: null,
                style: null,
            }

            let elemHeight = 0
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

            const loaderElem = `
                <div 
                    class="loader-wrapper center-child transparent-bg" 
                    style="height:fit-content;">
                        <span class="loader" style="height:${elemHeight}px; width:${elemHeight}px"></span>
                </div>`

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
