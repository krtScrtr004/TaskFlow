import { userInfoCard } from '../../Render/UserCard.js'
import { Dialog } from '../../Render/Dialog.js'
import { Http } from '../../Utility/Http.js'
import { handleException } from '../../Utility/HandleException.js'
import { die } from '../../Utility/Utility.js'

let isLoading = false

const userGrid = document.querySelector('.user-grid-container > .user-grid')
if (!userGrid) die('User grid container not found')

userGrid?.addEventListener('click', async e => {
    const userCard = e.target.closest('.user-grid-card')
    if (!userCard) return

    const userId = userCard.dataset.userid
    if (!userId || userId.trim() === '') {
        console.error('User ID is missing.')
        Dialog.somethingWentWrong()
        return
    }

    try {
        // Render user info card
        userInfoCard(userId, () => fetchUserInfo(userId))
    } catch (error) {
        handleException(error)
    }
})


/**
 * Fetches user information from the server by user ID.
 *
 * This asynchronous function performs the following:
 * - Prevents concurrent requests by checking and setting a loading flag.
 * - Validates that a user ID is provided.
 * - Sends a GET request to the endpoint `users/{userId}` to retrieve user data.
 * - Handles errors and ensures the loading flag is reset after completion.
 *
 * @param {string} userId The unique identifier of the user to fetch.
 * @returns {Promise<Object>} A promise that resolves to the user data object returned from the server.
 * @throws {Error} If the user ID is missing, the request fails, or the response is invalid.
 */
async function fetchUserInfo(userId) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!userId || userId === '') throw new Error('User ID is required')

        const response = await Http.GET(`users/${userId}`)
        if (!response) throw new Error('No response from server')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}