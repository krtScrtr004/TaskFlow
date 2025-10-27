import { Dialog } from '../render/dialog.js'
import { errorListDialog } from '../render/error-list-dialog.js'

export function handleException(exception, message = exception?.message || 'An error occurred') {
    console.error(`${message}: ${exception}`)

    if (!exception.hasOwnProperty('errors') || exception.status >= 500) {
        Dialog.somethingWentWrong()
    } else {
        errorListDialog(exception?.message || message, exception.errors)
    }
}