import { Http } from '../Utility/Http.js'
import { handleException } from '../Utility/HandleException.js'

let isLoading = false

const logoutButton = document.querySelector('#logout')
if (!logoutButton) console.warn('Logout button not found')

logoutButton?.addEventListener('click', async e => {
    e.preventDefault()
    try {
        if (isLoading) return
        isLoading = true

        await Http.POST('auth/logout')
        window.location.href = '/login'
    } catch (error) {
        handleException(error)
    } finally {
        isLoading = false
    }
})
