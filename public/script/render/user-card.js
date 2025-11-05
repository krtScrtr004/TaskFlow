import { Loader } from './loader.js'

/**
 * Renders and populates the User Info Card for a given user.
 *
 * This function fetches user information asynchronously and displays it in a user info card template.
 * It handles loading states, error cases, and ensures the template is properly shown or hidden.
 *
 * @param {string} userId The unique identifier of the user whose information is to be displayed.
 * @param {function} asyncFunction An asynchronous function that takes a userId and returns user data (Promise).
 *      - Should resolve to an array of user objects, where the first element contains user details.
 *      - Should reject or return null/undefined if user is not found.
 *
 * @throws {Error} If userId is missing or empty.
 * @throws {Error} If asyncFunction is not a valid function.
 * @throws {Error} If the user info card template is not found in the DOM.
 * @throws {Error} If user data cannot be fetched or is not found.
 *
 * @returns {Promise<void>} Resolves when the user info card is rendered or hidden based on the result.
 */
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

/**
 * Populates a user card DOM element with user information.
 *
 * This function updates the provided card element with user details such as profile picture,
 * name, ID, job titles, bio, statistics, performance, email, and contact number. It adapts
 * the displayed statistics based on whether the current page is the home page or not.
 *
 * @param {HTMLElement} card The DOM element representing the user card to populate.
 * @param {Object} user The user data object containing the following properties:
 *      - id: string|number User's unique identifier
 *      - firstName: string User's first name
 *      - lastName: string User's last name
 *      - jobTitles: string[] Array of user's job titles
 *      - bio: string User's biography
 *      - profileLink: string (optional) URL to user's profile picture
 *      - email: string User's email address
 *      - contactNumber: string User's contact number
 *      - additionalInfo: Object Additional user statistics, including:
 *          - totalTasks: number Total tasks (for home page)
 *          - completedTasks: number Completed tasks (for home page)
 *          - totalProjects: number Total projects (for other pages)
 *          - completedProjects: number Completed projects (for other pages)
 *          - performance: number User's performance percentage
 * 
 * @returns {void}
 */
function addInfoToCard(card, user) {
    const ICON_PATH = 'asset/image/icon/'

    // Get DOM elements within the card
    const domElements = getCardDomElements(card)
    const {
        userProfilePicture, userName, userId, userBio,
        userTotalStatistics, userCompletedStatistics, userPerformance,
        userEmail, userContact, userJobTitles
    } = domElements

    // Determine if the current page is the home page, to adjust statistics content
    const isHomePage = window.location.href.includes('home')
    const isTaskPage = window.location.href.includes('task')

    // Add user info to card
    userProfilePicture.src = user.profileLink ?? `${ICON_PATH}profile_w.svg`
    userName.textContent = `${user.firstName} ${user.lastName}` ?? 'Unknown'
    userId.textContent = user.id ?? 'N/A'
    userJobTitles.innerHTML = user.jobTitles.map(title =>
        `<span class="job-title-chip">${title}</span>`
    ).join('')
    userBio.textContent = user.bio ?? 'No bio available'
    userTotalStatistics.textContent = (isHomePage || isTaskPage) 
        ? user.additionalInfo.totalTasks 
        : user.additionalInfo.totalProjects
    userCompletedStatistics.textContent = (isHomePage || isTaskPage)
        ? user.additionalInfo.completedTasks 
        : user.additionalInfo.completedProjects
    userPerformance.textContent = (user.additionalInfo.performance ?? 0) + '%'
    userEmail.textContent = user.email ?? 'N/A'
    userContact.textContent = user.contactNumber ?? 'N/A'

    closeUserInfoCard(card)
}

/**
 * Attaches a close event handler to a user info card element, allowing it to be hidden and cleared when the close button is clicked.
 *
 * This function:
 * - Finds the close button within the provided card element.
 * - Adds a click event listener to the close button.
 * - When clicked, hides the card by adding the 'no-display' class and removing the 'flex-col' class.
 * - Clears all user information fields within the card, including profile picture, name, ID, bio, statistics, performance, email, and contact.
 *
 * @param {HTMLElement} card The DOM element representing the user info card. Must contain:
 *      - A close button with id 'user_info_card_close_button'
 *      - User info fields as returned by getCardDomElements(card):
 *          - userProfilePicture: HTMLImageElement
 *          - userName: HTMLElement
 *          - userId: HTMLElement
 *          - userBio: HTMLElement
 *          - userTotalStatistics: HTMLElement
 *          - userCompletedStatistics: HTMLElement
 *          - userPerformance: HTMLElement
 *          - userEmail: HTMLElement
 *          - userContact: HTMLElement
 *
 * @returns {void}
 */
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

/**
 * Retrieves references to key DOM elements within a user card element.
 *
 * This function queries the provided card element for specific child elements
 * representing user information and statistics, returning an object containing
 * references to these elements for further manipulation.
 *
 * @param {HTMLElement} card The root DOM element representing the user card.
 *      Should contain the following elements:
 *      - .user-profile-picture: User's profile image element
 *      - .user-name: Element displaying the user's name
 *      - .user-id: Element displaying the user's ID
 *      - .user-job-titles: Element displaying the user's job titles
 *      - .user-bio: Element displaying the user's biography
 *      - .user-total-statistics h4: Element displaying total statistics
 *      - .user-completed-statistics h4: Element displaying completed statistics
 *      - .user-performance h4: Element displaying performance statistics
 *      - .user-email: Element displaying the user's email address
 *      - .user-contact: Element displaying the user's contact information
 *
 * @return {Object} An object containing references to the queried DOM elements:
 *      - userProfilePicture: HTMLElement|null
 *      - userName: HTMLElement|null
 *      - userId: HTMLElement|null
 *      - userJobTitles: HTMLElement|null
 *      - userBio: HTMLElement|null
 *      - userTotalStatistics: HTMLElement|null
 *      - userCompletedStatistics: HTMLElement|null
 *      - userPerformance: HTMLElement|null
 *      - userEmail: HTMLElement|null
 *      - userContact: HTMLElement|null
 */
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

