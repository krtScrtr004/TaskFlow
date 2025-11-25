<?php

namespace App\Validator;

use App\Abstract\Validator;
use App\Container\JobTitleContainer;
use App\Enumeration\WorkerStatus;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Exception\ValidationException;
use COM;
use DateTime;

class UserValidator extends Validator
{
    /**
     * Validates a first name and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Treats a null value or a string that is empty after trimming as invalid.
     * - Ensures the trimmed first name length is between the constants NAME_MIN and NAME_MAX.
     * - Verifies the name contains only valid characters (letters, spaces, apostrophes, hyphens, and periods).
     * - Checks for the presence of three or more consecutive special characters using hasConsecutiveSpecialChars().
     *
     * On failure, the corresponding error messages are added to $this->errors:
     * - "First name must be between {NAME_MIN} and {NAME_MAX} characters long." when the length check fails or name is null/empty.
     * - "First name contains invalid characters." when the name contains disallowed characters.
     * - "First name contains three or more consecutive special characters." when the consecutive-special-character check fails.
     *
     * @param string|null $firstName The first name to validate. May be null.
     *
     * @return void
     */
    public function validateFirstName(?string $firstName): void
    {
        if ($firstName === null || trim($firstName) === '' || strlen($firstName) < NAME_MIN || strlen($firstName) > NAME_MAX) {
            $this->errors[] = 'First name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-.]{" . NAME_MIN . "," . NAME_MAX . "}$/", $firstName)) {
            $this->errors[] = 'First name contains invalid characters.';
        }

        if ($this->hasConsecutiveSpecialChars($firstName)) {
            $this->errors[] = 'First name contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a middle name and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Treats a null value or a string that is empty after trimming as invalid.
     * - Ensures the trimmed middle name length is between the constants NAME_MIN and NAME_MAX.
     * - Verifies the name contains only valid characters (letters, spaces, apostrophes, hyphens, and periods).
     * - Checks for the presence of three or more consecutive special characters using hasConsecutiveSpecialChars().
     *
     * On failure, the corresponding error messages are added to $this->errors:
     * - "Middle name must be between {NAME_MIN} and {NAME_MAX} characters long." when the length check fails or name is null/empty.
     * - "Middle name contains invalid characters." when the name contains disallowed characters.
     * - "Middle name contains three or more consecutive special characters." when the consecutive-special-character check fails.
     *
     * @param string|null $middleName The middle name to validate. May be null.
     *
     * @return void
     */
    public function validateMiddleName(?string $middleName): void
    {
        if ($middleName === null || trim($middleName) === '' || strlen($middleName) < NAME_MIN || strlen($middleName) > NAME_MAX) {
            $this->errors[] = 'Middle name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-.]{" . NAME_MIN . "," . NAME_MAX . "}$/", $middleName)) {
            $this->errors[] = 'Middle name contains invalid characters.';
        }

        if ($this->hasConsecutiveSpecialChars($middleName)) {
            $this->errors[] = 'Middle name contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a last name and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Treats a null value or a string that is empty after trimming as invalid.
     * - Ensures the trimmed last name length is between the constants NAME_MIN and NAME_MAX.
     * - Verifies the name contains only valid characters (letters, spaces, apostrophes, hyphens, and periods).
     * - Checks for the presence of three or more consecutive special characters using hasConsecutiveSpecialChars().
     *
     * On failure, the corresponding error messages are added to $this->errors:
     * - "Last name must be between {NAME_MIN} and {NAME_MAX} characters long." when the length check fails or name is null/empty.
     * - "Last name contains invalid characters." when the name contains disallowed characters.
     * - "Last name contains three or more consecutive special characters." when the consecutive-special-character check fails.
     *
     * @param string|null $lastName The last name to validate. May be null.
     *
     * @return void
     */
    public function validateLastName(?string $lastName): void
    {
        if ($lastName === null || trim($lastName) === '' || strlen($lastName) < NAME_MIN || strlen($lastName) > NAME_MAX) {
            $this->errors[] = 'Last name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-.]{" . NAME_MIN . "," . NAME_MAX . "}$/", $lastName)) {
            $this->errors[] = 'Last name contains invalid characters.';
        }

        if ($this->hasConsecutiveSpecialChars($lastName)) {
            $this->errors[] = 'Last name contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a bio string and records validation errors.
     *
     * This method performs validation only when a non-null bio is provided:
     * - Trims surrounding whitespace and ensures the resulting length is between LONG_TEXT_MIN and LONG_TEXT_MAX.
     * - Verifies the bio does not contain three or more consecutive special characters by delegating to hasConsecutiveSpecialChars().
     *
     * @param string|null $bio The bio to validate; pass null to skip validation.
     *
     * @return void
     *
     * Side effects:
     * - If the length check fails, an error message is appended to $this->errors:
     *     "Bio must be between {LONG_TEXT_MIN} and {LONG_TEXT_MAX} characters long."
     * - If the consecutive-special-character check fails, an error message is appended to $this->errors:
     *     "Bio contains three or more consecutive special characters."
     *
     * Notes:
     * - Length checks use strlen() on the trimmed string.
     * - LONG_TEXT_MIN and LONG_TEXT_MAX are expected to be defined constants.
     * - hasConsecutiveSpecialChars() is a class helper used to detect consecutive special characters.
     */
    public function validateBio(?string $bio): void
    {
        if ($bio !== null && (strlen(trim($bio)) < LONG_TEXT_MIN || strlen(trim($bio)) > LONG_TEXT_MAX)) {
            $this->errors[] = 'Bio must be between ' . LONG_TEXT_MIN . ' and ' . LONG_TEXT_MAX . ' characters long.';
        }

        if ($bio !== null && $this->hasConsecutiveSpecialChars($bio)) {
            $this->errors[] = 'Bio contains three or more consecutive special characters.';
        }
    }

    /**
     * Validates a gender enumeration value and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the value is not null.
     * - Verifies the gender is one of the valid Gender enumeration values (Gender::MALE or Gender::FEMALE).
     *
     * On failure, the error message "Please select a valid gender." is added to $this->errors.
     *
     * @param Gender|null $gender The gender enumeration value to validate. May be null.
     *
     * @return void
     */
    public function validateGender(?Gender $gender): void
    {
        if ($gender === null || !in_array($gender, [Gender::MALE, Gender::FEMALE])) {
            $this->errors[] = 'Please select a valid gender.';
        }
    }

    /**
     * Validates a date of birth and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures a DateTime value is provided; if null, appends 'Date of birth is required.' and returns early.
     * - Verifies the date is in the past (before current date/time).
     * - Uses PHP's checkdate() on the month, day and year extracted from the DateTime to ensure the date is a real calendar date.
     * - Validates the year component using self::isValidYear().
     * - Calculates age and ensures the person is at least 18 years old.
     *
     * Possible error messages appended to $this->errors:
     * - 'Date of birth is required.' when $birthDate is null.
     * - 'Date of birth must be in the past.' when the date is not before current date/time.
     * - 'Date of birth is not a valid date.' when checkdate() fails.
     * - 'Date of birth year is not valid.' when self::isValidYear() returns false.
     * - 'You must be at least 18 years old to register.' when calculated age is less than 18.
     *
     * @param DateTime|null $birthDate DateTime instance representing the date of birth, or null if not provided.
     *
     * @return void
     */
    public function validateBirthDate(?DateTime $birthDate): void
    {
        if ($birthDate === null) {
            $this->errors[] = 'Date of birth is required.';
            return;
        }

        $now = new DateTime();
        if ($birthDate >= $now) {
            $this->errors[] = 'Date of birth must be in the past.';
        }

        if (checkdate((int) $birthDate->format('m'), (int) $birthDate->format('d'), (int) $birthDate->format('Y')) === false) {
            $this->errors[] = 'Date of birth is not a valid date.';
        }

        if (!self::isValidYear((int) $birthDate->format('Y'))) {
            $this->errors[] = 'Date of birth year is not valid.';
        }

        // Calculate age
        $age = $now->diff($birthDate)->y;
        if ($age < 18) {
            $this->errors[] = 'You must be at least 18 years old to register.';
        }
    }

    /**
     * Validates a role enumeration value and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the value is not null.
     * - Verifies the role is one of the valid Role enumeration values (Role::PROJECT_MANAGER or Role::WORKER).
     *
     * On failure, the error message "Please select a valid role." is added to $this->errors.
     *
     * @param Role|null $role The role enumeration value to validate. May be null.
     *
     * @return void
     */
    public function validateRole(?Role $role): void
    {
        if ($role === null || !in_array($role, [Role::PROJECT_MANAGER, Role::WORKER])) {
            $this->errors[] = 'Please select a valid role.';
        }
    }

    /**
     * Validates a collection of job titles and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the JobTitleContainer is not null and contains at least one job title.
     * - For each job title in the container:
     *   - Verifies the trimmed length is between 1 and 100 characters.
     *   - Verifies the title contains only valid characters (letters, numbers, spaces, apostrophes, hyphens, forward/back slashes).
     *
     * Possible error messages appended to $this->errors:
     * - 'Job titles must be provided.' when the container is null or empty.
     * - 'Each job title must be between 1 and 100 characters long.' when any title's length is invalid (breaks after first occurrence).
     * - 'Job title "{title}" contains invalid characters.' when any title contains disallowed characters (breaks after first occurrence).
     *
     * @param JobTitleContainer|null $jobTitles The collection of job titles to validate. May be null.
     *
     * @return void
     */
    public function validateJobTitles(?JobTitleContainer $jobTitles): void
    {
        if ($jobTitles === null || $jobTitles->count() < 1) {
            $this->errors[] = 'Job titles must be provided.';
            return;
        }

        foreach ($jobTitles as $jobTitle) {
            if (strlen(trim($jobTitle)) < 1 || strlen(trim($jobTitle)) > 100) {
                $this->errors[] = 'Each job title must be between 1 and 100 characters long.';
                break;
            }

            if (preg_match("/[^a-zA-Z0-9\s'\-\\\/]/", $jobTitle)) {
                $this->errors[] = 'Job title "' . $jobTitle . '" contains invalid characters.';
                break;
            }
        }
    }

    /**
     * Validates a contact number and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Treats a null value or a string that is empty after trimming as invalid.
     * - Ensures the contact number length is between the constants CONTACT_NUMBER_MIN and CONTACT_NUMBER_MAX.
     * - Verifies the contact number contains only valid characters (digits, spaces, hyphens, parentheses, and optional leading plus sign).
     *
     * Possible error messages appended to $this->errors:
     * - 'Contact number must be between {CONTACT_NUMBER_MIN} and {CONTACT_NUMBER_MAX} characters long.' when the length check fails or value is null/empty.
     * - 'Contact number contains invalid characters.' when the number contains disallowed characters.
     *
     * @param string|null $contactNumber The contact number to validate. May be null.
     *
     * @return void
     */
    public function validateContactNumber(?string $contactNumber): void
    {
        if ($contactNumber === null || trim($contactNumber) === '' || strlen($contactNumber) < CONTACT_NUMBER_MIN || strlen($contactNumber) > CONTACT_NUMBER_MAX) {
            $this->errors[] = 'Contact number must be between ' . CONTACT_NUMBER_MIN . ' and ' . CONTACT_NUMBER_MAX . ' characters long.';
        }

        if (!preg_match('/^\+?[\d\s\-\(\)]{' . CONTACT_NUMBER_MIN . ',' . CONTACT_NUMBER_MAX . '}$/', $contactNumber)) {
            $this->errors[] = 'Contact number contains invalid characters.';
        }
    }

    /**
     * Validates a worker status enumeration value and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the value is not null.
     * - Verifies the status is one of the valid WorkerStatus enumeration values (ASSIGNED, UNASSIGNED, or TERMINATED).
     *
     * On failure, the error message "Please select a valid worker status." is added to $this->errors.
     *
     * @param WorkerStatus|null $status The worker status enumeration value to validate. May be null.
     *
     * @return void
     */
    public function validateStatus(?WorkerStatus $status): void
    {
        if ($status === null || !in_array($status, [WorkerStatus::ASSIGNED, WorkerStatus::UNASSIGNED, WorkerStatus::TERMINATED])) {
            $this->errors[] = 'Please select a valid worker status.';
        }
    }

    /**
     * Validates an email address and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the email is not null.
     * - Verifies the trimmed email length is between the constants URI_MIN and URI_MAX.
     * - Uses PHP's filter_var() with FILTER_VALIDATE_EMAIL to ensure the email format is valid.
     *
     * Possible error messages appended to $this->errors:
     * - 'Email must be between {URI_MIN} and {URI_MAX} characters long.' when the length check fails or value is null.
     * - 'Invalid email address.' when the email format validation fails.
     *
     * @param string|null $email The email address to validate. May be null.
     *
     * @return void
     */
    public function validateEmail(?string $email): void
    {
        if ($email === null || strlen(trim($email)) < URI_MIN || strlen(trim($email)) > URI_MAX) {
            $this->errors[] = 'Email must be between ' . URI_MIN . ' and ' . URI_MAX . ' characters long.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Invalid email address.';
        }
    }

    /**
     * Validates a password and appends any validation error messages to $this->errors.
     *
     * This method performs the following checks:
     * - Ensures the password is not null and its length is between PASSWORD_MIN and PASSWORD_MAX.
     * - Verifies the password contains at least one lowercase letter.
     * - Verifies the password contains at least one uppercase letter.
     * - Ensures the password contains only allowed characters (alphanumeric and _!@'.- special characters).
     *
     * Possible error messages appended to $this->errors:
     * - 'Password must be between {PASSWORD_MIN} and {PASSWORD_MAX} characters long.' when the length check fails or value is null.
     * - 'Password must contain at least one lowercase letter.' when no lowercase letters are found.
     * - 'Password must contain at least one uppercase letter.' when no uppercase letters are found.
     * - 'Password contains invalid special characters. Only _!@\'.- are allowed.' when disallowed characters are detected.
     *
     * @param string|null $password The password to validate. May be null.
     *
     * @return void
     */
    public function validatePassword(?string $password): void
    {
        if ($password === null || strlen($password) < PASSWORD_MIN || strlen($password) > PASSWORD_MAX) {
            $this->errors[] = 'Password must be between ' . PASSWORD_MIN . ' and ' . PASSWORD_MAX . ' characters long.';
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $this->errors[] = 'Password must contain at least one lowercase letter.';
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors[] = 'Password must contain at least one uppercase letter.';
        }

        // Check for special characters (should NOT contain special characters except _!@'.- which are allowed)
        if (preg_match('/[^a-zA-Z0-9_!@\'\.\-]/', $password)) {
            $this->errors[] = 'Password contains invalid special characters. Only _!@\'.- are allowed.';
        }
    }

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validates multiple user data fields and appends any validation error messages to $this->errors.
     *
     * This method accepts an associative array of user data and conditionally validates each field if it is not null:
     * - firstName: Validates using validateFirstName().
     * - middleName: Validates using validateMiddleName().
     * - lastName: Validates using validateLastName().
     * - gender: Validates using validateGender().
     * - birthDate: Validates using validateBirthDate().
     * - role: Validates using validateRole() (checked twice in original implementation).
     * - jobTitles: Validates using validateJobTitles().
     * - password: Validates using validatePassword().
     * - contactNumber: Validates using validateContactNumber().
     * - email: Validates using validateEmail().
     * - bio: Validates using validateBio().
     * - profileLink: Validates using UrlValidator's validateUrl().
     * - createdAt: Ensures the DateTime value is not in the future.
     *
     * Each field is trimmed before validation where applicable. All validation errors from individual
     * validators are collected in $this->errors.
     *
     * @param array $data Associative array containing user data fields. Expected keys include:
     *                    firstName, middleName, lastName, gender, birthDate, role, jobTitles,
     *                    password, contactNumber, email, bio, profileLink, createdAt.
     *
     * @return void
     */
    public function validateMultiple(array $data): void
    {
        $urlValidator = new UrlValidator();

        if ($data['firstName'] !== null) {
            $this->validateFirstName(trim($data['firstName']) ?? null);
        }

        if ($data['middleName'] !== null) {
            $this->validateMiddleName(trim($data['middleName']) ?? null);
        }

        if ($data['lastName'] !== null) {
            $this->validateLastName(trim($data['lastName']) ?? null);
        }

        if ($data['gender'] !== null) {
            $this->validateGender($data['gender'] ?? null);
        }

        if ($data['birthDate'] !== null) {
            $this->validateBirthDate($data['birthDate'] ?? null);
        }

        if ($data['role'] !== null) {
            $this->validateRole($data['role'] ?? null);
        }

        if ($data['jobTitles'] !== null) {
            $this->validateJobTitles($data['jobTitles'] ?? null);
        }

        if ($data['password'] !== null) {
            $this->validatePassword(trim($data['password']) ?? null);
        }

        if ($data['contactNumber'] !== null) {
            $this->validateContactNumber(trim($data['contactNumber']) ?? null);
        }

        if ($data['email'] !== null) {
            $this->validateEmail(trim($data['email']) ?? null);
        }

        if ($data['bio'] !== null) {
            $this->validateBio(trim($data['bio']));
        }

        if ($data['profileLink'] !== null) {
            $urlValidator->validateUrl(trim($data['profileLink']) ?? null);
        }

        if ($data['createdAt'] > new DateTime()) {
            $this->addError("createdAt", "Created At date cannot be in the future.");
        }

        if ($data['role'] !== null) {
            $this->validateRole($data['role'] ?? null);
        }
    }
}
