import { Http } from '../../../utility/http.js'
import { Dialog } from '../../../render/dialog.js'
import { confirmationDialog } from '../../../render/confirmation-dialog.js'

let isLoading = false
async function sendToBackend(phaseId) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST('cancel-phase', { phaseId })
    if (!response) {
        throw new Error('Failed to cancel phase.')
    }

    isLoading = false
}

function updateBadgeToCancelled(phaseElement) {
    const statusBadge = phaseElement.querySelector('.status-badge')
    if (statusBadge) {
        statusBadge.className = 'status-badge center-child red-bg'
        const statusBadgeChild = statusBadge.querySelector('p')
        statusBadgeChild.textContent = 'Cancelled'
        statusBadgeChild.className = 'center-text white-text'
    }
}

function disableInputs(phaseElement) {
    const allInputs = phaseElement.querySelectorAll('input, textarea, select, button')
    allInputs.forEach(input => input.setAttribute('disabled', 'disabled'))
}

const phaseDetails = document.querySelector('.phase-details')
phaseDetails?.addEventListener('click', async (e) => {
    e.stopPropagation()

    const cancelButton = e.target.closest('.cancel-phase-button')
    if (!cancelButton) {
        return
    }

    if (!await confirmationDialog(
        'Cancel Phase',
        'Are you sure you want to cancel this phase? This action cannot be undone.',
    )) return

    const phase = cancelButton.closest('.phase')
    const phaseId = phase.dataset.id
    if (!phaseId) {
        console.error('Phase ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(phaseId)
        updateBadgeToCancelled(phase)
        disableInputs(phase)

        cancelButton.remove()
    } catch (error) {
        console.error('Error canceling phase:', error)
        Dialog.errorOccurred('Failed to cancel phase. Please try again.')
    }
})