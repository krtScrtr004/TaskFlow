import { die } from "../../utility/utility"

const projectForm = document.querySelector('#project_form')
if (!projectForm) {
    die('Project form not found on the page')
}

/**
 * Project Info
 */

const infoSection = projectForm.querySelector('#info_section')
if (!infoSection) {
    die('Info section not found in the form')
}
export const addedProjectInfo = new Map()
export const changedProjectInfo = new Map()

const projectNameInput = infoSection?.querySelector('input[name="name"]')
const projectDescriptionInput = infoSection?.querySelector('textarea[name="description"]')
const projectBudgetInput = infoSection?.querySelector('input[name="budget"]')
const projectMaxWorkersInput = infoSection?.querySelector('input[name="max_workers"]')
const projectStartDateTimeInput = infoSection?.querySelector('input[name="start_date_time"]')
const projectCompletionDateTimeInput = infoSection?.querySelector('input[name="completion_date_time"]')
if (!projectNameInput || !projectDescriptionInput || !projectBudgetInput
    || !projectMaxWorkersInput || !projectStartDateTimeInput || !projectCompletionDateTimeInput) {
    die('One or more project info inputs not found in the form')
}

const oldProjectData = {
    name: projectNameInput?.value || '',
    description: projectDescriptionInput?.value || '',
    budget: projectBudgetInput?.value || '',
    maxWorkers: projectMaxWorkersInput?.value || '',
    startDateTime: projectStartDateTimeInput?.value || '',
    completionDateTime: projectCompletionDateTimeInput?.value || ''
}

projectNameInput?.addEventListener('input', () => {
    const value = projectNameInput.value
    if (oldProjectData['name'] === value) {
        return
    }

    if (!addedProjectInfo.has('name') && !changedProjectInfo.has('name')) {
        addedProjectInfo.set('name', value)
    } else {
        changedProjectInfo.set('name', value)
    }
})

projectDescriptionInput?.addEventListener('input', () => {
    const value = projectDescriptionInput.value
    if (oldProjectData['description'] === value) {
        return
    }

    if (!addedProjectInfo.has('description') && !changedProjectInfo.has('description')) {
        addedProjectInfo.set('description', value)
    } else {
        changedProjectInfo.set('description', value)
    }
})

projectBudgetInput?.addEventListener('input', () => {
    const value = projectBudgetInput.value
    if (oldProjectData['budget'] === value) {
        return
    }
    
    if (!addedProjectInfo.has('budget') && !changedProjectInfo.has('budget')) {
        addedProjectInfo.set('budget', value)
    } else {
        changedProjectInfo.set('budget', value)
    }
})

projectMaxWorkersInput?.addEventListener('input', () => {
    const value = projectMaxWorkersInput.value
    if (oldProjectData['maxWorkers'] === value) {
        return
    }

    if (!addedProjectInfo.has('max_workers') && !changedProjectInfo.has('max_workers')) {
        addedProjectInfo.set('max_workers', value)
    } else {
        changedProjectInfo.set('max_workers', value)
    }
})

projectStartDateTimeInput?.addEventListener('input', () => {
    const value = projectStartDateTimeInput.value
    if (oldProjectData['startDateTime'] === value) {
        return
    }

    if (!addedProjectInfo.has('start_date_time') && !changedProjectInfo.has('start_date_time')) {
        addedProjectInfo.set('start_date_time', value)
    } else {
        changedProjectInfo.set('start_date_time', value)
    }
})

projectCompletionDateTimeInput?.addEventListener('input', () => {
    const value = projectCompletionDateTimeInput.value
    if (oldProjectData['completionDateTime'] === value) {
        return
    }

    if (!addedProjectInfo.has('completion_date_time') && !changedProjectInfo.has('completion_date_time')) {
        addedProjectInfo.set('completion_date_time', value)
    } else {
        changedProjectInfo.set('completion_date_time', value)
    }
})

/**
 * END
 * 
 * Phases Info
 */

const phaseSection = projectForm.querySelector('#phase_section')
if (!phaseSection) {
    die('Phases section not found in the form')
}

export const addedPhases = new Map()
export const changedPhases = new Map()
export const removedPhases = new Set()

const oldPhasesData = new Map()()
const phaseCards = phaseSection?.querySelectorAll('.phase-form-card') || []
phaseCards.forEach(card => {
    const id = card.dataset.phaseid || null
    if (!id) {
        return
    }

    const {
        phaseNameInput,
        phaseDescriptionInput,
        phaseBudgetInput,
        phaseContingencyRateInput,
        phaseBudgetNoteInput,
        phaseStartDateTimeInput,
        phaseCompletionDateTimeInput
    } = getPhaseDomParts(card) ?? {}

    oldPhasesData.set(id, {
        name: phaseNameInput.value.trim() || '',
        description: phaseDescriptionInput.value.trim() || '',
        budget: phaseBudgetInput.value.trim() || '',
        contingencyRate: phaseContingencyRateInput.value.trim() || '',
        budgetNote: phaseBudgetNoteInput.value.trim() || '',
        startDateTime: phaseStartDateTimeInput.value.trim() || '',
        completionDateTime: phaseCompletionDateTimeInput.value.trim() || ''
    })
})

