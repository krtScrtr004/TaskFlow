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

/**
 * Formats a date string into a human-readable date.
 *
 * This function parses the provided date string and returns it in the format:
 * "Month Day, Year" (e.g., "January 1, 2024"), using the user's locale.
 * Throws an error if the input is missing or invalid.
 *
 * @param {string} dateString The date string to format. Should be a valid date string parseable by the Date constructor.
 * @throws {Error} If the date string is not provided or is invalid.
 * @return {string} The formatted date string in "Month Day, Year" format.
 */
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

/**
 * Formats a Date object into a string in the format 'YYYY-MM-DD'.
 *
 * This function takes a JavaScript Date object and returns a string
 * representing the date in ISO 8601 format (without the time component).
 * Throws an error if the input is not a valid Date object.
 *
 * @param {Date} date The Date object to format.
 * @throws {Error} If the date parameter is missing or not a valid Date object.
 * @returns {string} The formatted date string in 'YYYY-MM-DD' format.
 */
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
 * Compares two date strings by date only (ignores time).
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

    // Compare only the date part (YYYY-MM-DD)
    const ymd1 = `${d1.getFullYear()}-${String(d1.getMonth() + 1).padStart(2, '0')}-${String(d1.getDate()).padStart(2, '0')}`
    const ymd2 = `${d2.getFullYear()}-${String(d2.getMonth() + 1).padStart(2, '0')}-${String(d2.getDate()).padStart(2, '0')}`

    if (ymd1 > ymd2) return -1
    if (ymd1 < ymd2) return 1
    return 0
}

/**
 * Normalizes a date string to ISO 8601 format (YYYY-MM-DD).
 * 
 * Handles different browser date input formats and ensures consistent
 * date formatting regardless of browser or locale settings. This is particularly
 * useful for HTML5 date inputs which may display differently across browsers
 * but should be sent to the backend in a standardized format.
 * 
 * @param {string} dateString - The date string from HTML5 date input or other source
 * @returns {string} Normalized date in YYYY-MM-DD format, or empty string if input is falsy
 * 
 * @example
 * // All of these return '2025-11-15'
 * normalizeDateFormat('2025-11-15')
 * normalizeDateFormat('11/15/2025')
 * normalizeDateFormat(new Date('2025-11-15').toString())
 */
export function normalizeDateFormat(dateString) {
    if (!dateString) {
        return ''
    }
    
    // Parse the date string into a Date object
    const date = new Date(dateString)
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
        console.warn(`Invalid date: ${dateString}`)
        return dateString
    }
    
    // Return ISO format: YYYY-MM-DD
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    
    return `${year}-${month}-${day}`
}

/**
 * Constructs a full name string from provided name parts.
 *
 * This function:
 * - Uses firstName as the base (falls back to an empty string if falsy)
 * - Appends middleName and lastName if they are provided, each preceded by a single space
 * - Trims leading/trailing whitespace to avoid extra spaces when parts are missing
 *
 * @param {string|null|undefined} firstName The user's first name.
 * @param {string|null|undefined} middleName Optional middle name; appended if truthy.
 * @param {string|null|undefined} lastName Optional last name; appended if truthy.
 * 
 * @returns {string} The combined full name, trimmed. Returns an empty string when no parts are provided.
 */
export function createFullName(firstName, middleName, lastName) {
    let fullName = firstName || ''
    if (middleName) {
        fullName += ` ${middleName.charAt(0)}.`
    }
    if (lastName) {
        fullName += ` ${lastName}`
    }
    return fullName.trim()
}