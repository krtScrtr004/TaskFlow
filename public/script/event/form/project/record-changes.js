import { die } from '../../../utility/utility.js'

const projectForm = document.querySelector('#project_form')
if (!projectForm) die('Project form not found on the page')

/**
 * Project Info
 */

const infoSection = projectForm.querySelector('#info_section')
if (!infoSection) die('Info section not found in the form')

export const addedProjectInfo = new Map()
export const changedProjectInfo = new Map()

const projectNameInput = infoSection.querySelector('input[name="name"]')
const projectDescriptionInput = infoSection.querySelector('textarea[name="description"]')
const projectBudgetInput = infoSection.querySelector('input[name="budget"]')
const projectMaxWorkersInput = infoSection.querySelector('input[name="max_workers"]')
const projectStartDateTimeInput = infoSection.querySelector('input[name="start_date_time"]')
const projectCompletionDateTimeInput = infoSection.querySelector('input[name="completion_date_time"]')
if (!projectNameInput || !projectDescriptionInput || !projectBudgetInput
    || !projectMaxWorkersInput || !projectStartDateTimeInput || !projectCompletionDateTimeInput) 
    die('One or more project info changes not found in the form')

/**
 * Update tracking maps for project information when an change's value changes.
 *
 * Compares the provided change's current value against the original value in oldProjectData.
 * If the value is unchanged, the function returns early. If the key already exists in the
 * addedProjectInfo map, its value is updated there; otherwise the key/value pair is recorded
 * in the changedProjectInfo map.
 *
 * Behavior and side effects:
 * - Reads oldProjectData[key] to determine if the value changed.
 * - No-op (early return) when the new value strictly equals the old value.
 * - If addedProjectInfo.has(key) is true, calls addedProjectInfo.set(key, value).
 * - Otherwise calls changedProjectInfo.set(key, value).
 * - Operates on external maps/objects: oldProjectData, addedProjectInfo, changedProjectInfo.
 *
 * @param {string} key Key identifying the project field being updated.
 * @param {HTMLInputElement|{value: string}} change DOM change element or object with a `value` property.
 *
 * @throws {void} This function does not throw.
 *
 * @return {void}
 *
 * PHPDoc:
 * @param string $key Key identifying the project field being updated
 * @param mixed $change DOM change element or object with a `value` property
 * @return void
 */
function updateProjectInfoMaps(key, change) {
    const value = change.value

    if (oldProjectData[key] === value) return
    else if (addedProjectInfo.has(key)) addedProjectInfo.set(key, value)
    else changedProjectInfo.set(key, value)
}

// Record old project data
const oldProjectData = {
    name: projectNameInput.value || '',
    description: projectDescriptionInput.value || '',
    budget: projectBudgetInput.value || '',
    maxWorkers: projectMaxWorkersInput.value || '',
    startDateTime: projectStartDateTimeInput.value || '',
    completionDateTime: projectCompletionDateTimeInput.value || ''
}

// Record any changes
projectNameInput.addEventListener('change', () => updateProjectInfoMaps('name', projectNameInput))

projectDescriptionInput.addEventListener('change', () => updateProjectInfoMaps('description', projectDescriptionInput))

projectBudgetInput.addEventListener('change', () => updateProjectInfoMaps('budget', projectBudgetInput))

projectMaxWorkersInput.addEventListener('change', () => updateProjectInfoMaps('maxWorkers', projectMaxWorkersInput))

projectStartDateTimeInput.addEventListener('change', () => updateProjectInfoMaps('startDateTime',  projectStartDateTimeInput))

projectCompletionDateTimeInput.addEventListener('change', () => updateProjectInfoMaps('completionDateTime', projectCompletionDateTimeInput))

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
if (!phaseSection) die('Phases section not found in the form')

export const addedPhases = new Map()
export const changedPhases = new Map()
export const removedPhases = new Map()

