const passwordElement = document.querySelector('.validate-password')
if (passwordElement) {
    const [green, red] = ['rgb(0, 208, 38)', 'rgb(220, 53, 69)']
    const patterns = [/[a-z]/, /[A-Z]/, /[^a-zA-Z0-9_!@'.-]/]

    passwordElement.addEventListener('input', () => {
        const password = passwordElement.value

        const rules = document.querySelectorAll('.password-list-validator > li')

        const count = password.length
        const [lowerCase, upperCase, characters] = [patterns[0].test(password), patterns[1].test(password), patterns[2].test(password)]

        // Update rule colors based on validation
        rules?.forEach(rule => {
            if (rule.id === 'lower_case') {
                rule.style.color = (lowerCase) ? green : red
            } else if (rule.id === 'upper_case') {
                rule.style.color = (upperCase) ? green : red
            } else if (rule.id === 'count') {
                rule.style.color = (count >= 8 && count <= 255) ? green : red
            } else if (rule.id === 'characters') {
                rule.style.color = (characters) ? red : green
            }
        })
    })
} else {
    console.warn('Password element not found.')
}