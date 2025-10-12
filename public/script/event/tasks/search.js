import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'

const taskGridContainer = document.querySelector('.task-grid-container')

try {
    const searchBarForm = taskGridContainer?.parentElement.querySelector('form.search-bar')
    search(searchBarForm)
} catch (error) {
    console.error('Error initializing search functionality:', error)
    Dialog.somethingWentWrong()
}
