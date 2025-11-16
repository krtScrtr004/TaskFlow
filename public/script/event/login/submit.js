import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

let isLoading = false

const loginForm = document.querySelector('#login_form')
if (!loginForm) {
    console.error('Login form not found.')
    Dialog.somethingWentWrong()
}

const loginButton = loginForm?.querySelector('#login_button')
if (!loginButton) {
    console.error('Login button not found.')
    Dialog.somethingWentWrong()
}

loginForm?.addEventListener('submit', (e) => debounceAsync(submit(e), 300))
loginButton?.addEventListener('click', (e) => debounceAsync(submit(e), 300))

/**
 * Handles the login form submission event.
 *
 * This function performs the following steps:
 * - Prevents the default form submission behavior.
 * - Retrieves and validates the email and password input fields from the login form.
 * - Trims and validates the input values using custom validation rules.
 * - Shows a loading indicator on the login button.
 * - Sends the email and password to the backend for authentication.
 * - Redirects the user to the home page upon successful login.
 * - Handles and logs any errors that occur during the login process.
 * - Removes the loading indicator after the process completes.
 *
 * @async
 * @param {Event} e The form submission event object.
 * @returns {Promise<void>} Resolves when the login process is complete.
 */
async function submit(e) {
    e.preventDefault()
    
    try {
        Loader.patch(loginButton.querySelector('.text-w-icon'))


        const emailInput = loginForm.querySelector('#login_email')
        if (!emailInput) { 
            throw new Error('Email input not found.')
        }

        const passwordInput = loginForm.querySelector('#login_password')
        if (!passwordInput) {
            throw new Error('Password input not found.')
        }

        const email = emailInput.value.trim()
        const password = passwordInput.value.trim()

        // Validate inputs
        if (!validateInputs({
            email: email,
            password: password
        }, userValidationRules())) return

        await sendToBackend(email, password)

        // Redirect to home page after successful login
        window.location.href = `/TaskFlow/home` 
    } catch (error) {
        handleException(error, 'Error during login:', error)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends login credentials to the backend for authentication.
 *
 * This function performs the following steps:
 * - Prevents multiple simultaneous requests using the isLoading flag.
 * - Validates that both email and password are provided and non-empty.
 * - Sends a POST request to the 'auth/login' endpoint with the provided credentials.
 * - Handles missing server responses and propagates errors.
 * - Ensures the isLoading flag is reset after the request completes.
 *
 * @param {string} email The user's email address. Must be a non-empty string.
 * @param {string} password The user's password. Must be a non-empty string.
 * @throws {Error} If email or password is missing or empty, or if the server response is invalid.
 * @returns {Promise<void>} Resolves when the request completes, or rejects with an error.
 */
async function sendToBackend(email, password) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!email || email.trim() === '') {
            throw new Error('Email is required.')
        }

        if (!password || password.trim() === '') {
            throw new Error('Password is required.')
        }

        const response = await Http.POST('auth/login', { email, password })
        if (!response) {
            throw new Error('No response from server.')
        }
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}