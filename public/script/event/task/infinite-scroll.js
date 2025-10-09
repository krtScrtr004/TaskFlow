import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { createTaskGridCard } from './create-task-grid-card.js'

let isLoading = false
const taskGridContainer = document.querySelector('.task-grid-container')
const taskGrid = taskGridContainer?.querySelector('.task-grid')
const sentinel = taskGridContainer?.querySelector('.sentinel')
const projectId = taskGridContainer?.dataset.projectid
if (!projectId || projectId.trim() === '')
    console.warn('Project ID not found.')

if (!taskGridContainer)
    console.warn('Task Grid Container element not found.')

if (!sentinel)
    console.warn('Sentinel element not found.')

try {
    infiniteScroll(
        taskGrid,
        sentinel,
        (offset) => asyncFunction(offset),
        (task) => createTaskGridCard(task),
        getExistingItemsCount()
    )
} catch (error) {
    console.error('Error initializing infinite scroll:', error)
    Dialog.somethingWentWrong()
}

function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    return queryParams.get('offset') ??
        taskGrid.querySelectorAll('.task-grid-card:not(.add-task-button)').length
}

async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!offset || isNaN(offset) || offset < 0)
            throw new Error('Invalid offset value.')

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID not found.')

        const response = await Http.GET(`projects/${projectId}/tasks/?offset=${offset}`)
        if (!response?.data)
            throw new Error('No response from server.')

        return response.data
    } catch (error) {
        console.error('Error during infinite scroll fetch:', error)
        throw error
    } finally {
        isLoading = false
    }
}