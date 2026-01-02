import { Dialog } from '../render/dialog.js'


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
 * Compares two dates by date only (ignores time).
 * @param {Date|string} date1 - First date string to compare
 * @param {Date|string} date2 - Second date string to compare
 * @returns {number} -1 if date1 is later, 1 if date2 is later, 0 if equal
 * @throws {Error} If either date string is invalid or missing
 */
export function compareDates(date1, date2) {
    if (!date1 || !date2) {
        throw new Error('Both date strings are required.')
    }

    const d1 = date1 instanceof Date ? date1 : new Date(date1)
    const d2 = date2 instanceof Date ? date2 : new Date(date2)

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

/**
 * Fetches validation constraints from a JSON file.
 *
 * This function asynchronously retrieves validation constraints from a JSON configuration
 * file located in the backend data directory. It is used to load validation rules
 * for form inputs or data validation throughout the application.
 *
 * @async
 * @returns {Promise<Object>} A promise that resolves to the parsed JSON object containing validation constraints
 * @throws {Error} Throws an error if the fetch request fails or if the JSON parsing fails,
 *      with the original error message appended to 'Error loading validation constraints: '
 */
export async function getValidationConstraints() {
    try {
        const response = await fetch('../../../source/backend/data/validation-constraints.json')
        return await response.json()
    } catch (error) {
        throw new Error('Error loading validation constraints: ' + error.message)
    }
}

/**
 * Terminates execution by throwing an Error and optionally displays an error dialog.
 *
 * @param {string|Error} error - Error instance or message.
 * @param {object} [options]
 * @param {boolean} [options.showDialog=true]
 * @throws {Error}
 */
export function die(error, { showDialog = true } = {}) {
    const err = error instanceof Error
        ? error
        : new Error(String(error))

    if (showDialog) {
        Dialog.errorOccurred(err.message)
    }
    console.error(err.message)

    throw err
}

/**
 * Toggles the display of an element by adding/removing CSS classes.
 *
 * @param {HTMLElement} elem - The element to show or hide.
 * @param {boolean} show - True to show the element, false to hide it.
 * @param {string[]} [displayClasses=['flex-col', 'flex-row', 'block']] - CSS classes to add when showing the element.
 * @param {string[]} [hideClasses=['no-display']] - CSS classes to add when hiding the element.
 */
export function toggleElemDisplay(elem, show, 
    displayClasses = [
        'flex-col', 
        'flex-row', 
        'block'
    ], hideClasses = [
        'no-display'
]) {
    if (show) {
        elem.classList.remove(...hideClasses) 
        elem.classList.add(...displayClasses)
    } else {
        elem.classList.add(...hideClasses)
        elem.classList.remove(...displayClasses)
    }
}

/**
 * Formats a number with commas as thousand separators.
 *
 * This function takes a number and returns a string representation
 * with commas inserted at every thousandth place for better readability.
 * It also handles decimal numbers by preserving up to two decimal places.
 *
 * @param {number} number - The number to format.
 * @returns {string} The formatted number as a string with commas.
 */
export function formatNumber(number) {
    let stringNumber = String(number);
    
    if (stringNumber.length < 4) {
        return stringNumber;
    }
    
    // Search whether the param is float
    const decimalIndex = stringNumber.indexOf('.');
    let decimal = null;
    
    if (decimalIndex !== -1) {
        // Extract the decimal part
        decimal = Math.round((number - Math.floor(number)) * 100) / 100;
        // Remove the decimal part
        stringNumber = stringNumber.substring(0, decimalIndex);
    }
    
    // Apply comma on string number
    let formatted = '';
    for (let i = stringNumber.length; i > 0; ) {
        for (let j = 0; j < 3; j++) {
            if (i > 0) {
                formatted = stringNumber[--i] + formatted;
            }
        }
        // Check if there is/are more number(s) upfront to apply comma
        // Second condition is to check if there is a negative sign
        if (i > 0 && !isNaN(stringNumber[i - 1])) {
            formatted = ',' + formatted;
        }
    }
    
    if (decimal) {
        // Offset to 1 to remove the leading zero of the decimal part
        formatted += String(decimal).substring(1, 4);
    }
    
    return formatted;
}