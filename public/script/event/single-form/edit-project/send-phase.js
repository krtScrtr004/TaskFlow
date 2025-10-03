import { Http } from '../../../utility/http.js'
import { Loader } from '../../../render/loader.js'
import { Dialog } from '../../../render/dialog.js'
import { debounce } from '../../../utility/debounce.js'

let isLoading = false
async function sendToBackend(data) {
    if (isLoading) return
    isLoading = true

    const response = await Http.POST('add-phase', data)
    if (!response) {
        throw new Error('No response from server')
    }

    if (!response.data?.id) {
        throw new Error('Invalid response from server')
    }

    isLoading = false
    return response.data
}

function clonePhaseDom(data) {

    const phases = document.querySelector('.phase-details > .phases')
    let phaseClone = phases.querySelectorAll('.phase').item(0).cloneNode(true)

    Loader.trail(phases)

    const name = phaseClone.querySelector('.phase-name')
    const description = phaseClone.querySelector('.phase-description')
    const startDateTime = phaseClone.querySelector('.phase-start-datetime')
    const completionDateTime = phaseClone.querySelector('.phase-completion-datetime')
    const statusBadge = phaseClone.querySelector('.status-badge')

    // Remove disabled attribute from all inputs
    const allInputs = phaseClone.querySelectorAll('input, textarea, select');
    allInputs.forEach(input => input.removeAttribute('disabled'));
    
    // Disable startDateTime if it's already in progress (current date is after start date)
    const startDate = new Date(startDateTime);
    const hasStarted = startDate < new Date();
    if (hasStarted) {
        const startDateTimeInput = phaseClone.querySelector('.phase-start-datetime input');
        if (startDateTimeInput) {
            startDateTimeInput.setAttribute('disabled', 'disabled');
        }
    }
    
    phaseClone.dataset.id = data.id
    name.textContent = data.name
    description.textContent = data.description || 'No description provided.'
    startDateTime.textContent = new Date(data.startDateTime).toLocaleString()
    completionDateTime.textContent = new Date(data.completionDateTime).toLocaleString()
    statusBadge.className = 'status-badge center-child' + (hasStarted ? ' green-bg' : ' yellow-bg')

    const statusBadgeChild = statusBadge.querySelector('p')
    if (!statusBadgeChild) {
        const p = document.createElement('p')
        statusBadge.appendChild(p)

        statusBadgeChild = p
    }
    statusBadgeChild.textContent = hasStarted ? 'On Going' : 'Pending'
    statusBadgeChild.className = 'center-text' + (hasStarted ? ' white-text' : ' black-text')

    const phaseNameGrandGrandGrandParent = name.parentElement.parentElement.parentElement
    createCancelButton(phaseNameGrandGrandGrandParent, data.name)

    phases.appendChild(phaseClone)

    phases.scrollTo({ top: phases.scrollHeight, behavior: 'smooth' })   

    Loader.delete()
}

function createCancelButton(parentElement, phaseName) {
    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'unset-button'

    const icon = document.createElement('img')
    icon.src = 'asset/image/icon/delete_r.svg'
    icon.alt = `Cancel ${phaseName}`
    icon.title = `Cancel ${phaseName}`
    icon.height = 20

    button.appendChild(icon)
    parentElement.appendChild(button)
}

const addPhaseModal = document.querySelector('#add_phase_modal')
if (addPhaseModal) {
    const addNewPhaseButton = addPhaseModal.querySelector('#add_new_phase_button')
    addNewPhaseButton.addEventListener('click', async () => {
        const addPhaseForm = addPhaseModal.querySelector('#add_phase_form')

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
            const body = {
                'name': name,
                'description': description,
                'startDateTime': startDateTime,
                'completionDateTime': completionDateTime,
            }
            const phaseId = await sendToBackend(body)
            body.id = phaseId

            // Simulate a click on the close button to close the modal
            const closeButton = addPhaseModal.querySelector('#add_phase_close_button')
            closeButton.click()

            Loader.delete()

            Dialog.operationSuccess('Phase Added', 'New phase added successfully.')
            clonePhaseDom(body)

            // Reset form fields
            nameInput.value = ''
            descriptionInput.value = ''
            startDateTimeInput.value = ''
            completionDateTimeInput.value = ''
        } catch (error) {
            console.error('Error adding new phase:', error)
            Dialog.errorOccurred('Failed to add new phase. Please try again.')
            Loader.delete()
        } 
    })
} else {
    console.error('Add Phase Modal not found')
}