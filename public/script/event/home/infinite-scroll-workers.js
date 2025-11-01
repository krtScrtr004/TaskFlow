import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { createWorkerListCard } from './create-worker-list-card.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false
const projectContainer = document.querySelector('.project-container')
const workerList = projectContainer?.querySelector('.worker-list > .list')
const sentinel = projectContainer?.querySelector('.sentinel')
const projectId = projectContainer?.dataset.projectid
if (!projectId || projectId.trim() === '')
    console.warn('Project ID not found.')

if (!workerList)
    console.warn('Worker List element not found.')

if (!sentinel)
    console.warn('Sentinel element not found.')

try {
    infiniteScroll(
        workerList,
        sentinel,
        (offset) => asyncFunction(offset),
        (worker) => createWorkerListCard(worker),
        getExistingItemsCount()
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    return queryParams.get('offset') ??
        workerList.querySelectorAll('.worker-list-card').length
}

async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (isNaN(offset) || offset < 0) {
            throw new Error('Invalid offset value.')
        }

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID not found.')
        }

        const queryParams = new URLSearchParams()
        queryParams.append('status', 'assigned')
        queryParams.append('excludeProjectTerminated', 'true')
        queryParams.append('offset', offset)

        const endpoint = `projects/${projectId}/workers?${queryParams.toString()}`
        const response = await Http.GET(endpoint)
        if (!response?.data) {
            throw error
        }

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}