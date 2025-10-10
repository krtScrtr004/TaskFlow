import { Loader } from '../render/loader.js'
import { Dialog } from '../render/dialog.js'

let isLoading = false
let offset = 0

export function infiniteScroll(
    container,
    sentinel,
    asyncFunction,
    domCreator,
    existingItemsCount = 0
) {
    if (!container)
        throw new Error('Container element not found.')

    if (!sentinel)
        throw new Error('Sentinel element not found.')

    if (typeof asyncFunction !== 'function')
        throw new Error('asyncFunction must be a function.')

    if (typeof domCreator !== 'function')
        throw new Error('domCreator must be a function.')

    if (isNaN(existingItemsCount) || existingItemsCount < 0)
        throw new Error('existingItemsCount must be a non-negative number.')
    offset = existingItemsCount

    try {
        const observer = createObserver(container, asyncFunction, domCreator, offset)
        if (!observer)
            throw error
        observer.observe(sentinel)
    } catch (error) {
        console.error('Error setting up infinite scroll observer:', error)
        if (error?.status === 401 || error?.status === 403) {
            const message = error.errorData.message || 'You do not have permission to perform this action.'
            Dialog.errorOccurred(message)
        } else {
            Dialog.somethingWentWrong()
        }
    }
}

function createObserver(container, asyncFunction, domCreator, offset) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(async (entry) => {
            if (entry.isIntersecting && !isLoading) {
                const el = entry.target

                try {
                    const response = await asyncFunction(offset)
                    if (response?.length < 1) {
                        observer.unobserve(el)
                        return
                    }

                    offset += response.length
                    response.forEach(item => domCreator(item))

                    isLoading = true
                    Loader.trail(container)
                } catch (error) {
                    throw error
                } finally {
                    isLoading = false
                    Loader.delete()
                }
            }
        })
    })
    return observer
}