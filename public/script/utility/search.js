import { debounce } from './debounce.js'

/**
 * Attaches search functionality to a search bar form element.
 *
 * This function sets up event listeners on the provided search bar form and its search button.
 * When the search button is clicked or the form is submitted, it debounces the submit action
 * to prevent rapid repeated submissions. Throws errors if required elements are not found.
 *
 * @param {HTMLFormElement} searchBarForm The form element containing the search bar and search button.
 * @throws {Error} If the search bar form is not found.
 * @throws {Error} If the search button within the form is not found.
 */
export function search(searchBarForm) {
    if (!searchBarForm) {
        throw new Error('Search Bar form not found.')
    }

    const searchButton = searchBarForm?.querySelector('button.search-button')

    if (!searchButton) {
        throw new Error('Search Task button not found.')
    }
    searchButton.addEventListener('click', e => debounce(submit(e, searchBarForm), 300))
    searchBarForm.addEventListener('submit', e => debounce(submit(e, searchBarForm), 300))
}

/**
 * Handles the submission of a search form, constructs query parameters, and redirects to the updated URL.
 *
 * This function prevents the default form submission, extracts the search key and filter from the form,
 * builds a URLSearchParams object, and navigates to the current path with the new query string.
 *
 * @param {Event} e The form submission event.
 * @param {HTMLFormElement} searchBarForm The search bar form element containing the input and optional filter.
 *      - input.search-input: HTMLInputElement The input field for the search key.
 *      - select.search-filter: HTMLSelectElement (optional) The select field for filtering search results.
 *
 * @returns {void}
 */
function submit(e, searchBarForm) {
    e.preventDefault()

    const key = searchBarForm.querySelector('input.search-input').value.trim() ?? ''

    const params = new URLSearchParams()
    params.append('key', key)

    const searchFilter = searchBarForm?.querySelector('select.search-filter')
    if (searchFilter) {
        params.append('filter', searchFilter.value.trim())
    }

    params.append('offset', 0)

    window.location.href = `${window.location.pathname}?${params.toString()}`
}