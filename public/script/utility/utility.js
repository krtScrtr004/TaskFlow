/**
 * @param {number} year
 * @param {number} month - 1-based (1 = January, 12 = December)
 * @param {number} day
 * @returns {boolean}
 */
export function isValidDate(year, month, day) {
    if (
        typeof year !== 'number' ||
        typeof month !== 'number' ||
        typeof day !== 'number'
    ) return false;
    // JavaScript Date months are 0-based, so subtract 1 from month
    const date = new Date(year, month - 1, day);
    return (
        date.getFullYear() === year &&
        date.getMonth() === month - 1 &&
        date.getDate() === day
    );
}

export function formatDate(dateString) {
    if (!dateString) {
        throw new Error('Date string is required.')
    }

    const date = new Date(dateString)
    if (isNaN(date.getTime())) {
        throw new Error('Invalid date string.')
    }

    const options = { year: 'numeric', month: 'long', day: 'numeric' }
    return date.toLocaleDateString(undefined, options)
}

export function formatDateToString(date) {
    if (!date) {
        throw new Error('Date is required.')
    }

    if (!(date instanceof Date)) {
        throw new Error('Invalid \'date\' is not a valid Date object.')
    }

    return date.toISOString().split('T')[0]
}

/**
 * Compares two date strings.
 * @param {string} date1 - First date string to compare
 * @param {string} date2 - Second date string to compare
 * @returns {number} -1 if date1 is later, 1 if date2 is later, 0 if equal
 * @throws {Error} If either date string is invalid or missing
 */
export function compareDates(date1, date2) {
    if (!date1 || !date2) {
        throw new Error('Both date strings are required.')
    }
    
    const d1 = new Date(date1)
    const d2 = new Date(date2)
    
    if (isNaN(d1.getTime()) || isNaN(d2.getTime())) {
        throw new Error('Invalid date string.')
    }
        
    if (d1.getTime() > d2.getTime()) return -1
    if (d1.getTime() < d2.getTime()) return 1
    return 0
}