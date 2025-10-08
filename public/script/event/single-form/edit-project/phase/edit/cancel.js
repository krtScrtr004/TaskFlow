import { Dialog } from '../../../../../render/dialog.js'
import { confirmationDialog } from '../../../../../render/confirmation-dialog.js'

export const phaseToCancel = new Set()

const phaseDetails = document.querySelector('.phase-details')
if (phaseDetails) {
    phaseDetails.addEventListener('click', async (e) => {
        e.stopPropagation()

        const cancelButton = e.target.closest('.cancel-phase-button')
        if (!cancelButton) return

        if (!await confirmationDialog(
            'Cancel Phase',
            'Are you sure you want to cancel this phase? This action cannot be undone.',
        )) return

        const phase = cancelButton.closest('.phase')
        const phaseId = phase.dataset.phaseid
        if (!phaseId) {
            console.error('Phase ID not found.')
            Dialog.somethingWentWrong()
            return
        }

        if (phaseId) phaseToCancel.add(phaseId)
        else phase.remove()

        try {

            updateBadgeToCancelled(phase)
            disableInputs(phase)

            cancelButton.remove()
        } catch (error) {
            console.error('Error canceling phase:', error)
            Dialog.errorOccurred('Failed to cancel phase. Please try again.')
        }
    })
} else {
    console.error('Phase details container not found.')
    Dialog.somethingWentWrong()
}

function updateBadgeToCancelled(phaseElement) {
    const statusBadge = phaseElement.querySelector('.status-badge')
    if (statusBadge) {
        statusBadge.className = 'status-badge badge center-child red-bg'
        const statusBadgeChild = statusBadge.querySelector('p')
        statusBadgeChild.textContent = 'Cancelled'
        statusBadgeChild.className = 'center-text white-text'
    }
}

function disableInputs(phaseElement) {
    const allInputs = phaseElement.querySelectorAll('input, textarea, select, button')
    allInputs.forEach(input => input.setAttribute('disabled', 'disabled'))
}

