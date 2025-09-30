export function debounce(callback, delay = 300) {
    let timeoutId;

    return function (...args) {
        clearTimeout(timeoutId) // Clear previous timer
        timeoutId = setTimeout(() => {
            callback.apply(this, args) // Call with correct context and arguments
        }, delay)
    }
}