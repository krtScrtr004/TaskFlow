export function formatDate(dateString) {
    if (!dateString) 
        throw new Error('Date string is required.')

    const date = new Date(dateString)
    if (isNaN(date.getTime())) 
        throw new Error('Invalid date string.')

    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return date.toLocaleDateString(undefined, options)
}

export function formatDateToString(date) {
    if (!date) 
        throw new Error('Date is required.')

    if (!(date instanceof Date)) 
        throw new Error('Invalid \'date\' is not a valid Date object.')

    return date.toISOString().split('T')[0]
}