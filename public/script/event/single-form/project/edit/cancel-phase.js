import { Dialog } from '../../../../render/dialog.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { handleException } from '../../../../utility/handle-exception.js'

export const phaseToCancel = new Set()

const phaseDetails = document.querySelector('.phase-details')
if (!phaseDetails) {
    console.error('Phase details container not found.')
    Dialog.somethingWentWrong()
}

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
    const phaseId = phase.dataset.phaseid
    if (!phaseId) {
        console.error('Phase ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    // If phaseId exists, it means it's an existing phase that needs to be canceled in the backend
    if (phaseId) {
        phaseToCancel.add(phaseId)
    } else {
        phase.remove()
    }

    try {
        // Update UI to reflect cancellation
        updateBadgeToCancelled(phase)
        // Disable all inputs in the phase
        disableInputs(phase)

        cancelButton.remove()
    } catch (error) {
        handleException(error, `Error canceling phase: ${error.message}`)
    }
})


/**
 * Updates the status badge of a given phase element to indicate it is cancelled.
 *
 * This function locates the status badge within the provided phase element and updates its
 * appearance and text to reflect a "Cancelled" state:
 * - Sets the badge's class to display a red background and center its content.
 * - Changes the badge's text to "Cancelled".
 * - Updates the text styling to be centered and white.
 *
 * @param {HTMLElement} phaseElement The DOM element representing the phase whose badge should be updated.
 *      - Should contain a child element with the class 'status-badge'.
 *      - The 'status-badge' element should contain a <p> element for the badge text.
 *
 * @returns {void} This function does not return a value.
 */
function updateBadgeToCancelled(phaseElement) {
    const statusBadge = phaseElement.querySelector('.status-badge')
    if (statusBadge) {
        statusBadge.className = 'status-badge badge center-child red-bg'
        const statusBadgeChild = statusBadge.querySelector('p')
        statusBadgeChild.textContent = 'Cancelled'
        statusBadgeChild.className = 'center-text white-text'
    }
}

/**
 * Disables all form controls within a given phase element.
 *
 * This function selects all input, textarea, select, and button elements
 * inside the provided phaseElement and sets their 'disabled' attribute,
 * making them non-interactive for the user.
 *
 * @param {HTMLElement} phaseElement The container element representing a phase,
 *        within which all form controls will be disabled.
 *        Should be a valid DOM element containing form fields.
 *
 * @return {void} This function does not return a value.
 */
function disableInputs(phaseElement) {
    const allInputs = phaseElement.querySelectorAll('input, textarea, select, button')
    allInputs.forEach(input => input.setAttribute('disabled', 'disabled'))
}

