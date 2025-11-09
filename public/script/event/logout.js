import { Http } from '../utility/http.js'
import { handleException } from '../utility/handle-exception.js'

let isLoading = false
const logoutButton = document.querySelector('#logout')
if (!logoutButton) {
    console.warn('Logout button not found.')
} else {
    logoutButton.addEventListener('click', async e => {
        e.preventDefault()
        try {
            if (isLoading) return
            isLoading = true

            await Http.POST('auth/logout')
            window.location.href = '/TaskFlow/login'
        } catch (error) {
            handleException(error)
        } finally {
            isLoading = false
        }
    })
}