import { Http } from './http.js'
import { Dialog } from '../render/dialog.js'
import { debounceAsync } from './debounce.js'
import { confirmationDialog } from '../render/confirmation-dialog.js'

let isLoading = false
let targetUser = null
const userInfoCardTemplate = document.querySelector('#user_info_card_template')
let thisUserContainer = null

export function terminateWorker(projectId, userContainer, userCardSelector) {
    if (!userContainer)
        throw new Error('user container element is required.')

    thisUserContainer = userContainer

    const userCard = userContainer.querySelector(userCardSelector)
    if (!userCard)
        throw new Error('user card element is required.')

    userContainer.addEventListener('click', e => {
        targetUser = e.target.closest(userCardSelector)
    })

    const terminateUserButton = userInfoCardTemplate.querySelector('#terminate_worker_button')
    if (!terminateUserButton)
        throw new Error('Terminate user button not found.')
    terminateUserButton.addEventListener('click', e => debounceAsync(terminateButtonEvent(e, projectId, userCardSelector), 300))
}

async function terminateButtonEvent(e, projectId, userCardSelector) {
    e.preventDefault()

    const userId = userInfoCardTemplate.dataset.userid
    if (!userId) {
        console.error('User ID not found.')
        Dialog.somethingWentWrong()
        return
    }

    if (!await confirmationDialog(
        'Terminate user',
        `Are you sure you want to terminate this user?`,
    )) return

    if (!projectId) {
        console.error('Project ID not found in modal dataset.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        await sendToBackend(projectId, userId)
        Dialog.operationSuccess(
            'User Terminated.',
            'The user has been successfully terminated.'
        )

        // Remove worker card from UI
        targetUser.remove()

        const remainingWorkerCard = thisUserContainer.querySelectorAll(userCardSelector)
        if (remainingWorkerCard.length === 0) {
            const noWorkersWall = thisUserContainer.querySelector('.no-workers-wall')
                || thisUserContainer.parentElement?.querySelector('.no-workers-wall')
            noWorkersWall?.classList.add('flex-col')
            noWorkersWall?.classList.remove('no-display')
        }
        const closeButton = userInfoCardTemplate.querySelector('#user_info_card_close_button')
        closeButton?.click()
    } catch (error) {
        throw error
    }
}

async function sendToBackend(projectId, userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!projectId || projectId.trim() === '')
            throw new Error('Project ID is required.')

        if (!userId || userId.trim() === '')
            throw new Error('user ID is required.')

        const response = await Http.PUT(`projects/${projectId}/workers/${userId}`, { status: 'terminated' })
        if (!response)
            throw new Error('Failed to terminate worker from project.')
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}