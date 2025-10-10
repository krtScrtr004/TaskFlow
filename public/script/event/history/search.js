import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'

const history = document.querySelector('.history')
try {
    const searchBarForm = history?.querySelector('form.search-bar')
    search(searchBarForm)
} catch (error) {
    console.error('Error initializing search functionality:', error)
    Dialog.somethingWentWrong()
}
