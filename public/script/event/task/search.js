import { debounce } from '../../utility/debounce.js'

const taskGridContainer = document.querySelector('.task-grid-container')
const searchBarForm = taskGridContainer?.parentElement.querySelector('form.search-bar')
const searchTaskButton = searchBarForm?.querySelector('#search_task_button')
const searchFilter = searchBarForm?.querySelector('#search_task_filter')

if (!searchFilter)
    console.warn('Search filter input not found.')

if (searchBarForm) {
    searchBarForm.addEventListener('submit', e => debounce(submit(e), 300))
} else {
    console.error('Search Bar form not found.')
}

if (searchTaskButton) {
    searchTaskButton.addEventListener('click', e => debounce(submit(e), 300))
} else {
    console.error('Search Task button not found.')
}

function submit(e) {
    e.preventDefault()

    const key = searchBarForm.querySelector('input#search_task_input').value.trim() ?? ''

    const params = new URLSearchParams()
    params.append('key', key)
    if (searchFilter)
        params.append('filter', searchFilter.value.trim())
    params.append('offset', 0)

    window.location.href = `${window.location.pathname}?${params.toString()}`
}