import { Loader } from './loader.js'

export async function userInfoCard(userId, asyncFunction) {
    if (!userId || userId.trim() === '') {
        throw new Error('User ID is required to fetch user info.')
    }

    if (!asyncFunction || typeof asyncFunction !== 'function') {
        throw new Error('A valid function to fetch user info must be provided.')
    }

    const userInfoCardTemplate = document.querySelector('#user_info_card_template')
    if (!userInfoCardTemplate) {
        throw new Error('User Info Card template not found!')
    }

    userInfoCardTemplate.classList.add('flex-col')
    userInfoCardTemplate.classList.remove('no-display')

    userInfoCardTemplate.setAttribute('data-userid', userId)

    Loader.full(userInfoCardTemplate.querySelector('.user-info-card'))

    try {
        const user = await asyncFunction(userId)
        if (!user) {
            userInfoCardTemplate.classList.remove('flex-col')
            userInfoCardTemplate.classList.add('no-display')

            throw error
        }
        addInfoToCard(userInfoCardTemplate, user[0])
    } catch (error) {
        throw error
    } finally {
        Loader.delete()
    }
}

function addInfoToCard(card, user) {
    const ICON_PATH = 'asset/image/icon/'

    const domElements = getCardDomElements(card)
    const {
        userProfilePicture, userName, userId, userBio,
        userTotalStatistics, userCompletedStatistics, userPerformance,
        userEmail, userContact, userJobTitles
    } = domElements

    const isHomePage = window.location.href.includes('home')

    // Add user info to card
    userProfilePicture.src = user.profileLink ?? `${ICON_PATH}profile_w.svg`
    userName.textContent = `${user.firstName} ${user.lastName}` ?? 'Unknown'
    userId.textContent = user.id ?? 'N/A'
    userJobTitles.innerHTML = user.jobTitles.map(title =>
        `<span class="job-title-chip">${title}</span>`
    ).join('')
    userBio.textContent = user.bio ?? 'No bio available'
    userTotalStatistics.textContent = (isHomePage) 
        ? user.additionalInfo.totalTasks 
        : user.additionalInfo.totalProjects
    userCompletedStatistics.textContent = (isHomePage)
        ? user.additionalInfo.completedTasks 
        : user.additionalInfo.completedProjects
    userPerformance.textContent = (user.additionalInfo.performance ?? 0) + '%'
    userEmail.textContent = user.email ?? 'N/A'
    userContact.textContent = user.contactNumber ?? 'N/A'

    closeUserInfoCard(card)
}

function closeUserInfoCard(card) {
    const closeButton = card.querySelector('#user_info_card_close_button')
    closeButton.addEventListener('click', () => {
        card.classList.add('no-display')
        card.classList.remove('flex-col')

        const domElements = getCardDomElements(card)
        const {
            userProfilePicture, userName, userId, userBio,
            userTotalStatistics, userCompletedStatistics, userPerformance,
            userEmail, userContact
        } = domElements
        // Remove recent user info
        userProfilePicture.src = ''
        userName.textContent = ''
        userId.textContent = ''
        userBio.textContent = ''
        userTotalStatistics.textContent = ''
        userCompletedStatistics.textContent = ''
        userPerformance.textContent = ''
        userEmail.textContent = ''
        userContact.textContent = ''
    })
}

function getCardDomElements(card) {
    return {
        userProfilePicture: card.querySelector('.user-profile-picture'),
        userName: card.querySelector('.user-name'),
        userId: card.querySelector('.user-id'),
        userJobTitles: card.querySelector('.user-job-titles'),
        userBio: card.querySelector('.user-bio'),
        userTotalStatistics: card.querySelector('.user-total-statistics h4'),
        userCompletedStatistics: card.querySelector('.user-completed-statistics h4'),
        userPerformance: card.querySelector('.user-performance h4'),
        userEmail: card.querySelector('.user-email'),
        userContact: card.querySelector('.user-contact')
    }
}

