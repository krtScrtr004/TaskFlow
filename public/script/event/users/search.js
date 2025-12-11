import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

const userGridContainer = document.querySelector('.user-grid-container')

try {
    const searchBarForm = userGridContainer?.parentElement.querySelector('form.search-bar')
    // Initialize search functionality for users
    search(searchBarForm)
} catch (error) {
    handleException(error, 'Error initializing search functionality:', error)
}
