const backButtons = document.querySelectorAll('.back-button')
backButtons.forEach(button => {
    button.addEventListener('click', e => {
        e.stopPropagation()
        window.history.back()
    })
})

