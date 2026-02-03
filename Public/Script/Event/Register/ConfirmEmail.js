import { Http } from '../../Utility/Http.js'
import { handleException } from '../../Utility/HandleException.js'

try {
    const token = new URLSearchParams(window.location.search).get('token')
    if (!token || token.trim() === '') throw new Error('Invalid or missing token')

    await Http.POST('auth/confirm-email', { token } )
} catch (error) {
    handleException(error)
}