import { search } from '../../utility/search.js'
import { Dialog } from '../../render/dialog.js'

const userGridContainer = document.querySelector('.user-grid-container')

try {
    const searchBarForm = userGridContainer?.parentElement.querySelector('form.search-bar')
    search(searchBarForm)
} catch (error) {
    console.error('Error initializing search functionality:', error)
    Dialog.somethingWentWrong()
}
