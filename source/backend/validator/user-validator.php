<?php

namespace App\Validator;

use App\Abstract\Validator;
use App\Container\JobTitleContainer;
use App\Enumeration\WorkerStatus;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Exception\ValidationException;
use DateTime;

class UserValidator extends Validator
{

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

    // ------------------------------------------------------------------------------------------------------------------------------ //

    /**
     * Validate first name
     */
    public function validateFirstName(?string $firstName): void
    {
        if ($firstName === null || trim($firstName) === '' || strlen($firstName) < 1 || strlen($firstName) > 255) {
            $this->errors['firstName'] = 'First name must be between 1 and 255 characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{1,255}$/", $firstName)) {
            $this->errors['firstName'] = 'First name contains invalid characters.';
        }
    }

    /**
     * Validate middle name
     */
    public function validateMiddleName(?string $middleName): void
    {
        if ($middleName === null || trim($middleName) === '' || strlen($middleName) < 1 || strlen($middleName) > 255) {
            $this->errors['middleName'] = 'Middle name must be between 1 and 255 characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{1,255}$/", $middleName)) {
            $this->errors['middleName'] = 'Middle name contains invalid characters.';
        }
    }

    /**
     * Validate last name
     */
    public function validateLastName(?string $lastName): void
    {
        if ($lastName === null || trim($lastName) === '' || strlen($lastName) < 1 || strlen($lastName) > 255) {
            $this->errors['lastName'] = 'Last name must be between 1 and 255 characters long.';
        }

        if (!preg_match("/^[a-zA-Z\s'\-]{1,255}$/", $lastName)) {
            $this->errors['lastName'] = 'Last name contains invalid characters.';
        }
    }

    /**
     * Validate bio
     */
    public function validateBio(?string $bio): void
    {
        if ($bio !== null && (strlen(trim($bio)) < 10 || strlen(trim($bio)) > 500)) {
            $this->errors['bio'] = 'Bio must be between 10 and 500 characters long.';
        }
    }

    /**
     * Validate gender
     */
    public function validateGender(?Gender $gender): void
    {
        if ($gender === null || !in_array($gender, [Gender::MALE, Gender::FEMALE])) {
            $this->errors['gender'] = 'Please select a valid gender.';
        }
    }

    /**
     * Validate date of birth
     */
    public function validateBirthDate(?DateTime $birthDate): void
    {
        if ($birthDate === null) {
            $this->errors['birthDate'] = 'Date of birth is required.';
            return;
        }

        $now = new DateTime();
        if ($birthDate >= $now) {
            $this->errors['birthDate'] = 'Date of birth must be in the past.';
        }

        // Calculate age
        $age = $now->diff($birthDate)->y;
        if ($age < 18) {
            $this->errors['birthDate'] = 'You must be at least 18 years old to register.';
        }
    }

    /**
     * Validate role
     */
    public function validateRole(?Role $role): void
    {
        if ($role === null || !in_array($role, [Role::PROJECT_MANAGER, Role::WORKER])) {
            $this->errors['role'] = 'Please select a valid role.';
        }
    }

    /**
     * Validate job titles
     */
    public function validateJobTitles(?JobTitleContainer $jobTitles): void
    {
        if ($jobTitles === null || $jobTitles->count() < 1) {
            $this->errors['jobTitles'] = 'Job titles must be provided.';
            return;
        }

        foreach ($jobTitles as $jobTitle) {
            if (strlen(trim($jobTitle)) < 1 || strlen(trim($jobTitle)) > 20) {
                $this->errors['jobTitles'] = 'Each job title must be between 1 and 20 characters long.';
                break;
            }
        }
    }

    /**
     * Validate contact number
     */
    public function validateContactNumber(?string $contactNumber): void
    {
        if ($contactNumber === null || trim($contactNumber) === '' || strlen($contactNumber) < 11 || strlen($contactNumber) > 20) {
            $this->errors['contactNumber'] = 'Contact number must be between 11 and 20 characters long.';
        }

        if (!preg_match('/^\+?[\d\s\-\(\)]{11,20}$/', $contactNumber)) {
            $this->errors['contactNumber'] = 'Contact number contains invalid characters.';
        }
    }

    /**
     * Validate worker status
     */
    public function validateStatus(?WorkerStatus $status): void
    {
        if ($status === null || !in_array($status, [WorkerStatus::ASSIGNED, WorkerStatus::UNASSIGNED, WorkerStatus::TERMINATED])) {
            $this->errors['status'] = 'Please select a valid worker status.';
        }
    }

    /**
     * Validate email
     */
    public function validateEmail(?string $email): void
    {
        if ($email === null || strlen(trim($email)) < 3 || strlen(trim($email)) > 255) {
            $this->errors['email'] = 'Email must be between 3 and 255 characters long.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email address.';
        }
    }

    /**
     * Validate password
     */
    public function validatePassword(?string $password): void
    {
        if ($password === null || strlen($password) < 8 || strlen($password) > 128) {
            $this->errors['password'] = 'Password must be between 8 and 128 characters long.';
        }

        // Check for at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $this->errors['password'] = 'Password must contain at least one lowercase letter.';
        }

        // Check for at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $this->errors['password'] = 'Password must contain at least one uppercase letter.';
        }

        // Check for special characters (should NOT contain special characters except _!@'.- which are allowed)
        if (preg_match('/[^a-zA-Z0-9_!@\'\.\-]/', $password)) {
            $this->errors['password'] = 'Password contains invalid special characters. Only _!@\'.- are allowed.';
        }
    }
}
