import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { Notification } from '../../render/notification.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'
import { normalizeDateFormat } from '../../utility/utility.js'

let isLoading = false

const registerForm = document.querySelector('#register_form')
if (!registerForm) {
    console.error('Register form not found.')
    Dialog.somethingWentWrong()
}

registerForm?.addEventListener('submit', e => debounceAsync(submit(e), 300))

const registerButton = registerForm?.querySelector('#register_button')
if (!registerButton) {
    console.error('Register button not found.')
    Dialog.somethingWentWrong()
}

registerButton?.addEventListener('click', e => debounceAsync(submit(e), 300))

/**
 * Handles the registration form submission event.
 *
 * This function collects user input from the registration form, validates the inputs,
 * displays a loading indicator, sends the data to the backend, and handles the response.
 * On successful registration, it shows a notification and redirects the user to the home page.
 * On error, it displays an error notification.
 *
 * @async
 * @param {Event} e The form submission event.
 * 
 * @throws {Error} If one or more required form inputs are not found in the DOM.
 *
 * @returns {Promise<void>} Resolves when the registration process is complete.
 */
async function submit(e) {
    e.preventDefault()

    // Retrieve input fields from the form
    const firstNameInput = registerForm.querySelector('#register_first_name')
    const middleNameInput = registerForm.querySelector('#register_middle_name')
    const lastNameInput = registerForm.querySelector('#register_last_name')
    const genderInput = registerForm.querySelector('input[name="gender"]:checked')
    const birthDateInput = registerForm.querySelector('#register_birth_date')
    const jobTitlesInput = registerForm.querySelector('#register_job_titles')
    const contactInput = registerForm.querySelector('#register_contact')
    const emailInput = registerForm.querySelector('#register_email')
    const passwordInput = registerForm.querySelector('#register_password')
    const roleInput = registerForm.querySelector('input[name="role"]:checked')
    if (!firstNameInput || !middleNameInput || !lastNameInput || !genderInput ||
        !birthDateInput || !jobTitlesInput || !emailInput || !passwordInput || !roleInput) {
        throw new Error('One or more form inputs not found.')
    }

    // Handle job titles trailing comma
    let jobTitlesValue = jobTitlesInput.value.trim()
    for (const char of jobTitlesInput.value) {
        if (jobTitlesValue.slice(-1) === ',' || jobTitlesValue.slice(-1) === ' ') {
            jobTitlesValue = jobTitlesValue.slice(0, -1)
        } else {
            break
        }
    }

    const inputs = {
        firstName: firstNameInput.value.trim(),
        middleName: middleNameInput.value.trim(),
        lastName: lastNameInput.value.trim(),
        gender: genderInput.value.trim(),
        birthDate: normalizeDateFormat(birthDateInput.value),
        jobTitles: jobTitlesValue,
        contactNumber: contactInput.value.trim(),
        email: emailInput.value.trim(),
        password: passwordInput.value.trim(),
        role: roleInput.value.trim()
    }

    // Validate inputs
    if (!validateInputs(inputs, userValidationRules())) {
        return
    }

    Loader.patch(registerButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(...Object.values(inputs))

        Dialog.operationSuccess('Registration Successful', 'A confirmation email has been sent to your email address. Please verify your email before logging in.')
        setTimeout(() => window.location.href = '/TaskFlow/login', 2000)
    } catch (error) {
        handleException(error, `Error during register: ${error}`)
    } finally {
        Loader.delete()
    }
}

/**
 * Sends user registration data to the backend for account creation.
 *
 * This function performs client-side validation on the provided user details before sending them
 * to the backend API endpoint for registration. It prevents duplicate submissions by checking
 * the loading state, and throws errors for missing or invalid required fields.
 *
 * @param {string} firstName User's first name (required)
 * @param {string} middleName User's middle name (optional)
 * @param {string} lastName User's last name (required)
 * @param {string} gender User's gender (required)
 * @param {string} birthDate User's birth date in ISO format or a valid date string (required)
 * @param {string} jobTitles User's job titles (optional)
 * @param {string} contactNumber User's contact number (required)
 * @param {string} email User's email address (required)
 * @param {string} password User's password (required)
 * @param {string} role User's role (required)
 *
 * @throws {Error} If any required field is missing or invalid, or if a request is already in progress.
 * @returns {Promise<void>} Resolves when the registration request completes successfully.
 */
async function sendToBackend(
    firstName,
    middleName,
    lastName,
    gender,
    birthDate,
    jobTitles,
    contactNumber,
    email,
    password,
    role
) {
    try {
        if (isLoading) {
            console.warn('Request already in progress. Please wait.')
            return
        }
        isLoading = true

        if (!firstName || firstName.trim() === '') {
            throw new Error('First name is required.')
        }

        if (!lastName || lastName.trim() === '') {
            throw new Error('Last name is required.')
        }

        if (!gender || gender.trim() === '') {
            throw new Error('Gender is required.')
        }

        if (!birthDate || isNaN(new Date(birthDate).getTime())) {
            throw new Error('Valid date of birth is required.')
        }

        if (!contactNumber || contactNumber.trim() === '') {
            throw new Error('Contact number is required.')
        }

        if (!email || email.trim() === '') {
            throw new Error('Email is required.')
        }

        if (!password || password.trim() === '') {
            throw new Error('Password is required.')
        }

        if (!role || role.trim() === '') {
            throw new Error('Role is required.')
        }

        await Http.POST('auth/register', {
            firstName: firstName.trim(),
            middleName: middleName?.trim(),
            lastName: lastName.trim(),
            gender: gender.trim(),
            birthDate: birthDate.trim(),
            jobTitles: jobTitles?.trim(),
            contactNumber: contactNumber.trim(),
            email: email.trim(),
            password: password.trim(),
            role: role.trim()
        })
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}