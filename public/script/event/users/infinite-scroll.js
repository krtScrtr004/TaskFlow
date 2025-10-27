import { infiniteScroll } from '../../utility/infinite-scroll.js'
import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { createUserGridCard } from './create-user-grid-card.js'
import { handleException } from '../../utility/handle-exception.js'


let isLoading = false
const userGridContainer = document.querySelector('.user-grid-container')
const userGrid = userGridContainer?.querySelector('.user-grid')
const sentinel = userGridContainer?.querySelector('.sentinel')
const projectId = userGridContainer?.dataset.projectid
if (!projectId || projectId.trim() === '')
    console.warn('Project ID not found.')

if (!userGridContainer)
    console.warn('User Grid Container element not found.')

if (!sentinel)
    console.warn('Sentinel element not found.')

try {
    infiniteScroll(
        userGrid,
        sentinel,
        (offset) => asyncFunction(offset),
        (user) => createUserGridCard(user),
        getExistingItemsCount()
    )
} catch (error) {
    handleException(error, 'Error initializing infinite scroll:', error)
}

function getExistingItemsCount() {
    const queryParams = new URLSearchParams(window.location.search)
    return queryParams.get('offset') ?? userGrid.querySelectorAll('.user-grid-card').length
}

async function asyncFunction(offset) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!offset || isNaN(offset) || offset < 0) {
            throw new Error('Invalid offset value.')
        }

        if (!projectId || projectId.trim() === '') {
            throw new Error('Project ID not found.')
        }

        const response = await Http.GET(`users?offset=${offset}`)
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