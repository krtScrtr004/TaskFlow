import { search } from '../../utility/search.js'
import { handleException } from '../../utility/handle-exception.js'

const projects = document.querySelector('.projects')
try {
    const searchBarForm = projects?.querySelector('form.search-bar')
    const targetSection = projects?.querySelector('#project_grid')
    
    // Initialize search functionality for projects
    search(searchBarForm, targetSection)
} catch (error) {
    handleException(error, 'Error initializing search functionality:', error)
}

