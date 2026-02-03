import { search } from '../../Utility/Search.js'
import { handleException } from '../../Utility/HandleException.js'

try {
    const taskGridContainer = document.querySelector('.task-grid-container')
    const searchBarForm = taskGridContainer?.parentElement.querySelector('form.search-bar')
    // Initialize search functionality for tasks
    search(searchBarForm)
} catch (error) {
    handleException(error, 'Error initializing search functionality:', error)
}
