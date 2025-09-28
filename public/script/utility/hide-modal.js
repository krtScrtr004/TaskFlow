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