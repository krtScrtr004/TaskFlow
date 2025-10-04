import { clonePhaseListCard } from './clone-phase-list-card.js'
import { Loader } from '../../../../render/loader.js'
import { Dialog } from '../../../../render/dialog.js'
import { debounce } from '../../../../utility/debounce.js'

/**
 * 
 * @param {Object} params 
 * @param {Function} params.action - Function to process form data before sending to backend. Receives form data object and should return modified form data object.
 * @param {boolean} params.allowDisable - Whether to allow disabling of fields based on phase status.
 */
export function addPhase(params = {}) {
    const addPhaseModal = document.querySelector('#add_phase_modal')
    if (!addPhaseModal) {
        throw new Error('Add Phase Modal not found.')
    }

    const addNewPhaseButton = addPhaseModal.querySelector('#add_new_phase_button')
    if (!addNewPhaseButton) {
        throw new Error('Add New Phase Button not found.')
    }
    addNewPhaseButton.addEventListener('click', debounce(async () => {
        const addPhaseForm = addPhaseModal.querySelector('#add_phase_form')
        if (!addPhaseForm) {
            throw new Error('Add Phase Form not found.')
        }

        const nameInput = addPhaseForm.querySelector('#phase_name')
        const descriptionInput = addPhaseForm.querySelector('#phase_description')
        const startDateTimeInput = addPhaseForm.querySelector('#phase_start_datetime')
        const completionDateTimeInput = addPhaseForm.querySelector('#phase_completion_datetime')

        const name = nameInput.value.trim()
        const description = descriptionInput.value.trim()
        const startDateTime = startDateTimeInput.value.trim()
        const completionDateTime = completionDateTimeInput.value.trim()

        // Check required fields
        if (!name || !startDateTime || !completionDateTime) {
            Dialog.errorOccurred('Name, start date and completion date fields are required. Please fill in all fields.')
            return
        }

        Loader.patch(addNewPhaseButton.querySelector('.text-w-icon'))
        try {
            let body = {
                'name': name,
                'description': description,
                'startDateTime': startDateTime,
                'completionDateTime': completionDateTime,
            }
            if (params.action && typeof params.action === 'function') {
                const returnValue = await params.action(body)
                if (returnValue !== undefined) {
                    body = returnValue
                }
            }

            // Simulate a click on the close button to close the modal
            const closeButton = addPhaseModal.querySelector('#add_phase_close_button')
            closeButton.click()

            Loader.delete()

            Dialog.operationSuccess('Phase Added', 'New phase added successfully.')

            const allowDisable = params.allowDisable ?? true
            clonePhaseListCard(body, allowDisable)

            // Reset form fields
            addPhaseForm.reset()
        } catch (error) {
            throw new Error(error)
        }
    }, 300))
}