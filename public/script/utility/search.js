import { debounce } from './debounce.js'

export function search(searchBarForm) {
    if (!searchBarForm) {
        throw new Error('Search Bar form not found.')
    }

    const searchButton = searchBarForm?.querySelector('button.search-button')

    if (!searchButton) {
        throw new Error('Search Task button not found.')
    } else {
        searchButton.addEventListener('click', e => debounce(submit(e, searchBarForm), 300))
    }

    searchBarForm.addEventListener('submit', e => debounce(submit(e, searchBarForm), 300))
}

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