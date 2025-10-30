import { Http } from '../../../../utility/http.js'
import { Dialog } from '../../../../render/dialog.js'
import { Loader } from '../../../../render/loader.js'
import { confirmationDialog } from '../../../../render/confirmation-dialog.js'
import { debounceAsync } from '../../../../utility/debounce.js'
import { phaseToCancel } from './cancel-phase.js'
import { validateInputs, workValidationRules } from '../../../../utility/validator.js'
import { handleException } from '../../../../utility/handle-exception.js'

let isLoading = false

// Note: phaseToEdit tracks existing phases that have been modified
const phaseToAdd = []
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

// Helper: normalize date input to ISO string or null
const normalizeDate = (val) => {
    if (!val) return null
    const d = new Date(val)
    return Number.isNaN(d.getTime()) ? val : d.toISOString()
}

// Capture original project state (normalize once on load)
const descriptionInput = document.querySelector('#project_description')
const budgetInput = document.querySelector('#project_budget')
const startDateInput = document.querySelector('#project_start_date')
const completionDateInput = document.querySelector('#project_completion_date')

const originalProject = {
    description: descriptionInput ? (descriptionInput.value?.trim() || null) : null,
    budget: budgetInput ? (budgetInput.value ? parseFloat(budgetInput.value) : null) : null,
    startDateTime: startDateInput ? normalizeDate(startDateInput.value) : null,
    completionDateTime: completionDateInput ? normalizeDate(completionDateInput.value) : null
}

// Capture original phases state
const originalPhases = {}
const phaseContainers = document.querySelectorAll('.phase') || []
phaseContainers.forEach(pc => {
    const pid = pc.dataset.phaseid
    if (!pid || pid === '') return
    const descInput = pc.querySelector('.phase-description')
    const startInput = pc.querySelector('.phase-start-datetime')
    const completionInput = pc.querySelector('.phase-completion-datetime')
    originalPhases[pid] = {
        description: descInput ? (descInput.value?.trim() || null) : null,
        startDateTime: startInput ? normalizeDate(startInput.value) : null,
        completionDateTime: completionInput ? normalizeDate(completionInput.value) : null
    }
})

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

    // Clear before re-collecting to prevent duplication
    phaseToAdd.length = 0
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
        // Normalize current project
        const currentProject = {
            description: descriptionInput.value?.trim() || null,
            budget: budgetInput.value ? parseFloat(budgetInput.value) : null,
            startDateTime: normalizeDate(startDateInput.value),
            completionDateTime: normalizeDate(completionDateInput.value)
        }

        // Build changedProject by comparing to originalProject captured on load
        const changedProject = {}
        const orig = originalProject || {}
        Object.keys(currentProject).forEach(key => {
            if (key === 'budget') {
                const origNum = orig[key] === null || orig[key] === undefined ? null : Number(orig[key])
                const curNum = currentProject[key] === null || currentProject[key] === undefined ? null : Number(currentProject[key])
                if (origNum !== curNum) changedProject[key] = curNum
                return
            }
            const origVal = orig[key] ?? null
            const curVal = currentProject[key] ?? null
            if (origVal !== curVal) changedProject[key] = curVal
        })

        const phasePayload = {
            toAdd: phaseToAdd,
            toEdit: phaseToEdit,
            toCancel: phaseToCancel.size > 0 
                ? Array.from(phaseToCancel.values()).map(phase => ({ id: phase }))
                : null
        }

        const payload = {}
        if (Object.keys(changedProject).length > 0) { 
            payload.project = changedProject
        }
        const hasPhaseChanges = 
            (phasePayload.toAdd && phasePayload.toAdd.length > 0) || 
            (phasePayload.toEdit && phasePayload.toEdit.length > 0) || 
            (phasePayload.toCancel && phasePayload.toCancel.length > 0)
        if (hasPhaseChanges) { 
            payload.phase = phasePayload
        }

        if (Object.keys(payload).length === 0) {
            // Nothing changed — no backend call required
            Dialog.operationSuccess('No changes', 'No changes detected to save.')
            Loader.delete()
            return
        }

        const response = await sendToBackend(projectId, payload)
        if (!response) {
            throw new Error('No response from server.')
        }

        // Clear phase tracking only after successful submission to prevent data loss on retry
        phaseToAdd.length = 0
        phaseToEdit.length = 0
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

    const nameInput = phaseContainer.querySelector(`.phase-name`)
    const descriptionInput = phaseContainer.querySelector(`.phase-description`)
    const startDateInput = phaseContainer.querySelector(`.phase-start-datetime`)
    const completionDateInput = phaseContainer.querySelector(`.phase-completion-datetime`)
    if (!descriptionInput || !startDateInput || !completionDateInput) {
        console.error(`One or more input fields not found for phase ID: ${phaseId}`)
        Dialog.somethingWentWrong()
        return
    }

    // Normalize current values
    const cur = {
        description: descriptionInput.value ? descriptionInput.value.trim() : null,
        startDateTime: normalizeDate(startDateInput.value),
        completionDateTime: normalizeDate(completionDateInput.value)
    }

    // Only track existing phases that have been edited
    if (phaseId && phaseId !== '') {
        const orig = (originalPhases && originalPhases[phaseId]) ? originalPhases[phaseId] : {}
        const changed = {}

        if ((orig.description ?? null) !== (cur.description ?? null)) {
            changed.description = cur.description
        }
        if ((orig.startDateTime ?? null) !== (cur.startDateTime ?? null)) {
            changed.startDateTime = cur.startDateTime
        }
        if ((orig.completionDateTime ?? null) !== (cur.completionDateTime ?? null)) {
            changed.completionDateTime = cur.completionDateTime
        }

        // Only push when there are actual changes
        if (Object.keys(changed).length > 0) {
            phaseToEdit.push({ id: phaseId, ...changed })
        }
    } else {
        phaseToAdd.push({ name: nameInput.textContent.trim(), ...cur })
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

    const response = await Http.PATCH(`projects/${projectId}`, data)
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