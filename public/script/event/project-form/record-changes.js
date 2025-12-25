import { die } from '../../utility/utility.js'

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

// Record old project data
const oldProjectData = {
    name: projectNameInput?.value || '',
    description: projectDescriptionInput?.value || '',
    budget: projectBudgetInput?.value || '',
    maxWorkers: projectMaxWorkersInput?.value || '',
    startDateTime: projectStartDateTimeInput?.value || '',
    completionDateTime: projectCompletionDateTimeInput?.value || ''
}

// Record any changes
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

    if (!addedProjectInfo.has('maxWorkers') && !changedProjectInfo.has('maxWorkers')) {
        addedProjectInfo.set('maxWorkers', value)
    } else {
        changedProjectInfo.set('maxWorkers', value)
    }
})

projectStartDateTimeInput?.addEventListener('input', () => {
    const value = projectStartDateTimeInput.value
    if (oldProjectData['startDateTime'] === value) {
        return
    }

    if (!addedProjectInfo.has('startDateTime') && !changedProjectInfo.has('startDateTime')) {
        addedProjectInfo.set('startDateTime', value)
    } else {
        changedProjectInfo.set('startDateTime', value)
    }
})

projectCompletionDateTimeInput?.addEventListener('input', () => {
    const value = projectCompletionDateTimeInput.value
    if (oldProjectData['completionDateTime'] === value) {
        return
    }

    if (!addedProjectInfo.has('completionDateTime') && !changedProjectInfo.has('completionDateTime')) {
        addedProjectInfo.set('completionDateTime', value)
    } else {
        changedProjectInfo.set('completionDateTime', value)
    }
})

/**
 * Builds and returns a merged Map of project information from added and changed sources.
 *
 * This function creates a new Map, inserts entries from addedProjectInfo first, and then
 * overlays entries from changedProjectInfo so that any project IDs present in both are
 * replaced by the changedProjectInfo version. Neither source collection is modified.
 *
 * @returns {Map<any, any>} A Map keyed by project id containing the merged project info
 */
export function getMergedAddedAndChangedProjectsMap() {
    const merged = new Map()

    // Start with addedProjects
    for (const [id, added] of addedProjectInfo) {
        merged.set(id, added)
    }

    // Override with changedProjects
    for (const [id, changed] of changedProjectInfo) {
        merged.set(id, changed)
    }

    return merged
}

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

// Record old phase data
const oldPhasesData = new Map()
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

// Record any changes
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
 * name, description, budget, contingency_rate, budget_note, startDateTime and completionDateTime.
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
 *      - phaseStartDateTimeInput: HTMLInputElement input[name="startDateTime"]
 *      - phaseCompletionDateTimeInput: HTMLInputElement input[name="completionDateTime"]
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
 * Creates and returns a new Map of phase objects merged from two external collections:
 * - Start with entries from `addedPhases` (each value shallow-copied).
 * - Then apply entries from `changedPhases`, performing a shallow merge over any
 *   existing entry so that properties in `changedPhases` override those from `addedPhases`.
 *
 * If a changed entry has no corresponding added entry, it is merged into an empty base
 * object and still included in the resulting Map. Note that merges are shallow (spread),
 * so nested objects are not deep-cloned.
 *
 * @return {Map<any, Object>} A Map keyed by phase id whose values are the merged phase objects
 */
export function getMergedAddedAndChangedPhasesMap() {
    const merged = new Map()

    // Start with addedPhases
    for (const [id, added] of addedPhases) {
        merged.set(id, { ...added })
    }

    // Override with changedPhases
    for (const [id, changed] of changedPhases) {
        const base = merged.get(id) ?? {}
        merged.set(id, {
            ...base, ...changed
        })
    }

    return merged
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

// Record old workers data
const oldWorkersData = new Map()
const selectedWorkerRows = selectedWorkersTableList.querySelectorAll('tr.selected-worker-row')
selectedWorkerRows.forEach(row => {
    const id = row.dataset.workerid
    if (!id) {
        die('Worker ID not found')
    }

    const defaultRateInput = row.querySelector('input.default-rate-input')
    oldWorkersData.set(id, defaultRateInput.value.trim())
})

// Record any changes
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
    changedWorkers.set(id, defaultRateInput.value.trim())
})

/**
 * Merges entries from addedWorkers and changedWorkers into a single Map.
 *
 * This function creates a new Map, copies all entries from addedWorkers into it,
 * then copies all entries from changedWorkers, overwriting any existing entry with the same id.
 * Both addedWorkers and changedWorkers are expected to be iterables of [id, value] pairs
 * (e.g., Map instances or arrays of tuples).
 *
 * @returns {Map<any, any>} A Map of merged worker records keyed by id; entries from changedWorkers
 *                          take precedence over entries from addedWorkers when ids conflict.
 */
export function getMergedAddedAndChangedWorkersMap() {
    const merged = new Map()
    for (const [id, added] of addedWorkers) {
        merged.set(id, added)
    }

    for (const [id, changed] of changedWorkers) {
        merged.set(id, changed)
    }

    return merged
}