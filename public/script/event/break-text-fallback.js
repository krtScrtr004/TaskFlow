// Special case handling for Firefox to support multi-line text ellipsis
document.addEventListener('DOMContentLoaded', () => {
    // Check if the browser is Firefox
    const isFirefox = navigator.userAgent.toLowerCase().includes('firefox')
    if (isFirefox) {
        // Apply the ellipsis fallback for multi-line text elements
        applyEllipsisFallback()
    }
})

/**
 * Applies a multi-line ellipsis fallback to elements matching the given selector.
 *
 * This function ensures that the text content of each selected element does not exceed
 * the specified number of lines. If the content overflows, it iteratively removes words
 * from the end and appends an ellipsis ("...") until the content fits within the allowed lines.
 *
 * @param {string} [selector='.multi-line-ellipsis'] CSS selector for target elements.
 * @param {number} [lines=2] Maximum number of lines to display before truncating with an ellipsis.
 *
 * @example
 * // Apply a 3-line ellipsis to all elements with the class 'truncate'
 * applyEllipsisFallback('.truncate', 3);
 */
function applyEllipsisFallback(selector = '.multi-line-ellipsis', lines = 2) {
    const elements = document.querySelectorAll(selector)

    elements.forEach(el => {
        const lineHeight = parseFloat(getComputedStyle(el).lineHeight)
        const maxHeight = lines * lineHeight

        while (el.scrollHeight > maxHeight) {
            el.textContent = el.textContent.replace(/\s+\S*$/, '...')
        }
    })
}