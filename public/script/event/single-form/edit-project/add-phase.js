const editableProjectDetailsForm = document.querySelector('#editable_project_details')
if (editableProjectDetailsForm) {
    const phaseDetails = editableProjectDetailsForm.querySelector('.phase-details')

    const addPhaseButton = phaseDetails.querySelector('#add_phase_button')
    const addPhaseModal = document.querySelector('#add_phase_modal')

    addPhaseModal.addEventListener('click', (e) => {
        // Check if the close button was clicked
        const closeButton = e.target.closest('#add_phase_close_button')
        if (closeButton) {
            addPhaseModal.classList.remove('flex-row')
            addPhaseModal.classList.add('no-display')
        }
    })

    addPhaseButton.addEventListener('click', () => {
        addPhaseModal.classList.add('flex-row')
        addPhaseModal.classList.remove('no-display')
    })
} else {
    console.error('Editable Project Details form is not found.')
}