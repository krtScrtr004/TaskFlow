import { search } from '../../Utility/Search.js'
import { handleException } from '../../Utility/HandleException.js'

const userGridContainer = document.querySelector('.user-grid-container')
try {
    const searchBarForm = userGridContainer?.parentElement.querySelector('form.search-bar')
    // Initialize search functionality for users
    search(searchBarForm)
} catch (error) {
    handleException(error)
}
