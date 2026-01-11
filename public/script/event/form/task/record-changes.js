import { die } from '../../../utility/utility.js'

const taskForm = document.querySelector('#task_form')
if (!taskForm) die('Task form element not found')

/**
 * Task Info
 */

const taskInfo = taskForm.querySelector('#task_info')
if (!taskInfo) die('Task info element not found')

export const addedTaskInfo = new Map()
export const changedTaskInfo = new Map()

const taskNameInput = taskInfo.querySelector('#name')
const taskStartDateTimeInput = taskInfo.querySelector('#start_date_time')
const taskCompletionDateTimeInput = taskInfo.querySelector('#completion_date_time')
const taskDescriptionInput = taskInfo.querySelector('#description')
const taskEstimatedCostInput = taskInfo.querySelector('#estimated_cost')
const taskBudgetNoteInput = taskInfo.querySelector('#budget_note')
if (!taskNameInput || !taskStartDateTimeInput || !taskCompletionDateTimeInput
    || !taskDescriptionInput || !taskEstimatedCostInput || !taskBudgetNoteInput)
    die('One or more task info input elements not found')

/**
 * Updates task information maps based on changes to task input fields.
 *
 * This function tracks modifications to task data by comparing the current input value
 * against the original task data. It maintains two separate maps to distinguish between
 * newly added task information and modified existing information.
 *
 * Behavior and side effects:
 * - Extracts the current value from the provided input element.
 * - Compares the current value with the original value stored in oldTaskData[id].
 * - If the value hasn't changed from the original, performs no action (early return).
 * - If the task ID already exists in addedTaskInfo map, updates its value in that map.
 * - Otherwise, records the change in the changedTaskInfo map.
 * - Does not validate input types or check for null/undefined values.
 * - Assumes oldTaskData, addedTaskInfo, and changedTaskInfo are accessible in the outer scope.
 * - Uses strict equality (===) for value comparison.
 *
 * @param {string|number} id - The unique identifier for the task field being updated
 * @param {HTMLInputElement|HTMLTextAreaElement|HTMLSelectElement} input - The DOM input element containing the new value
 *
 * @return {void}
 */
function updateTaskInfoMaps(id, input) {
    const value = input.value

    if (oldTaskData[id] === value) return
    else if (addedTaskInfo.has(id)) addedTaskInfo.set(id, value)
    else changedTaskInfo.set(id, value)
}

// Record old task data
const oldTaskData = {
    name: taskNameInput.value || '',
    startDateTime: taskStartDateTimeInput.value || '',
    completionDateTime: taskCompletionDateTimeInput.value || '',
    description: taskDescriptionInput.value || '',
    estimatedCost: taskEstimatedCostInput.value || '',
    budgetNote: taskBudgetNoteInput.value || ''
}

// Record any changes in the task info

taskNameInput.addEventListener('change', () => updateTaskInfoMaps('name', taskNameInput))

taskStartDateTimeInput.addEventListener('change', () => updateTaskInfoMaps('startDateTime', taskStartDateTimeInput))

taskCompletionDateTimeInput.addEventListener('change', () => updateTaskInfoMaps('completionDateTime', taskCompletionDateTimeInput))

taskDescriptionInput.addEventListener('change', () => updateTaskInfoMaps('description', taskDescriptionInput))

taskEstimatedCostInput.addEventListener('change', () => updateTaskInfoMaps('estimatedCost', taskEstimatedCostInput))

taskBudgetNoteInput.addEventListener('change', () => updateTaskInfoMaps('budgetNote', taskBudgetNoteInput))

/**
 * Merges added and changed task information into a single Map.
 *
 * This function combines two sources of task data: newly added tasks and modified existing tasks.
 * It creates a unified Map by first copying all entries from addedTaskInfo, then overlaying
 * any entries from changedTaskInfo. When a task ID exists in both sources, the changed task
 * information takes precedence and overrides the added task information.
 *
 * Behavior and side effects:
 * - Creates a new Map instance to store the merged results.
 * - Iterates through addedTaskInfo and copies all [id, task] pairs to the merged Map.
 * - Iterates through changedTaskInfo and copies all [id, task] pairs to the merged Map,
 *   overwriting any existing entries with the same ID from addedTaskInfo.
 * - Does not modify the original addedTaskInfo or changedTaskInfo collections.
 * - The order of iteration follows the insertion order of the source Maps.
 * - If a task ID appears in both addedTaskInfo and changedTaskInfo, the value from
 *   changedTaskInfo will be present in the returned Map.
 *
 * @returns {Map} A new Map containing all added tasks with changed tasks overlaid on top
 */
export function getMergedAddedAndChangedTasksMap() {
    const merged = new Map()

    for (const [id, added] of addedTaskInfo) {
        merged.set(id, added)
    }

    // Override initial value
    for (const [id, changed] of changedTaskInfo) {
        merged.set(id, changed)
    }

    return merged
}

/**
 * END
 * 
 * Worker Info
 */

const workerInfo = taskForm.querySelector('#worker_info')
if (!workerInfo) die('Worker info element not found')

