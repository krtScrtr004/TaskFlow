import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { confirmationDialog } from '../../render/confirmation-dialog.js'
import { debounceAsync } from '../../utility/debounce.js'

let currentInfiniteScrollObserver = null // Store the current observer to allow resetting
let isSelectWorkerEventInitialized = false
export const selectedUsers = []
const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')

export function initializeAddWorkerModal(projectId) {
    searchWorkerEvent(projectId)
    infiniteScrollWorkers(projectId)

    cancelAddWorkerModal()
}

// Search Worker -------------------------

function searchWorkerEvent(projectId) {
    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    const searchBarForm = addWorkerModalTemplate?.querySelector('form.search-bar')
    const button = searchBarForm?.querySelector('button')
    if (!searchBarForm) {
        console.error('Search bar form not found.')
        return
    }

    if (!button) {
        console.error('Search button not found.')
        return
    }

    searchBarForm.addEventListener('submit', e => debounceAsync(searchForWorker(e, projectId), 300))
    button.addEventListener('click', e => debounceAsync(searchForWorker(e, projectId), 300))
}

async function searchForWorker(e, projectId) {
    e.preventDefault()

    const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }
    const noWorkersWall = workerList.parentElement.querySelector('.no-workers-wall')

    workerList.textContent = ''

    // Hide no workers message and show worker list
    noWorkersWall?.classList.remove('flex-col')
    noWorkersWall?.classList.add('no-display')

    workerList.classList.add('flex-col')
    workerList.classList.remove('no-display')

    Loader.full(workerList)

    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    const searchTerm = document.querySelector('.search-bar input[type="text"]').value.trim()

    try {
        const workers = await fetchWorkers(projectId, searchTerm)

        if (workers && workers.length > 0) {
            workers.forEach(worker => createWorkerListCard(worker))

            // Reset and reinitialize infinite scroll with the search term
            infiniteScrollWorkers(projectId, searchTerm)
        } else {
            // Show no workers message if no results
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')

            workerList.classList.remove('flex-col')
            workerList.classList.add('no-display')

            // Disconnect infinite scroll observer when no results
            disconnectInfiniteScroll()
        }
    } catch (error) {
        console.error(error.message)
        Dialog.errorOccurred('Failed to load workers. Please try again.')
    } finally {
        Loader.delete()
    }
}

// Infinite Scroll -------------------------

function infiniteScrollWorkers(projectId, searchKey = '') {
    if (!projectId || projectId.trim() === '')
        throw new Error('Project ID not found.')

    // Disconnect any existing observer before creating a new one
    disconnectInfiniteScroll()

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    const workerList = addWorkerModalTemplate?.querySelector('.worker-list > .list ')
    const sentinel = addWorkerModalTemplate?.parentElement.querySelector('.sentinel')

    if (!workerList)
        throw new Error('Worker List element not found.')

    if (!sentinel)
        throw new Error('Sentinel element not found.')

    try {
        // Create a new observer with a closure that captures the search key
        const observer = createInfiniteScrollObserver(
            workerList,
            sentinel,
            projectId,
            searchKey
        )

        // Store the observer so we can disconnect it later
        currentInfiniteScrollObserver = { observer, sentinel }
    } catch (error) {
        console.error('Error initializing infinite scroll:', error)
        Dialog.somethingWentWrong()
    }
}

function createInfiniteScrollObserver(workerList, sentinel, projectId, searchKey) {
    let offset = getExistingItemsCount()
    let isLoading = false

    const observer = new IntersectionObserver(async (entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting && !isLoading) {
                isLoading = true
                Loader.trail(workerList)

                try {
                    const workers = await fetchWorkers(projectId, searchKey, offset)

                    if (!workers || workers.length === 0) {
                        observer.unobserve(sentinel)
                        return
                    }

                    workers.forEach(worker => createWorkerListCard(worker))
                    offset += workers.length
                } catch (error) {
                    console.error('Error during infinite scroll fetch:', error)
                    Dialog.errorOccurred('Failed to load more workers.')
                } finally {
                    isLoading = false
                    Loader.delete()
                }
            }
        }
    })

    observer.observe(sentinel)
    return observer

    function getExistingItemsCount() {
        return workerList.querySelectorAll('.worker-checkbox').length || 0
    }
}

function disconnectInfiniteScroll() {
    if (currentInfiniteScrollObserver) {
        currentInfiniteScrollObserver.observer.disconnect()
        currentInfiniteScrollObserver = null
    }
}

// Cancel Button -------------------------

function cancelAddWorkerModal(workerContainer = addWorkerModalTemplate.querySelector('.worker-list > .list')) {
    const cancelButton = addWorkerModalTemplate?.querySelector('#cancel_add_worker_button')
    if (!cancelButton) {
        console.error('Cancel button not found.')
        return
    }

    cancelButton.addEventListener('click', () => {
        addWorkerModalTemplate.classList.remove('flex-col')
        addWorkerModalTemplate.classList.add('no-display')

        if (workerContainer) workerContainer.textContent = ''
        selectedUsers.length = 0
    })
}

// Fetch Worker -------------------------

export async function fetchWorkers(projectId, key = null, offset = 0) {
    let isLoading = false
    return await fetchFromDatabase(projectId, key, isLoading, offset)
}

async function fetchFromDatabase(projectId, key = null, isLoading = false, offset = 0) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        const param = (key) ? key : ''
        const response = await Http.GET(`projects/${projectId}/workers?key=${param}&offset=${offset}`)
        if (!response)
            throw new Error('Workers data not found!')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}


// Create Worker List Card -------------------------

