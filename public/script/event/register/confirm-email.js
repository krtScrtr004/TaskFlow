import { Http } from '../../utility/http.js'
import { handleException } from '../../utility/handle-exception.js'

try {
    const token = new URLSearchParams(window.location.search).get('token')
    if (!token || token.trim() === '') {
        throw new Error('Invalid or missing token.')
    }

    await Http.POST('auth/confirm-email', { token } )
} catch (error) {
    handleException(error)
}