export const addedWorkerInfo = new Map()
export const changedWorkerInfo = new Map()
export const removedWorkerInfo = new Set()

// Record old worker info
const oldWorkerInfo = new Map()
const oldSelectedTaskWorkerFormCard = workerInfo.querySelectorAll('.selected-task-worker-form-card')
oldSelectedTaskWorkerFormCard.forEach(card => {
    const id = card.dataset.workerid
    if (!id) return

    const unitRateInput = card.querySelector('.unit-rate-input')
    const hoursAssignedInput = card.querySelector('.hours-assigned-input')

    oldWorkerInfo.set(id, {
        unitRate: unitRateInput.value || '',
        hoursAssigned: hoursAssignedInput.value || ''
    })
})

/**
 * Updates internal maps that track changes to a worker's information based on an input element's value.
 *
 * This function:
 * - Reads the new value from the provided input (input.value).
 * - If the current stored value (oldWorkerInfo.get(id)[key]) strictly equals the new value, it returns early.
 * - Merges or creates a changes object from changedWorkerInfo.get(id) || addedWorkerInfo.get(id) || {} and sets workerChanges[key] = value.
 * - If the worker ID is not present in oldWorkerInfo or is already present in addedWorkerInfo, stores the changes under addedWorkerInfo.set(id, workerChanges); otherwise stores them under changedWorkerInfo.set(id, workerChanges).
 *
 * Behavior and side effects:
 * - Reads and may mutate the globals/maps: oldWorkerInfo, changedWorkerInfo, addedWorkerInfo.
 * - May overwrite existing change entries for the given id.
 * - Performs no validation of input beyond accessing input.value.
 * - Accessing oldWorkerInfo.get(id)[key] when oldWorkerInfo.get(id) is undefined will throw a TypeError; callers should ensure oldWorkerInfo.has(id) when appropriate.
 *
 * @param {string|number} id Identifier of the worker whose info is being updated
 * @param {string} key The field/key on the worker info to update
 * @param {{ value: any }} input Input-like object with a value property (e.g. HTMLInputElement)
 *
 * @throws {TypeError} If oldWorkerInfo.get(id) is undefined and property access is attempted
 *
 * @returns {void}
 *
 * @param mixed $id Identifier of the worker (PHPDoc)
 * @param string $key The field/key on the worker info to update (PHPDoc)
 * @param mixed $input Input-like object with a value property (PHPDoc)
 * @throws TypeError If oldWorkerInfo.get($id) is undefined and property access is attempted (PHPDoc)
 * @return void
 */
function updateWorkerInfoMaps(id, key, input) {
    const value = input.value
    if (oldWorkerInfo.get(id)[key] === value) return

    let workerChanges = changedWorkerInfo.get(id) || addedWorkerInfo.get(id) || {}
    workerChanges[key] = value

    if (!oldWorkerInfo.has(id) || addedWorkerInfo.has(id))
        addedWorkerInfo.set(id, workerChanges)
    else
        changedWorkerInfo.set(id, workerChanges)
}

workerInfo.addEventListener('change', e => {
    const card = e.target.closest('.selected-task-worker-form-card')
    if (!card) return

    const unitRateInput = card.querySelector('.unit-rate-input')
    const hoursAssignedInput = card.querySelector('.hours-assigned-input')

    const id = card.dataset.workerid
    if (!id) return

    if (e.target === unitRateInput) updateWorkerInfoMaps(id, 'name', unitRateInput)
        
    if (e.target === hoursAssignedInput) updateTaskInfoMaps(id, 'hoursAssigned', hoursAssignedInput)
})

/**
 * Produces a merged map of worker info by combining entries from two source collections:
 * - Entries from addedWorkerInfo are copied into the result first.
 * - Entries from changedWorkerInfo are then merged on top of any existing entry for the same id,
 *   so properties from changedWorkerInfo override those from addedWorkerInfo.
 *
 * Behavior and side effects:
 * - Iterates addedWorkerInfo and changedWorkerInfo as iterables of [id, info].
 * - Performs shallow copies of objects; original objects in the source collections are not mutated.
 * - The merge is shallow: properties at the top level in changed entries overwrite those from added entries.
 * - Returns a new Map mapping each id to the merged worker info object.
 * - Does not perform I/O or modify external state beyond reading the source iterables.
 * - If addedWorkerInfo or changedWorkerInfo are not defined or are not iterable, a runtime ReferenceError
 *   or TypeError may be thrown by the engine.
 *
 * @returns {Map<string|number, Object>} Map of worker id to merged worker info (shallow-merged objects)
 *
 * PHPDoc:
 * @return \Map|string[]|array Map of worker id to merged worker info (shallow-merged associative arrays/objects)
 * @throws \ReferenceError If the source iterables (addedWorkerInfo or changedWorkerInfo) are not available
 */
export function getMergedAddedAndChangedWorkersMap() {
    const merged = new Map()

    for (const [id, added] of addedWorkerInfo) {
        merged.set(id, { ...added })
    }

    for (const [id, changed] of changedWorkerInfo) {
        const base = merged.get(id) ?? {}
        merged.set(id, {
            ...base, ...changed
        })
    }

    return merged
}