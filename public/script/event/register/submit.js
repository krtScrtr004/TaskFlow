import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { Notification } from '../../render/notification.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'

let isLoading = false

const registerForm = document.querySelector('#register_form')
const registerButton = registerForm?.querySelector('#register_button')
if (registerButton) {
    registerButton.addEventListener('click', e => debounceAsync(submit(e), 300))
} else {
    console.error('Register button not found.')
    Dialog.somethingWentWrong()
}

if (registerForm) {
    registerForm.addEventListener('submit', e => debounceAsync(submit(e), 300))
} else {
    console.error('Register form not found.')
    Dialog.somethingWentWrong()
}

async function submit(e) {
    e.preventDefault()

    const firstNameInput = registerForm.querySelector('#register_first_name')
    const middleNameInput = registerForm.querySelector('#register_middle_name')
    const lastNameInput = registerForm.querySelector('#register_last_name')
    const genderInput = registerForm.querySelector('input[name="gender"]:checked')
    const dayOfBirthInput = registerForm.querySelector('#day_of_birth')
    const monthOfBirthInput = registerForm.querySelector('#month_of_birth')
    const yearOfBirthInput = registerForm.querySelector('#year_of_birth')
    const jobTitlesInput = registerForm.querySelector('#register_job_titles')
    const contactInput = registerForm.querySelector('#register_contact')
    const emailInput = registerForm.querySelector('#register_email')
    const passwordInput = registerForm.querySelector('#register_password')
    const roleInput = registerForm.querySelector('input[name="role"]:checked')
    if (!firstNameInput || !middleNameInput || !lastNameInput || !genderInput ||
        !dayOfBirthInput || !monthOfBirthInput || !yearOfBirthInput ||
        !jobTitlesInput || !emailInput || !passwordInput || !roleInput) {
        throw new Error('One or more form inputs not found.')
    }

    const inputs = {
        firstName: firstNameInput.value.trim(),
        middleName: middleNameInput.value.trim(),
        lastName: lastNameInput.value.trim(),
        gender: genderInput.value.trim(),
        dateOfBirth: new Date(`${yearOfBirthInput.value.trim()}-${monthOfBirthInput.value.trim()}-${dayOfBirthInput.value.trim()}`),
        jobTitles: jobTitlesInput.value.trim(),
        contact: contactInput.value.trim(),
        email: emailInput.value.trim(),
        password: passwordInput.value.trim(),
        role: roleInput.value.trim()
    }

    if (!validateInputs(inputs, userValidationRules())) return

    Loader.patch(registerButton.querySelector('.text-w-icon'))
    try {
        const response = await sendToBackend(...Object.values(inputs))
        if (!response)
            throw new Error('No response from server.')

        window.location.href = `/TaskFlow/project`
    } catch (error) {
        console.error('Error during register:', error)
        if (error?.errors) {
            errorListDialog(error?.message, error.errors)
        } else {
            Dialog.somethingWentWrong()
        }
    } finally {
        Loader.delete()
    }
}

async function sendToBackend(
    firstName,
    middleName,
    lastName,
    gender,
    dateOfBirth,
    jobTitles,
    contact,
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

        if (!firstName || firstName.trim() === '')
            throw new Error('First name is required.')

        if (!middleName || middleName.trim() === '')
            throw new Error('Middle name is required.')

        if (!lastName || lastName.trim() === '')
            throw new Error('Last name is required.')

        if (!gender || gender.trim() === '')
            throw new Error('Gender is required.')

        if (!dateOfBirth || isNaN(new Date(dateOfBirth).getTime()))
            throw new Error('Valid date of birth is required.')

        if (!jobTitles || jobTitles.trim() === '')
            throw new Error('Job titles is required.')

        if (!contact || contact.trim() === '')
            throw new Error('Contact is required.')

        if (!email || email.trim() === '')
            throw new Error('Email is required.')

        if (!password || password.trim() === '')
            throw new Error('Password is required.')

        if (!role || role.trim() === '')
            throw new Error('Role is required.')

        await Http.POST('auth/register', {
            firstName: firstName.trim(),
            middleName: middleName.trim(),
            lastName: lastName.trim(),
            gender: gender.trim(),
            dateOfBirth: new Date(dateOfBirth).toISOString(),
            jobTitles: jobTitles.trim(),
            contact: contact.trim(),
            email: email.trim(),
            password: password.trim(),
            role: role.trim()
        })

        const delay = 1500
        Notification.success('Registration successful!', delay)
        setTimeout(() => window.location.href = '/TaskFlow/project', delay)
    } catch (error) {
        throw error
    } finally {
        isLoading = false
    }
}