export function debounce(callback, delay = 300) {
    let timeoutId;

    return function (...args) {
        clearTimeout(timeoutId) // Clear previous timer
        timeoutId = setTimeout(() => {
            callback.apply(this, args) // Call with correct context and arguments
        }, delay)
    }
}

export function debounceAsync(fn, delay) {
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
                    const result = await fn.apply(this, args)
                    resolve(result)
                } catch (err) {
                    reject(err)
                }
            }, delay)
        })
    }
}
