import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Loader } from '../../../../render/loader.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { errorListDialog } from '../../../../render/error-list-dialog.js'
import { phaseToAdd } from './add-phase.js'
import { phaseToCancel } from './cancel-phase.js'
import { validateInputs, workValidationRules } from '../../../../utility/validator.js'
import { handleException } from '../../../../utility/handle-exception.js'

let isLoading = false

// Note: phaseToEdit tracks existing phases that have been modified
const phaseToEdit = []

const editableProjectDetailsForm = document.querySelector('.edit-project #editable_project_details')
const saveProjectInfoButton = editableProjectDetailsForm?.querySelector('#save_project_info_button')
if (!saveProjectInfoButton) {
    console.error('Save Project Info button not found.')
    Dialog.somethingWentWrong()
} else {
    saveProjectInfoButton.addEventListener('click', e => debounceAsync(submitForm(e), 300))
}

if (!editableProjectDetailsForm) {
    console.error('Editable Project Details form not found.')
    Dialog.somethingWentWrong()
} else {
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
        startDateTime: startDateInput.value ?? null,
        completionDateTime: completionDateInput.value ?? null
    }, workValidationRules())) return

    // Clear phaseToEdit before re-collecting to prevent duplication
    phaseToEdit.length = 0

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
        const response = await sendToBackend(projectId, {
            project: {
                description: descriptionInput.value.trim(),
                budget: parseFloat(budgetInput.value),
                startDateTime: startDateInput.value,
                completionDateTime: completionDateInput.value
            },
            phase: {
                toAdd: Array.from(phaseToAdd.values()),
                toEdit: phaseToEdit,
                toCancel: phaseToCancel.size > 0 
                    ? Array.from(phaseToCancel.values()).map(phase => ({ id: phase }))
                    : null
            }
        })
        if (!response) {
            throw new Error('No response from server.')
        }

        // Clear phase tracking only after successful submission to prevent data loss on retry
        phaseToEdit.length = 0
        phaseToAdd.clear()
        phaseToCancel.clear()

        Dialog.operationSuccess('Project Edited.', 'The project has been successfully edited.')
        setTimeout(() => window.location.href = `/TaskFlow/home/${response.projectId}`, 1500)
    } catch (error) {
        handleException(error, `Error submitting form: ${error}`)
    } finally {
        Loader.delete()
        isLoading = false
    }
}

function addPhaseForm(phaseContainer) {
    const phaseId = phaseContainer.dataset.phaseid
    if (phaseToCancel.has(phaseId)) return

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
        startDateTime: startDateInput.value ? startDateInput.value : null,
        completionDateTime: completionDateInput.value ? completionDateInput.value : null
    }
    
    // Only track existing phases that have been edited
    // New phases are already tracked by the phaseToAdd Map in add-phase.js
    if (phaseId && phaseId !== '') {
        phaseToEdit.push({ id: phaseId, ...data })
    }
}

async function sendToBackend(projectId, data) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true


        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID is required.')
        }

        if (!data) {
            throw new Error('No data provided.')
        }

        const response = await Http.PUT(`projects/${projectId}`, data)
        if (!response) {
            throw new Error('No response from server.')
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}