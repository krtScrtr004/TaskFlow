import { Dialog } from '../../../../../render/dialog.js'
import { confirmationDialog } from '../../../../../render/confirmation-dialog.js'

const createProject = document.querySelector('.create-project')
if (createProject) {
    createProject.addEventListener('click', async (e) => {
        e.stopPropagation()

        const cancelButton = e.target.closest('.cancel-phase-button')
        if (!cancelButton) return

        if (!await confirmationDialog(
            'Cancel Phase Creation',
            'Are you sure you want to cancel creating this phase? All unsaved changes will be lost.',
        )) return

        const phase = cancelButton.closest('.phase')
        if (phase) {
            phase.remove()

            const phases = createProject.querySelector('.phase-details > .phases')
            if (phases && phases.querySelectorAll('.phase').length === 0) {
                const noPhaseWall = phases.querySelector('.no-phases-wall')
                noPhaseWall?.classList.remove('no-display')
                noPhaseWall?.classList.add('flex-col')
            }
        } else {
            console.error('Phase element not found.')
            Dialog.somethingWentWrong()
        }
    })
} else {
    console.error('Create Project element not found.')
    Dialog.somethingWentWrong()
}
