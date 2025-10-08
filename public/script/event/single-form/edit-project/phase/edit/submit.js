import { Http } from '../../../../../utility/http.js'
import { Dialog } from '../../../../../render/dialog.js'
import { Loader } from '../../../../../render/loader.js'
import { confirmationDialog } from '../../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../../utility/debounce.js'
import { phaseToAdd } from './add.js'
import { phaseToCancel } from './cancel.js'
import { validateInputs } from '../../../../../utility/validator.js'

let isLoading = false
const toAdd = []
const phaseToEdit = []

const editableProjectDetailsForm = document.querySelector('.edit-project #editable_project_details')
const saveProjectInfoButton = editableProjectDetailsForm?.querySelector('#save_project_info_button')
if (!saveProjectInfoButton) {
    console.error('Save Project Info button not found within the Editable Project Details form.')
    Dialog.errorOccurred('Save Project Info button not found. Please refresh the page and try again.')
} else {
    // Submit form on button click or form submit
    saveProjectInfoButton.addEventListener('click', e => debounceAsync(submitForm(e), 300))
    editableProjectDetailsForm?.addEventListener('submit', e => debounceAsync(submitForm(e), 300))
}

async function submitForm(e) {
    e.preventDefault()

    if (!await confirmationDialog(
        'Save Changes',
        'Are you sure you want to save these changes to the project?'
    )) return

    const descriptionInput = document.querySelector('#project_description')
    const budgetInput = document.querySelector('#project_budget')
    const startDateInput = document.querySelector('#project_start_date')
    const completionDateInput = document.querySelector('#project_completion_date')
    if (!descriptionInput || !budgetInput || !startDateInput || !completionDateInput) {
        console.error('One or more input fields not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    if (!validateInputs({
        description: descriptionInput.value.trim() ?? null,
        budget: parseFloat(budgetInput.value) ?? null,
        startDate: startDateInput.value ?? null,
        completionDate: completionDateInput.value ?? null
    })) return

    const phaseContainers = editableProjectDetailsForm.querySelectorAll('.phase')
    phaseContainers.forEach(phaseContainer => addPhaseForm(phaseContainer))

    const projectId = editableProjectDetailsForm.dataset.projectid
    if (!projectId || projectId === 'null') {
        console.error('Project ID not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(saveProjectInfoButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(projectId, {
            project: {
                description: descriptionInput.value.trim(),
                budget: parseFloat(budgetInput.value),
                startDate: startDateInput.value,
            },
            phase: {
                toAdd: Array.from(phaseToAdd.values()),
                toEdit: phaseToEdit,
                toCancel: toAdd
            }
        })

        Dialog.operationSuccess('Project Edited.', 'The project has been successfully edited.')
    } catch (error) {
        console.error('Error occurred while submitting form:', error)
        Dialog.somethingWentWrong()
    } finally {
        Loader.delete()
        isLoading = false
    }
}

function addPhaseForm(phaseContainer) {
    const phaseId = phaseContainer.dataset.phaseid
    if (phaseToCancel.has(phaseId))
        return

    const descriptionInput = phaseContainer.querySelector(`.phase-description`)
    const startDateInput = phaseContainer.querySelector(`.phase-start-datetime`)
    const completionDateInput = phaseContainer.querySelector(`.phase-completion-datetime`)
    if (!descriptionInput || !startDateInput || !completionDateInput) {
        console.error(`One or more input fields not found for phase ID: ${phaseId}`)
        Dialog.somethingWentWrong()
        return
    }

    const data = {
        description: descriptionInput.value ? descriptionInput.value.trim() : null,
        startDate: startDateInput.value ? startDateInput.value : null,
        completionDate: completionDateInput.value ? completionDateInput.value : null
    }
    // If phaseId is not present, it's a new phase to add
    if (!phaseId || phaseId === '') toAdd.push(data)
    else phaseToEdit.push({ phaseId: phaseId, ...data })
}

async function sendToBackend(projectId, data) {
    if (isLoading) return
    isLoading = true

    const response = await Http.PUT(`projects/${projectId}`, data)
    if (!response) {
        throw new Error('No response from server.')
    }
}