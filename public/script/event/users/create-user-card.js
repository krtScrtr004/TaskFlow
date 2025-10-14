import { userInfoCard } from '../../render/user-card.js'
import { Dialog } from '../../render/dialog.js'
import { Http } from '../../utility/http.js'

let isLoading = false

const userGrid = document.querySelector('.user-grid-container > .user-grid')
if (userGrid) {
    userGrid.addEventListener('click', async e => {
        const userCard = e.target.closest('.user-grid-card')
        if (!userCard) return

            const userId = userCard.dataset.userid
            if (!userId || userId.trim() === '') {
                console.error('User ID is missing.')
                Dialog.somethingWentWrong()
                return
            }

            try {
                userInfoCard(userId, () => fetchUserInfo(userId))
        } catch (error) {
            console.error(`Error fetching user info: ${error.message}`)
        }
    })
} else {
    console.error('Users grid not found!')
}
    
async function fetchUserInfo(userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '')
            throw new Error('User ID is required.')

        const response = await Http.GET(`users/${userId}`)
        if (!response)
            throw error

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}