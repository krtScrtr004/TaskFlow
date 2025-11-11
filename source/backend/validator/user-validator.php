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
     * Validate first name
     */
    public function validateFirstName(?string $firstName): void
    {
        if ($firstName === null || trim($firstName) === '' || strlen($firstName) < NAME_MIN || strlen($firstName) > NAME_MAX) {
            $this->errors[] = 'First name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{" . NAME_MIN . "," . NAME_MAX . "}$/", $firstName)) {
            $this->errors[] = 'First name contains invalid characters.';
        }
    }

    /**
     * Validate middle name
     */
    public function validateMiddleName(?string $middleName): void
    {
        if ($middleName === null || trim($middleName) === '' || strlen($middleName) < NAME_MIN || strlen($middleName) > NAME_MAX) {
            $this->errors[] = 'Middle name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{" . NAME_MIN . "," . NAME_MAX . "}$/", $middleName)) {
            $this->errors[] = 'Middle name contains invalid characters.';
        }
    }

    /**
     * Validate last name
     */
    public function validateLastName(?string $lastName): void
    {
        if ($lastName === null || trim($lastName) === '' || strlen($lastName) < NAME_MIN || strlen($lastName) > NAME_MAX) {
            $this->errors[] = 'Last name must be between ' . NAME_MIN . ' and ' . NAME_MAX . ' characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{" . NAME_MIN . "," . NAME_MAX . "}$/", $lastName)) {
            $this->errors[] = 'Last name contains invalid characters.';
        }
    }

    /**
     * Validate bio
     */
    public function validateBio(?string $bio): void
    {
        if ($bio !== null && (strlen(trim($bio)) < LONG_TEXT_MIN || strlen(trim($bio)) > LONG_TEXT_MAX)) {
            $this->errors[] = 'Bio must be between ' . LONG_TEXT_MIN . ' and ' . LONG_TEXT_MAX . ' characters long.';
        }
    }

    /**
     * Validate gender
     */
    public function validateGender(?Gender $gender): void
    {
        if ($gender === null || !in_array($gender, [Gender::MALE, Gender::FEMALE])) {
            $this->errors[] = 'Please select a valid gender.';
        }
    }

    /**
     * Validate date of birth
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
     * Validate role
     */
    public function validateRole(?Role $role): void
    {
        if ($role === null || !in_array($role, [Role::PROJECT_MANAGER, Role::WORKER])) {
            $this->errors[] = 'Please select a valid role.';
        }
    }

    /**
     * Validate job titles
     */
    public function validateJobTitles(?JobTitleContainer $jobTitles): void
    {
        if ($jobTitles === null || $jobTitles->count() < 1) {
            $this->errors[] = 'Job titles must be provided.';
            return;
        }

        foreach ($jobTitles as $jobTitle) {
            if (strlen(trim($jobTitle)) < 1 || strlen(trim($jobTitle)) > 20) {
                $this->errors[] = 'Each job title must be between 1 and 20 characters long.';
                break;
            }

            if (preg_match("/[^a-zA-Z0-9\s'\-]/", $jobTitle)) {
                $this->errors[] = 'Job title "' . $jobTitle . '" contains invalid characters.';
                break;
            }
        }
    }

    /**
     * Validate contact number
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
     * Validate worker status
     */
    public function validateStatus(?WorkerStatus $status): void
    {
        if ($status === null || !in_array($status, [WorkerStatus::ASSIGNED, WorkerStatus::UNASSIGNED, WorkerStatus::TERMINATED])) {
            $this->errors[] = 'Please select a valid worker status.';
        }
    }

    /**
     * Validate email
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
     * Validate password
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
     * Validate multiple data
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
