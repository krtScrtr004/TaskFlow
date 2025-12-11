/**
 * Creates a debounced version of the provided callback function.
 *
 * The debounced function delays invoking the callback until after the specified delay
 * has elapsed since the last time the debounced function was called. This is useful
 * for limiting the rate at which a function can fire, such as handling user input events.
 *
 * @param {Function} callback The function to debounce. It will be called with the same context and arguments as the debounced function.
 * @param {number} [delay=300] The number of milliseconds to delay. Defaults to 300ms if not provided.
 * @returns {Function} A debounced version of the callback function.
 */
export function debounce(callback, delay = 300) {
    let timeoutId;

    return function (...args) {
        clearTimeout(timeoutId) // Clear previous timer
        timeoutId = setTimeout(() => {
            callback.apply(this, args) // Call with correct context and arguments
        }, delay)
    }
}

/**
 * Creates a debounced version of an asynchronous function that delays its execution until after a specified delay has elapsed since the last time it was invoked.
 *
 * If the debounced function is called again before the delay has passed, the previous pending Promise is rejected with an Error('Debounced').
 * Only the last invocation within the delay period will execute the callback.
 *
 * @param {function(...*): Promise<*>} callback The asynchronous function to debounce. It should return a Promise.
 * @param {number} delay The number of milliseconds to delay invocation.
 * @returns {function(...*): Promise<*>} A debounced function that returns a Promise resolving to the callback's result, or rejecting if debounced or if the callback throws.
 */
export function debounceAsync(callback, delay) {
    let timer
    let pendingReject

    return function (...args) {
        // Cancel previous pending Promise
        if (pendingReject) {
            pendingReject(new Error('Debounced'))
        }

        return new Promise((resolve, reject) => {
            pendingReject = reject
            clearTimeout(timer)

            timer = setTimeout(async () => {
                try {
                    const result = await callback.apply(this, args)
                    resolve(result)
                } catch (err) {
                    reject(err)
                }
            }, delay)
        })
    }
}
