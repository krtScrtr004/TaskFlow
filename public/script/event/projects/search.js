import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'
import { handleException } from '../../utility/handle-exception.js'

const projects = document.querySelector('.projects')
try {
    const searchBarForm = projects?.querySelector('form.search-bar')
    search(searchBarForm)
} catch (error) {
    handleException (error, 'Error initializing search functionality:', error)
}
