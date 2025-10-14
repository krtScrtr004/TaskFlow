/**
 * Creates a user grid card element
 * @param {Object} user - User/Worker object with properties
 * @returns {HTMLElement} - User grid card button element
 */
export function createUserGridCard(user) {
    const name = `${user.firstName} ${user.lastName}`
    const id = user.publicId || user.id
    const email = user.email
    const contact = user.contactNumber
    const role = user.role?.displayName || user.role
    const profileLink = user.profileLink || 'asset/image/icon/profile_w.svg'
    
    // Check if we're on the users page
    const isUsersPage = window.location.pathname.includes('users')
    
    // Create card button
    const card = document.createElement('button')
    card.className = 'user-grid-card unset-button'
    card.dataset.userid = id
    
    // User Primary Info Section
    const primaryInfo = document.createElement('section')
    primaryInfo.className = 'user-primary-info flex-row flex-child-center-h'
    
    const profileImg = document.createElement('img')
    profileImg.className = 'circle fit-contain'
    profileImg.src = profileLink
    profileImg.alt = name
    profileImg.title = name
    profileImg.height = 32
    
    const nameContainer = document.createElement('div')
    nameContainer.className = 'flex-col'
    
    const userName = document.createElement('h3')
    userName.className = 'user-name start-text'
    userName.textContent = name
    
    const userId = document.createElement('p')
    userId.className = 'user-id start-text'
    const userIdEm = document.createElement('em')
    userIdEm.textContent = id
    userId.appendChild(userIdEm)
    
    nameContainer.appendChild(userName)
    nameContainer.appendChild(userId)
    
    primaryInfo.appendChild(profileImg)
    primaryInfo.appendChild(nameContainer)
    
    card.appendChild(primaryInfo)
    
    // Role Badge (only on users page)
    if (isUsersPage) {
        const roleBadge = document.createElement('div')
        roleBadge.className = 'role-badge badge center-child white-bg'
        
        const roleText = document.createElement('p')
        const roleStrong = document.createElement('strong')
        roleStrong.className = 'user-role black-text'
        roleStrong.textContent = role.charAt(0).toUpperCase() + role.slice(1)
        roleText.appendChild(roleStrong)
        
        roleBadge.appendChild(roleText)
        card.appendChild(roleBadge)
    }
    
    // User Statistics Section
    const statistics = document.createElement('section')
    statistics.className = 'user-statistics flex-col'
        
    if (isUsersPage) {
        const completedProjects = document.createElement('p')
        completedProjects.textContent = `Completed Projects: ${user.totalProjects || 0}`
        statistics.appendChild(completedProjects)
    } else {
        const completedTasks = document.createElement('p')
        completedTasks.textContent = `Completed Tasks: ${user.totalTasks || 0}`
        statistics.appendChild(completedTasks)
    }
    
    const performanceScore = document.createElement('p')
    performanceScore.textContent = `Performance: ${user.performance || 0}%`
    statistics.appendChild(performanceScore)
    
    card.appendChild(statistics)
    
    // Horizontal Rule
    const hr = document.createElement('hr')
    card.appendChild(hr)
    
    // User Contact Info Section
    const contactInfo = document.createElement('section')
    contactInfo.className = 'user-contact-info flex-col'
    
    // Email
    const emailDiv = document.createElement('div')
    emailDiv.className = 'text-w-icon'
    
    const emailIcon = document.createElement('img')
    emailIcon.src = 'asset/image/icon/email_w.svg'
    emailIcon.alt = 'Worker Email'
    emailIcon.title = 'Worker Email'
    emailIcon.height = 20
    
    const emailText = document.createElement('p')
    emailText.textContent = `Email: ${email}`
    
    emailDiv.appendChild(emailIcon)
    emailDiv.appendChild(emailText)
    
    // Contact Number
    const contactDiv = document.createElement('div')
    contactDiv.className = 'text-w-icon'
    
    const contactIcon = document.createElement('img')
    contactIcon.src = 'asset/image/icon/contact_w.svg'
    contactIcon.alt = 'Contact Number'
    contactIcon.title = 'Contact Number'
    contactIcon.height = 20
    
    const contactText = document.createElement('p')
    contactText.textContent = `Contact: ${contact}`
    
    contactDiv.appendChild(contactIcon)
    contactDiv.appendChild(contactText)
    
    contactInfo.appendChild(emailDiv)
    contactInfo.appendChild(contactDiv)
    
    card.appendChild(contactInfo)
    
    // Worker Status (if user is a worker and has status)
    if (user.status) {
        const statusSection = document.createElement('section')
        statusSection.className = 'user-status flex-col flex-child-end-h flex-child-end-v'
        
        const statusBadge = createStatusBadge(user.status)
        statusSection.appendChild(statusBadge)
        
        card.appendChild(statusSection)
    }
    
    const userGrid = document.querySelector('.user-grid')
    userGrid.appendChild(card)
}

/**
 * Creates a status badge element
 * @param {string} status - Worker status
 * @returns {HTMLElement} - Status badge element
 */
function createStatusBadge(status) {
    const badge = document.createElement('span')
    badge.className = 'worker-badge badge'
    
    // Map status to display text and class
    const statusMap = {
        'active': { text: 'Active', class: 'blue-bg white-text' },
        'unassigned': { text: 'Unassigned', class: 'yellow-bg black-text' },
        'onLeave': { text: 'On Leave', class: 'orange-bg white-text' },
        'terminated': { text: 'Terminated', class: 'red-bg white-text' }
    }
    
    const statusInfo = statusMap[status] || { text: status, class: 'default-bg' }
    
    badge.className += ` ${statusInfo.class}`
    badge.textContent = statusInfo.text
    
    return badge
}