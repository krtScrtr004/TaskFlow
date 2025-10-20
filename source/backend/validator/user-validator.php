<?php

use App\Abstract\Validator;

class UserValidator extends Validator {
    /**
     * Validate first name
     */
    public function validateFirstName(?string $firstName): void {
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
    public function validateMiddleName(?string $middleName): void {
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
    public function validateLastName(?string $lastName): void {
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
    public function validateBio(?string $bio): void {
        if ($bio !== null && (strlen(trim($bio)) < 10 || strlen(trim($bio)) > 500)) {
            $this->errors['bio'] = 'Bio must be between 10 and 500 characters long.';
        }
    }

    /**
     * Validate gender
     */
    public function validateGender(?string $gender): void {
        if ($gender === null || trim($gender) === '' || !in_array($gender, ['male', 'female', 'Male', 'Female'])) {
            $this->errors['gender'] = 'Please select a valid gender.';
        }
    }

    /**
     * Validate date of birth
     */
    public function validateDateOfBirth(?string $dateOfBirth): void {
        if ($dateOfBirth === null) {
            $this->errors['dateOfBirth'] = 'Date of birth is required.';
        }

        $dob = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
        if (!$dob) {
            $this->errors['dateOfBirth'] = 'Invalid date of birth format. Use YYYY-MM-DD.';
        }

        $now = new DateTime();
        if ($dob >= $now) {
            $this->errors['dateOfBirth'] = 'Date of birth must be in the past.';
        }

        // Calculate age
        $age = $now->diff($dob)->y;
        if ($age < 18) {
            $this->errors['dateOfBirth'] = 'You must be at least 18 years old to register.';
        }
    }

    /**
     * Validate job titles
     */
    public function validateJobTitles(?string $jobTitles): void {
        if ($jobTitles === null || trim($jobTitles) === '' || strlen($jobTitles) < 1 || strlen($jobTitles) > 500) {
            $this->errors['jobTitles'] = 'Job titles must be between 1 and 500 characters long.';
        }
    }

    /**
     * Validate contact number
     */
    public function validateContactNumber(?string $contactNumber): void {
        if ($contactNumber === null || trim($contactNumber) === '' || strlen($contactNumber) < 11 || strlen($contactNumber) > 15) {
            $this->errors['contactNumber'] = 'Contact number must be between 11 and 15 characters long.';
        }
    }

    /**
     * Validate email
     */
    public function validateEmail(?string $email): void {
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
    public function validatePassword(?string $password): void {
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
