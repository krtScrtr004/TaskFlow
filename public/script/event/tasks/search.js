import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

const taskGridContainer = document.querySelector('.task-grid-container')

try {
    const searchBarForm = taskGridContainer?.parentElement.querySelector('form.search-bar')
    // Initialize search functionality for tasks
    search(searchBarForm)
} catch (error) {
    handleException(error, 'Error initializing search functionality:', error)
}