phaseSection?.addEventListener('input', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) {
        return
    }
    const id = card.dataset.phaseid || null

    const {
        phaseNameInput,
        phaseDescriptionInput,
        phaseBudgetInput,
        phaseContingencyRateInput,
        phaseBudgetNoteInput,
        phaseStartDateTimeInput,
        phaseCompletionDateTimeInput
    } = getPhaseDomParts(card) ?? {}

    if (e.target === phaseNameInput) {
        const value = phaseNameInput.value.trim()
        if (oldPhasesData.get(id)?.name === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['name'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseDescriptionInput) {
        const value = phaseDescriptionInput.value.trim()
        if (oldPhasesData.get(id)?.['description'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['description'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseBudgetInput) {
        const value = phaseBudgetInput.value.trim()
        if (oldPhasesData.get(id)?.['budget'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['budget'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseContingencyRateInput) {
        const value = phaseContingencyRateInput.value.trim()
        if (oldPhasesData.get(id)?.['contingencyRate'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['contingencyRate'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseBudgetNoteInput) {      
        const value = phaseBudgetNoteInput.value.trim()
        if (oldPhasesData.get(id)?.['budgetNote'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['budgetNote'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseStartDateTimeInput) {
        const value = phaseStartDateTimeInput.value.trim()
        if (oldPhasesData.get(id)?.['startDateTime'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['startDateTime'] = value
        changedPhases.set(id, phaseChanges)
    }

    if (e.target === phaseCompletionDateTimeInput) {
        const value = phaseCompletionDateTimeInput.value.trim()
        if (oldPhasesData.get(id)?.['completionDateTime'] === value) {
            return
        }

        let phaseChanges = changedPhases.get(id) || {}
        phaseChanges['completionDateTime'] = value
        changedPhases.set(id, phaseChanges)
    }
})

/**
 * Extracts and validates DOM input elements for a phase form card.
 *
 * This function queries the provided card element for the expected phase inputs:
 * name, description, budget, contingency_rate, budget_note, start_date_time and completion_date_time.
 * If any input is missing it logs a warning and returns null. Otherwise it returns an object
 * with named references to each input element for further manipulation or change detection.
 *
 * @param {Element|HTMLElement} card The DOM element representing the phase form card.
 *
 * @returns {{phaseNameInput: HTMLInputElement, phaseDescriptionInput: HTMLTextAreaElement, phaseBudgetInput: HTMLInputElement, phaseContingencyRateInput: HTMLInputElement, phaseBudgetNoteInput: HTMLTextAreaElement, phaseStartDateTimeInput: HTMLInputElement, phaseCompletionDateTimeInput: HTMLInputElement}|null}
 *      An object containing the found input elements or null if one or more inputs are missing:
 *      - phaseNameInput: HTMLInputElement input[name="name"]
 *      - phaseDescriptionInput: HTMLTextAreaElement textarea[name="description"]
 *      - phaseBudgetInput: HTMLInputElement input[name="budget"]
 *      - phaseContingencyRateInput: HTMLInputElement input[name="contingency_rate"]
 *      - phaseBudgetNoteInput: HTMLTextAreaElement textarea[name="budget_note"]
 *      - phaseStartDateTimeInput: HTMLInputElement input[name="start_date_time"]
 *      - phaseCompletionDateTimeInput: HTMLInputElement input[name="completion_date_time"]
 */
export function getPhaseDomParts(card) {
    const phaseNameInput = card.querySelector('input[name="name"]')
    const phaseDescriptionInput = card.querySelector('textarea[name="description"]')
    const phaseBudgetInput = card.querySelector('input[name="budget"]')
    const phaseContingencyRateInput = card.querySelector('input[name="contingency_rate"]')
    const phaseBudgetNoteInput = card.querySelector('textarea[name="budget_note"]')
    const phaseStartDateTimeInput = card.querySelector('input[name="start_date_time"]')
    const phaseCompletionDateTimeInput = card.querySelector('input[name="completion_date_time"]')
    if (!phaseNameInput || !phaseDescriptionInput || !phaseBudgetInput || !phaseContingencyRateInput 
        || !phaseBudgetNoteInput || !phaseStartDateTimeInput || !phaseCompletionDateTimeInput) {
        console.warn('One or more phase inputs not found in the form card')
        return null
    }

    return {
        phaseNameInput,
        phaseDescriptionInput,
        phaseBudgetInput,
        phaseContingencyRateInput,
        phaseBudgetNoteInput,
        phaseStartDateTimeInput,
        phaseCompletionDateTimeInput
    }
}

/**
 * END
 * 
 * Workers Info
 */

const workersSection = projectForm.querySelector('#workers_section')
if (!workersSection) {
    die('Workers section not found in the form')
}

const selectedWorkersTableList = workersSection?.querySelector('.selected-workers-table tbody')
if (!selectedWorkersTableList) {
    die('Selected workers table list not found in the form')
}

export const addedWorkers = new Map()
export const changedWorkers = new Map()
export const removedWorkers = new Map()

const oldWorkersData = new Map()
const selectedWorkerRows = selectedWorkersTableList.querySelectorAll('tr.selected-worker-row')
selectedWorkerRows.forEach(row => {
    const id = row.dataset.workerid
    if (!id) {
        die('Worker ID not found')
    }

    const defaultRateInput = row.querySelector('input.default-rate-input')
    oldWorkersData.set(id, { defaultRate: defaultRateInput.value.trim() })
})

selectedWorkersTableList?.addEventListener('input', e => {
    const row = e.target.closest('tr.selected-worker-row')
    if (!row) {
        return
    }

    const id = row.dataset.workerid
    if (!id) {
        die('Worker ID not found')
    }

    const defaultRateInput = row.querySelector('input.default-rate-input')
    changedWorkers.set(id, { defaultRate: defaultRateInput.value.trim() })
})