export function createWorkerListCard(worker) {
    const ICON_PATH = 'asset/image/icon/'
    const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')

    // Create main container div
    const workerCheckbox = document.createElement('div')
    workerCheckbox.className = 'worker-checkbox flex-row flex-child-center-h'

    // Create checkbox input
    const checkbox = document.createElement('input')
    checkbox.type = 'checkbox'
    checkbox.name = worker.id
    checkbox.id = worker.id
    workerCheckbox.appendChild(checkbox)

    // Create label
    const label = document.createElement('label')
    label.htmlFor = worker.id
    label.className = 'worker-list-card'
    label.dataset.id = worker.id

    // Create image container
    const imgContainer = document.createElement('div')
    imgContainer.className = 'flex-col flex-child-center-v'

    const img = document.createElement('img')
    img.src = worker.profilePicture || ICON_PATH + 'profile_w.svg'
    img.alt = `${worker.firstName} ${worker.lastName}`
    img.title = `${worker.firstName} ${worker.lastName}`
    img.height = 40
    imgContainer.appendChild(img)

    // Create info container
    const infoContainer = document.createElement('div')
    infoContainer.className = 'flex-col'

    // Create name and ID section
    const nameSection = document.createElement('div')

    const nameHeader = document.createElement('h4')
    nameHeader.className = 'wrap-text'
    nameHeader.textContent = `${worker.firstName} ${worker.lastName}`
    nameSection.appendChild(nameHeader)

    const idPara = document.createElement('p')
    const idEm = document.createElement('em')
    idEm.textContent = worker.id
    idPara.appendChild(idEm)
    nameSection.appendChild(idPara)

    // Create job titles section
    const jobTitlesDiv = document.createElement('div')
    jobTitlesDiv.className = 'job-titles flex-row flex-wrap'

    if (worker.jobTitles && worker.jobTitles.length > 0) {
        worker.jobTitles.forEach(title => {
            const span = document.createElement('span')
            span.className = 'job-title-chip'
            span.textContent = title
            jobTitlesDiv.appendChild(span)
        })
    } else {
        const noJobSpan = document.createElement('span')
        noJobSpan.className = 'no-job-title-badge'
        noJobSpan.textContent = 'No Job Titles'
        jobTitlesDiv.appendChild(noJobSpan)
    }

    // Assemble the components
    infoContainer.appendChild(nameSection)
    infoContainer.appendChild(jobTitlesDiv)

    label.appendChild(imgContainer)
    label.appendChild(infoContainer)

    workerCheckbox.appendChild(label)

    workerList.appendChild(workerCheckbox)
}

// Select Worker -------------------------

export function selectWorker() {
    if (isSelectWorkerEventInitialized) return

    const workerList = addWorkerModalTemplate.querySelector('.worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found.')
        Dialog.somethingWentWrong()
        return
    }

    // Use event delegation but be more specific about what triggers the action
    workerList.addEventListener('click', e => {
        // Only proceed if clicked on checkbox, label, or worker-checkbox div
        const workerCheckbox = e.target.closest('.worker-checkbox')
        if (!workerCheckbox) return

        // Prevent multiple triggers
        e.stopPropagation()
        e.preventDefault()

        const checkbox = workerCheckbox.querySelector('input[type="checkbox"]')
        if (!checkbox) return

        // Toggle the checkbox state
        const wasChecked = checkbox.checked
        checkbox.checked = !wasChecked

        // Update selectedUsers map
        const workerId = checkbox.id
        if (checkbox.checked) {
            selectedUsers.push(workerId)
        } else {
            const index = selectedUsers.indexOf(workerId)
            if (index !== -1) {
                selectedUsers.splice(index, 1)
            }
        }
    })

    isSelectWorkerEventInitialized = true
}

// Add Worker -------------------------

export async function addWorker(
    projectId,
    asyncFunction,
    action = () => { },
    onSuccess = () => { }
) {
    if (!asyncFunction || typeof asyncFunction !== 'function') {
        console.error('Invalid asyncFunction provided to addWorker.')
        return
    }

    const addWorkerModalTemplate = document.querySelector('#add_worker_modal_template')
    if (!addWorkerModalTemplate) {
        console.error('Add Worker Modal template not found.')
        Dialog.somethingWentWrong()
        return
    }

    const confirmAddWorkerButton = addWorkerModalTemplate.querySelector('#confirm_add_worker_button')
    if (!confirmAddWorkerButton) {
        console.error('Confirm Add Worker button not found.')
        Dialog.somethingWentWrong()
        return
    }

    confirmAddWorkerButton.addEventListener('click', e => debounceAsync(addWorkerButtonEvent(e, projectId, confirmAddWorkerButton, asyncFunction, action, onSuccess), 300))
}

async function addWorkerButtonEvent(e, projectId, confirmAddWorkerButton, asyncFunction, action, onSuccess) {
    e.preventDefault()

    if (selectedUsers.length === 0) {
        Dialog.errorOccurred('No workers selected. Please select at least one worker to add.')
        return
    }

    if (!await confirmationDialog(
        'Add Workers',
        `Are you sure you want to add ${selectedUsers.length} worker(s) to this project?`,
    )) return

    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    Loader.patch(confirmAddWorkerButton.querySelector('.text-w-icon'))
    try {
        const result = await asyncFunction(projectId, selectedUsers)
        if (typeof action === 'function') action(result)

        // Close the modal
        const cancelButton = addWorkerModalTemplate.querySelector('#cancel_add_worker_button')
        cancelButton?.click()

        if (onSuccess.length > 0) onSuccess(result)
        else onSuccess()
    } catch (error) {
        console.error(error)
        errorListDialog(error?.errors, error?.message)
    } finally {
        Loader.delete()
    }
}