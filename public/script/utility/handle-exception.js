import { Dialog } from '../render/dialog.js'
import { errorListDialog } from '../render/error-list-dialog.js'

/**
 * Handles exceptions and displays appropriate dialogs based on the exception status.
 *
 * This function logs the exception and shows a user-friendly dialog depending on the HTTP status code:
 * - For status 422: Shows a dialog with a list of validation errors.
 * - For status 404: Shows a "Resource not found" error dialog.
 * - For status 403: Shows a "Permission denied" error dialog.
 * - For other statuses: Shows a generic "Something went wrong" dialog.
 *
 * @param {Object} exception The exception object, typically an error or HTTP response.
 * @param {string} [message] Optional custom error message. Defaults to exception.message or a generic message.
 *
 * @returns {void}
 */
export function handleException(exception, message = exception?.message || 'An error occurred') {
    console.error(`${message}: ${exception}`)
    const status = exception?.status

    switch (status) {
        case 422:
            errorListDialog(exception?.message || message, exception.errors)
            break
        case 404:
            Dialog.errorOccurred(message || 'Requested resource not found.');
            break
        case 403:
            Dialog.errorOccurred(message || 'You do not have permission to perform this action.')
            break
        default:
            Dialog.somethingWentWrong()
    }
}