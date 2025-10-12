import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'

const projects = document.querySelector('.projects')
try {
    const searchBarForm = projects?.querySelector('form.search-bar')
    search(searchBarForm)
} catch (error) {
    console.error('Error initializing search functionality:', error)
    Dialog.somethingWentWrong()
}
