import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const loginForm = document.querySelector('#login_form')
const loginButton = loginForm?.querySelector('#login_button')

if (loginButton) {
    loginButton.addEventListener('click', (e) => debounceAsync(submit(e), 300))
} else {
    console.error('Login button not found.')
    Dialog.somethingWentWrong()
}

if (loginForm) {
    loginForm.addEventListener('submit', (e) => debounceAsync(submit(e), 300))
} else {
    console.error('Login form not found.')
    Dialog.somethingWentWrong()
}

async function submit(e) {
    e.preventDefault()

    const emailInput = loginForm.querySelector('#login_email')
    if (!emailInput) { // s
        console.error('Email input not found.')
        Dialog.somethingWentWrong()
    }

    const passwordInput = loginForm.querySelector('#login_password')
    if (!passwordInput) {
        console.error('Password input not found.')
        Dialog.somethingWentWrong()
    }

    const email = emailInput.value.trim()
    const password = passwordInput.value.trim()

    if (!validateInputs({
        email: email,
        password: password
    }, userValidationRules())) return

    Loader.patch(loginButton.querySelector('.text-w-icon'))
    try {
        const response = await sendToBackend(email, password)
        if (!response) {
            throw new Error('No response from server.')
        }

        const projectId = response.projectId
        const redirect = (projectId && projectId.trim() !== '') ? `/${projectId}` : ``
        window.location.href = `/TaskFlow/home${redirect}`
    } catch (error) {
        handleException(error, 'Error during login:', error)
    } finally {
        Loader.delete()
    }
}

async function sendToBackend(email, password) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!email || email.trim() === '')
            throw new Error('Email is required.')

        if (!password || password.trim() === '')
            throw new Error('Password is required.')

        const response = await Http.POST('auth/login', { email, password })
        if (!response)
            throw new Error('No response from server.')

        return response.data
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}