import { Http } from '../../utility/http.js'
import { Dialog } from '../../render/dialog.js'
import { errorListDialog } from '../../render/error-list-dialog.js'
import { Loader } from '../../render/loader.js'
import { Notification } from '../../render/notification.js'
import { validateInputs, userValidationRules } from '../../utility/validator.js'
import { debounceAsync } from '../../utility/debounce.js'
import { handleException } from '../../utility/handle-exception.js'

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
        birthDate: new Date(
            `${yearOfBirthInput.value.trim()}-${monthOfBirthInput.value.trim().padStart(2, '0')}-${dayOfBirthInput.value.trim().padStart(2, '0')}`
        ),
        jobTitles: jobTitlesInput.value.trim(),
        contactNumber: contactInput.value.trim(),
        email: emailInput.value.trim(),
        password: passwordInput.value.trim(),
        role: roleInput.value.trim()
    }

    if (!validateInputs(inputs, userValidationRules())) return

    Loader.patch(registerButton.querySelector('.text-w-icon'))
    try {
        await sendToBackend(...Object.values(inputs))

        const delay = 1500
        Notification.success('Registration successful!', delay)
        setTimeout(() => window.location.href = '/TaskFlow/home', delay)
    } catch (error) {
        handleException(error, `Error during register: ${error}`)
    } finally {
        Loader.delete()
    }
}

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
            birthDate: new Date(birthDate).toISOString(),
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