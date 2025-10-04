import { Dialog } from '../../../../render/dialog.js'

const phaseDetails = document.querySelector('.phase-details')
if (phaseDetails) {

    const addPhaseButton = phaseDetails.querySelector('#add_phase_button')
    if (!addPhaseButton) {
        console.error('Add Phase button not found.')
        Dialog.somethingWentWrong()
    } else {
        addPhaseButton.addEventListener('click', () => {
            addPhaseModal.classList.add('flex-row')
            addPhaseModal.classList.remove('no-display')
        })
    }
    const addPhaseModal = document.querySelector('#add_phase_modal')
    if (!addPhaseModal) {
        console.error('Add Phase modal not found.')
        Dialog.somethingWentWrong()
    } else {
        addPhaseModal.addEventListener('click', (e) => {
            // Check if the close button was clicked
            const closeButton = e.target.closest('#add_phase_close_button')
            if (closeButton) {
                addPhaseModal.classList.remove('flex-row')
                addPhaseModal.classList.add('no-display')
            }
        })
    }
} else {
    console.error('Project Details form is not found.')
    Dialog.somethingWentWrong()
}