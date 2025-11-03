import { Dialog } from '../../render/dialog.js'

let lastSelectedRoleButton = null
const registerForm = document.querySelector('#register_form')
const roleButtons = registerForm?.querySelectorAll('button.role-button')

if (!roleButtons) {
    console.error('Role buttons not found.')
    Dialog.somethingWentWrong()
}
    
roleButtons?.forEach(button => {
    button.addEventListener('click', e => {
        e.preventDefault()

        // Deselect the last selected button
        if (lastSelectedRoleButton) {
            deselectButton(lastSelectedRoleButton)                
        }

        // Select the clicked button
        selectButton(button)
        // Update the last selected button reference
        lastSelectedRoleButton = button

        const closestRadioInput = button.parentElement.querySelector('input[type="radio"]')
        if (!closestRadioInput) {
            console.error('No corresponding radio input found for the selected role button.')
            Dialog.somethingWentWrong()
            return
        }
        closestRadioInput.checked = true
    })
})

/**
 * Selects a button element and updates its visual state to indicate selection.
 *
 * This function performs the following actions:
 * - Throws an error if the provided button is null or undefined.
 * - Adds the 'selected' CSS class to the button.
 * - Changes the source of the first <img> child by replacing '_w.svg' with '_b.svg' in its filename.
 * - Sets the color of the first <p> child to '#1e1e1e'.
 *
 * @param {HTMLElement} button The button element to select. Must not be null or undefined.
 *      - Should contain an <img> element with a source ending in '_w.svg' for icon color change.
 *      - Should contain a <p> element for text color change.
 * 
 * @throws {Error} If the button parameter is null or undefined.
 * 
 * @returns {void} This function does not return a value.
 */
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

/**
 * Deselects a role selection button by removing its selected state and updating its appearance.
 *
 * This function performs the following actions:
 * - Throws an error if the provided button is null or undefined.
 * - Removes the 'selected' CSS class from the button.
 * - Finds the first <img> element within the button and updates its source:
 *      - Replaces '_b.svg' with '_w.svg' in the image filename to indicate deselection.
 * - Finds the first <p> element within the button and sets its text color to '#fffefe'.
 *
 * @param {HTMLElement} button The button element to be deselected. Must contain:
 *      - An <img> element whose 'src' attribute ends with '_b.svg' (optional).
 *      - A <p> element for the button label (optional).
 *      - The 'selected' class if currently selected.
 * @throws {Error} If the button parameter is null or undefined.
 * @returns {void}
 */
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