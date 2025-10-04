import { debounce } from '../utility/debounce.js'

function evaluateDate(dateString) {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
        return false // Not a date
    }

    // Extract day, month, year from input
    const parts = dateString.split(' ')
    if (parts.length !== 3) {
        return false
    }

    const [dayStr, monthStr, yearStr] = parts
    const day = parseInt(dayStr, 10)
    const year = parseInt(yearStr, 10)

    const months = {
        January: 0, February: 1, March: 2, April: 3,
        May: 4, June: 5, July: 6, August: 7,
        September: 8, October: 9, November: 10, December: 11
    }

    if (!(monthStr in months)) {
        return false
    }

    // Reconstruct valid date
    const reconstructed = new Date(year, months[monthStr], day)

    // Compare back: does the reconstructed date match what user typed?
    return (
        reconstructed.getDate() === day &&
        reconstructed.getMonth() === months[monthStr] &&
        reconstructed.getFullYear() === year
    )
}

const birthDate = document.querySelector('.birth-date')
if (birthDate) {
    const invalidDateResultBox = birthDate.querySelector('.invalid-date-result-box > p')

    const daySelect = birthDate.querySelector('#day_of_birth')
    const monthSelect = birthDate.querySelector('#month_of_birth')
    const yearSelect = birthDate.querySelector('#year_of_birth')

    const parts = [daySelect, monthSelect, yearSelect]
    parts.forEach(elem => {
        elem.addEventListener('change', debounce(() => {
            const result = evaluateDate(`${daySelect.value} ${monthSelect.value} ${yearSelect.value}`)
            invalidDateResultBox.textContent = (!result) ? 'Invalid date! Please enter a valid date.' : ''
        }, 300))
    })
}
