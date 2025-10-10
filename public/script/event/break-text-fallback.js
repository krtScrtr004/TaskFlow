document.addEventListener('DOMContentLoaded', () => {
    const isFirefox = navigator.userAgent.toLowerCase().includes('firefox')
    if (isFirefox) {
        applyEllipsisFallback()
    }
})

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