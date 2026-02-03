import { search } from '../../Utility/Search.js'
import { handleException } from '../../Utility/HandleException.js'

const projects = document.querySelector('.projects')
try {
    const searchBarForm = projects?.querySelector('form.search-bar')
    const targetSection = projects?.querySelector('#project_grid')
    
    // Initialize search functionality for projects
    search(searchBarForm, targetSection)
} catch (error) {
    handleException(error)
}