// Record old phase data
const oldPhasesData = new Map()
const phaseCards = phaseSection.querySelectorAll('.phase-form-card') || []
phaseCards.forEach(card => {
    const id = card.dataset.phaseid || null
    if (!id) return

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

/**
 * Updates internal maps tracking added or changed phase information for a given phase ID.
 *
 * This function obtains the trimmed phase name value (from the provided change element or the
 * module-scoped phaseNameInput), compares it to the stored original value in oldPhasesData for
 * the given id/key, and if different records the change into either changedPhases or addedPhases.
 *
 * Behavior and side effects:
 * - Reads the candidate value via change.value.trim() (implementation currently reads a
 *   module-scoped phaseNameInput.value.trim()).
 * - If the trimmed value equals oldPhasesData.get(id)[key], the function returns early (no-op).
 * - Builds or reuses an object of changes for the given id and assigns the new value at the given key.
 * - If the id is not present in oldPhasesData or is already present in addedPhases, the changes
 *   object is stored in addedPhases; otherwise it is stored in changedPhases.
 * - Mutates the global maps: changedPhases and addedPhases.
 *
 * @param {string|number} id - Identifier of the phase to update.
 * @param {string} key - Field/key within the phase to update (e.g., 'name').
 * @param {HTMLInputElement} change - Input element containing the new value for the phase field.
 *
 * @throws {void} This function does not throw; it performs no validation beyond value comparison.
 *
 * @return {void}
 *
 * PHPDoc:
 * @param mixed $id Identifier of the phase to update
 * @param string $key Field/key within the phase to update
 * @param mixed $change Input element containing the new value for the phase field
 * @return void
 */
function updatePhaseInfoMap(id, key, change) {
    const value = phaseNameInput.value.trim()
    if (oldPhasesData.get(id)[key] === value) return

    let phaseChanges = changedPhases.get(id) || addedPhases.get(id) || {}
    phaseChanges[key] = value

    if (!oldPhasesData.has(id) || addedPhases.has(id))
        addedPhases.set(id, phaseChanges)
    else
        changedPhases.set(id, phaseChanges)
}

// Record any changes
phaseSection?.addEventListener('change', e => {
    const card = e.target.closest('.phase-form-card')
    if (!card) return

    const id = card.dataset.phaseid || null
    if (!id) return

    const {
        phaseNameInput,
        phaseDescriptionInput,
        phaseBudgetInput,
        phaseContingencyRateInput,
        phaseBudgetNoteInput,
        phaseStartDateTimeInput,
        phaseCompletionDateTimeInput
    } = getPhaseDomParts(card) ?? {}

    if (e.target === phaseNameInput) updatePhaseInfoMap(id, 'name', phaseNameInput)

    if (e.target === phaseDescriptionInput) updatePhaseInfoMap(id, 'description', phaseDescriptionInput)

    if (e.target === phaseBudgetInput) updatePhaseInfoMap(id, 'budget', phaseBudgetInput)

    if (e.target === phaseContingencyRateInput) updatePhaseInfoMap(id, 'contingencyRate', phaseContingencyRateInput)

    if (e.target === phaseBudgetNoteInput) updatePhaseInfoMap(id, 'budgetNote', phaseBudgetNoteInput)

    if (e.target === phaseStartDateTimeInput) updatePhaseInfoMap(id, 'startDateTime', phaseStartDateTimeInput)

    if (e.target === phaseCompletionDateTimeInput) updatePhaseInfoMap(id, 'completionDateTime', phaseCompletionDateTimeInput)
})

/**
 * Extracts and validates DOM change elements for a phase form card.
 *
 * This function queries the provided card element for the expected phase changes:
 * name, description, budget, contingency_rate, budget_note, startDateTime and completionDateTime.
 * If any change is missing it logs a warning and returns null. Otherwise it returns an object
 * with named references to each change element for further manipulation or change detection.
 *
 * @param {Element|HTMLElement} card The DOM element representing the phase form card.
 *
 * @returns {{phaseNameInput: HTMLInputElement, phaseDescriptionInput: HTMLTextAreaElement, phaseBudgetInput: HTMLInputElement, phaseContingencyRateInput: HTMLInputElement, phaseBudgetNoteInput: HTMLTextAreaElement, phaseStartDateTimeInput: HTMLInputElement, phaseCompletionDateTimeInput: HTMLInputElement}|null}
 *      An object containing the found change elements or null if one or more changes are missing:
 *      - phaseNameInput: HTMLInputElement change[name="name"]
 *      - phaseDescriptionInput: HTMLTextAreaElement textarea[name="description"]
 *      - phaseBudgetInput: HTMLInputElement change[name="budget"]
 *      - phaseContingencyRateInput: HTMLInputElement change[name="contingency_rate"]
 *      - phaseBudgetNoteInput: HTMLTextAreaElement textarea[name="budget_note"]
 *      - phaseStartDateTimeInput: HTMLInputElement change[name="startDateTime"]
 *      - phaseCompletionDateTimeInput: HTMLInputElement change[name="completionDateTime"]
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
        console.warn('One or more phase changes not found in the form card')
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
if (!workersSection) die('Workers section not found in the form')

const selectedWorkersTableList = workersSection?.querySelector('.selected-workers-table tbody')
if (!selectedWorkersTableList) die('Selected workers table list not found in the form')

export const addedWorkers = new Map()
export const changedWorkers = new Map()
export const removedWorkers = new Map()

// Record old workers data
const oldWorkersData = new Map()
const selectedWorkerRows = selectedWorkersTableList.querySelectorAll('tr.selected-worker-row')
selectedWorkerRows.forEach(row => {
    const id = row.dataset.workerid
    if (!id) die('Worker ID not found')

    const defaultRateInput = row.querySelector('input.default-rate-input')
    oldWorkersData.set(id, defaultRateInput.value.trim())
})

// Record any changes
selectedWorkersTableList?.addEventListener('change', e => {
    const row = e.target.closest('tr.selected-worker-row')
    if (!row) return

    const id = row.dataset.workerid
    if (!id) return

    const defaultRateInput = row.querySelector('input.default-rate-input')
    (!oldWorkersData.has(id))
        ? addedWorkers.set(id, defaultRateInput.value.trim())
        : changedWorkers.set(id, defaultRateInput.value.trim())
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