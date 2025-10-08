
import { Http } from '../../utility/http.js'
import { Loader } from '../../render/loader.js'
import { Dialog } from '../../render/dialog.js'
import { Notification } from '../../render/notification.js'

let isLoading = false

const workerList = document.querySelector('.project-workers > .worker-list')
if (workerList) {
    const projectContainer = document.querySelector('.project-container')
    const projectId = projectContainer.dataset.projectid
    if (!projectId || projectId.trim() === '') {
        console.error('Project ID is missing.')
        Dialog.somethingWentWrong()
    } else {
        const workerListCards = workerList.querySelectorAll('.worker-list-card')
        workerListCards.forEach(card => {
            card.addEventListener('click', () => {
                const workerId = card.getAttribute('data-id')
                try {
                    createWorkerInfoCard(projectId, workerId)
                } catch (error) {
                    console.error(`Error fetching worker info: ${error.message}`)
                }
            })
        })
    }

} else {
    console.error('Worker list container not found!')
    Dialog.somethingWentWrong()
}

async function fetchWorkerInfo(projectId, workerId) {
    isLoading = true
    try {
        if (!projectId || projectId === '') 
            throw new Error('Project ID is required.')

        if (!workerId || workerId === '')
            throw new Error('Worker ID is required.')
        
        const response = await Http.GET(`projects/${projectId}/workers/${workerId}`)
        if (!response) 
            throw new Error('No response from server')
        
        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}

async function createWorkerInfoCard(projectId, workerId) {
    const workerInfoCardTemplate = document.querySelector('#worker_info_card_template')
    if (!workerInfoCardTemplate) {
        throw new Error('Worker Info Card template not found!')
    }

    workerInfoCardTemplate.classList.add('flex-col')
    workerInfoCardTemplate.classList.remove('no-display')

    workerInfoCardTemplate.setAttribute('data-workerid', workerId)

    Loader.full(workerInfoCardTemplate.querySelector('.worker-info-card'))

    const worker = await fetchWorkerInfo(projectId, workerId)
    if (!worker || worker.length === 0) {
        Loader.delete()
        Notification.error('Failed to fetch worker information. Please try again later.', 3000)

        workerInfoCardTemplate.classList.remove('flex-col')
        workerInfoCardTemplate.classList.add('no-display')

        return
    }

    addInfoToCard(workerInfoCardTemplate, worker[0])

    Loader.delete()
}

function addInfoToCard(card, worker) {
    const ICON_PATH = 'asset/image/icon/'

    const domElements = getCardDomElements(card)
    const {
        workerProfilePicture, workerName, workerId, workerBio,
        workerTotalTasks, workerCompletedTasks, workerPerformance,
        workerEmail, workerContact, workerJobTitles
    } = domElements

    // Add worker info to card
    workerProfilePicture.src = worker.profilePicture ?? `${ICON_PATH}profile_w.svg`
    workerName.textContent = worker.name ?? 'Unknown'
    workerId.textContent = worker.id ?? 'N/A'
    workerJobTitles.innerHTML = worker.jobTitles.map(title =>
        `<span class="job-title-chip">${title}</span>`
    ).join('')
    workerBio.textContent = worker.bio ?? 'No bio available'
    workerTotalTasks.textContent = worker.totalTasks ?? 0
    workerCompletedTasks.textContent = worker.completedTasks ?? 0
    workerPerformance.textContent = (worker.performance ?? 0) + '%'
    workerEmail.textContent = worker.email ?? 'N/A'
    workerContact.textContent = worker.contactNumber ?? 'N/A'

    closeWorkerInfoCard(card)
}

function closeWorkerInfoCard(card) {
    const closeButton = card.querySelector('#worker_info_card_close_button')
    closeButton.addEventListener('click', () => {
        card.classList.add('no-display')
        card.classList.remove('flex-col')

        const domElements = getCardDomElements(card)
        const {
            workerProfilePicture, workerName, workerId, workerBio,
            workerTotalTasks, workerCompletedTasks, workerPerformance,
            workerEmail, workerContact
        } = domElements
        // Remove recent worker info
        workerProfilePicture.src = ''
        workerName.textContent = ''
        workerId.textContent = ''
        workerBio.textContent = ''
        workerTotalTasks.textContent = ''
        workerCompletedTasks.textContent = ''
        workerPerformance.textContent = ''
        workerEmail.textContent = ''
        workerContact.textContent = ''
    })
}

function getCardDomElements(card) {
    return {
        workerProfilePicture: card.querySelector('.worker-profile-picture'),
        workerName: card.querySelector('.worker-name'),
        workerId: card.querySelector('.worker-id'),
        workerJobTitles: card.querySelector('.worker-job-titles'),
        workerBio: card.querySelector('.worker-bio'),
        workerTotalTasks: card.querySelector('.worker-total-tasks h4'),
        workerCompletedTasks: card.querySelector('.worker-completed-tasks h4'),
        workerPerformance: card.querySelector('.worker-performance h4'),
        workerEmail: card.querySelector('.worker-email'),
        workerContact: card.querySelector('.worker-contact')
    }
}

