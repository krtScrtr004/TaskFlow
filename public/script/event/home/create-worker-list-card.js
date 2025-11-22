/**
 * Creates a worker list card DOM element
 * @param {Object} worker - The worker data object
 * @param {string} worker.id - Worker's public ID
 * @param {string} worker.name - Worker's full name (or firstName + lastName)
 * @param {string} [worker.firstName] - Worker's first name (if name not provided)
 * @param {string} [worker.lastName] - Worker's last name (if name not provided)
 * @param {string} [worker.profileLink] - URL to worker's profile picture
 * @param {string} [worker.profilePicture] - Alternative property for profile picture URL
 * @param {Array<string>} [worker.jobTitles] - Array of job titles
 * @returns {HTMLElement} The worker list card button element
 */
export function createWorkerListCard(worker) {
    const ICON_PATH = 'asset/image/icon/'

    // Determine profile picture URL
    const profileLink = worker.profileLink ||
        worker.profilePicture ||
        ICON_PATH + 'profile_w.svg'

    // Determine worker name
    const name = worker.name ||
        `${worker.firstName || ''} ${worker.lastName || ''}`.trim()

    const id = worker.id
    const jobTitles = worker.jobTitles || []

    // Create main button container
    const button = document.createElement('button')
    button.className = 'user-list-card unset-button'
    button.dataset.id = id

    // Create profile image
    const img = document.createElement('img')
    img.className = 'circle fit-cover'
    img.src = profileLink
    img.alt = name
    img.title = name
    img.height = 40

    // Create info container
    const infoContainer = document.createElement('div')
    infoContainer.className = 'flex-col'

    // Create name and ID section
    const nameIdSection = document.createElement('div')

    const nameHeader = document.createElement('h4')
    nameHeader.className = 'wrap-text'
    nameHeader.textContent = name
    nameIdSection.appendChild(nameHeader)

    const idPara = document.createElement('p')
    const idEm = document.createElement('em')
    idEm.textContent = id
    idPara.appendChild(idEm)
    nameIdSection.appendChild(idPara)

    // Create job titles section
    const jobTitlesDiv = document.createElement('div')
    jobTitlesDiv.className = 'job-titles flex-row flex-wrap'

    if (jobTitles && jobTitles.length > 0) {
        jobTitles.forEach(jobTitle => {
            const span = document.createElement('span')
            span.className = 'job-title-chip'
            span.textContent = jobTitle
            jobTitlesDiv.appendChild(span)
        })
    }

    // Assemble the components
    infoContainer.appendChild(nameIdSection)
    infoContainer.appendChild(jobTitlesDiv)

    button.appendChild(img)
    button.appendChild(infoContainer)

    const workerList = document.querySelector('.project-workers > .worker-list > .list')
    if (!workerList) {
        console.error('Worker list container not found!')
    } else {
        const noWorkersWall = workerList.parentElement.querySelector('.no-workers-wall')
        noWorkersWall?.classList.remove('flex-col', 'flex-row')
        noWorkersWall?.classList.add('no-display')

        workerList.appendChild(button)
    }
}
