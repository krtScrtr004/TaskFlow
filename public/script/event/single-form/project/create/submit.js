import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Loader } from '../../../../render/loader.js'
import { errorListDialog } from '../../../../render/error-list-dialog.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { validateInputs, workValidationRules } from '../../../../utility/validator.js'
import { handleException } from '../../../../utility/handle-exception.js'

let isLoading = false
const phaseToAdd = []

const createProjectForm = document.querySelector('#create_project_form')
const submitProjectButton = createProjectForm?.querySelector('#submit_project_button')
if (!submitProjectButton) {
    console.error('Submit Project button not found.')
    Dialog.somethingWentWrong()
} else {
    submitProjectButton.addEventListener('click', e => debounceAsync(submitForm(e), 300))
}

if (!createProjectForm) {
    console.error('Create Project form not found.')
    Dialog.somethingWentWrong()
} else {
    createProjectForm?.addEventListener('submit', e => debounceAsync(submitForm(e), 300))
}

async function submitForm(e) {
    e.preventDefault()

    if (!await confirmationDialog(
        'Add Project',
        'Are you sure you want to add this project?',
    )) return

    const nameInput = createProjectForm.querySelector('#project_name')
    const descriptionInput = createProjectForm.querySelector('#project_description')
    const budgetInput = createProjectForm.querySelector('#project_budget')
    const startDateInput = createProjectForm.querySelector('#project_start_date')
    const completionDateInput = createProjectForm.querySelector('#project_completion_date')
    if (!nameInput || !descriptionInput || !budgetInput || !startDateInput || !completionDateInput) {
        console.error('One or more input fields not found in the Editable Project Details form.')
        Dialog.somethingWentWrong()
        return
    }

    if (!validateInputs({
        name: nameInput.value.trim() ?? null,
        description: descriptionInput.value.trim() ?? null,
        budget: parseFloat(budgetInput.value) ?? null,
        startDateTime: startDateInput.value ?? null,
        completionDateTime: completionDateInput.value ?? null
    }, workValidationRules())) return

    const phaseContainers = createProjectForm.querySelectorAll('.phase')
    phaseContainers.forEach(phaseContainer => addPhaseForm(phaseContainer))

    Loader.patch(submitProjectButton.querySelector('.text-w-icon'))
    try {
        const response = await sendToBackend({
            project: {
                name: nameInput.value.trim(),
                description: descriptionInput.value.trim(),
                budget: parseFloat(budgetInput.value),
                startDateTime: startDateInput.value.trim(),
                completionDateTime: completionDateInput.value.trim(),
            },
            phases: phaseToAdd,
        })
        if (!response) {
            throw new Error('No response from server.')
        }

        Dialog.operationSuccess('Project Created.', 'The project has been successfully created.')
        setTimeout(() => window.location.href = `/TaskFlow/project/${response.projectId}`, 1500)
    } catch (error) {
        handleException(error, 'Error submitting form:', error)
    } finally {
        Loader.delete()
        phaseToAdd.length = 0
    }
}

function addPhaseForm(phaseContainer) {
    if (!phaseContainer) {
        throw new Error('Phase container not found.')
    }

    const nameInput = phaseContainer.querySelector(`.phase-name`)
    const descriptionInput = phaseContainer.querySelector(`.phase-description`)
    const startDateInput = phaseContainer.querySelector(`.phase-start-datetime`)
    const completionDateInput = phaseContainer.querySelector(`.phase-completion-datetime`)
    if (!nameInput || !descriptionInput || !startDateInput || !completionDateInput) {
        console.error(`One or more input fields not found.`)
        Dialog.somethingWentWrong()
        return
    }

    const data = {
        name: nameInput.textContent ? nameInput.textContent.trim() : null,
        description: descriptionInput.value ? descriptionInput.value.trim() : null,
        startDateTime: startDateInput.value ? startDateInput.value : null,
        completionDateTime: completionDateInput.value ? completionDateInput.value : null,
    }
    phaseToAdd.push(data)
}

async function sendToBackend(data) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!data) {
            throw new Error('No input data provided.')
        }

        const response = await Http.POST(`projects`, data)
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