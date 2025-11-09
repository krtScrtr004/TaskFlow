import { Dialog } from '../../../render/dialog.js'

const addPhaseButton = document.querySelector('.phase-details #add_phase_button')
if (!addPhaseButton) {
    console.error('Add Phase button not found.')
    Dialog.somethingWentWrong()
}

addPhaseButton?.addEventListener('click', () => {
    addPhaseModal.classList.add('flex-row')
    addPhaseModal.classList.remove('no-display')
})


const addPhaseModal = document.querySelector('#add_phase_modal')
if (!addPhaseModal) {
    console.error('Add Phase modal not found.')
    Dialog.somethingWentWrong()
}

addPhaseModal?.addEventListener('click', e => {
    // Check if the close button was clicked
    const closeButton = e.target.closest('#add_phase_close_button')
    if (closeButton) {
        addPhaseModal.classList.remove('flex-row')
        addPhaseModal.classList.add('no-display')

        const addPhaseForm = addPhaseModal.querySelector('#add_phase_form')
        if (addPhaseForm) {
            addPhaseForm.reset()
        }
    }
})

