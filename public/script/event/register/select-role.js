import { Dialog } from '../../render/dialog.js'

let lastSelectedRoleButton = null
const registerForm = document.querySelector('#register_form')
const roleButtons = registerForm?.querySelectorAll('button.role-button')

if (roleButtons) {
    roleButtons.forEach(button => {
        button.addEventListener('click', e => {
            e.preventDefault()

            if (lastSelectedRoleButton) {
                deselectButton(lastSelectedRoleButton)                
            }

            selectButton(button)
            lastSelectedRoleButton = button

            const closestRadioInput = button.parentElement.querySelector('input[type="radio"]')
            if (closestRadioInput) {
                closestRadioInput.checked = true
            } else {
                console.error('No corresponding radio input found for the selected role button.')
                Dialog.somethingWentWrong()
            }
        })
    })
} else {
    console.error('Role buttons not found.')
    Dialog.somethingWentWrong()
}

function selectButton(button) {
    if (!button) {
        throw new Error('Button is null or undefined.')
    }

    button.classList.add('selected')

    const icon = button.querySelector('img')
    const text = button.querySelector('p')

    if (icon) {
        icon.src = icon.src.replace('_w.svg', '_b.svg')
    }
    if (text) {
        text.style.color = '#1e1e1e'
    }
}

function deselectButton(button) {
    if (!button) {
        throw new Error('Button is null or undefined.')
    }

    button.classList.remove('selected')

    const icon = button.querySelector('img')
    const text = button.querySelector('p')

    if (icon) {
        icon.src = icon.src.replace('_b.svg', '_w.svg')
    }
    if (text) {
        text.style.color = '#fffefe'
    }
}