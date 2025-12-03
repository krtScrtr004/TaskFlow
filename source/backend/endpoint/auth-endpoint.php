<?php

namespace App\Endpoint;

use App\Core\Session;
use App\Entity\User;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Validator\UserValidator;
use App\Model\UserModel;
use App\Enumeration\Gender;
use App\Auth\SessionAuth;
use App\Container\JobTitleContainer;
use App\Core\Me;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Model\TemporaryLinkModel;
use App\Service\AuthService;
use App\Utility\ResponseExceptionHandler;
use DateTime;
use Exception;
use Throwable;

class AuthEndpoint implements Controller
{
    private AuthService $service;

    private function __construct()
    {
        $this->service = new AuthService();
    }

    public static function index(array $args = []): void
    {
    }

    /**
     * Handles user login authentication.
     *
     * This method performs the following steps:
     * - Protects against CSRF attacks.
     * - Decodes incoming JSON data from the request body.
     * - Validates the provided email and password using UserValidator.
     * - Checks user credentials against the database.
     * - Regenerates the session ID to prevent session fixation attacks.
     * - Creates an authorized user session upon successful authentication.
     * - Returns appropriate success or error responses.
     *
     * @throws ValidationException If input validation fails.
     * @throws Exception For unexpected errors during authentication.
     *
     * @return void
     */
    public static function login(): void
    {
        try {
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $email = trimOrNull($data['email']);
            $password = trimOrNull($data['password']);

            $validator = new UserValidator();
            $validator->validateEmail($email);
            $validator->validatePassword($password);
            if ($validator->hasErrors()) {
                throw new ValidationException('Login Failed.', $validator->getErrors());
            }

            // Verify credentials
            $user = UserModel::findByEmail($email);
            if (!$user || !password_verify($password, $user->getPassword()) || $user->getDeletedAt() !== null) {
                throw new ValidationException('Login Failed.', [
                    'Invalid email or password.'
                ]);
            }

            if ($user->getConfirmedAt() === null) {
                throw new ForbiddenException('Please verify your email before logging in.');
            }

            // Regenerate session ID to prevent session fixation attacks
            Session::regenerate(true);

            // Create user session
            SessionAuth::setAuthorizedSession($user);

            Response::success([], 'Login successful.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Login Failed.', $e);
        }
    }

    /**
     * Handles user registration process.
     * 
     * This method processes user registration requests by:
     * 1. Decoding and extracting user data from the request
     * 2. Validating all user input fields
     * 3. Checking if the email is already registered
     * 4. Creating a new user record in the database
     * 
     * The method expects JSON data with user details including:
     * - agreedToTerms: Boolean indicating if the user agreed to terms and conditions
     * - firstName: User's first name
     * - middleName: User's middle name (optional)
     * - lastName: User's last name
     * - contactNumber: User's contact number
     * - birthDate: User's date of birth
     * - jobTitles: Comma-separated list of job titles
     * - email: User's email address
     * - password: User's password
     * - gender: User's gender (must be valid enum value)
     * - role: User's role (must be valid enum value)
     * 
     * @throws ValidationException When input validation fails
     * @throws DatabaseException When database operations fail
     * @throws Exception For any unexpected errors
     * 
     * @return void This method sends a JSON response directly
     */
    public static function register(): void
    {
        try {
            Csrf::protect();
            $instance = new self();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            // Extract Data
            $agreedToTerms = isset($data['agreedToTerms']) ? filter_var($data['agreedToTerms'], FILTER_VALIDATE_BOOLEAN) : false;
            $firstName = trimOrNull($data['firstName']);
            $middleName = trimOrNull($data['middleName']);
            $lastName = trimOrNull($data['lastName']);
            $contactNumber = trimOrNull($data['contactNumber']);
            $birthDate = isset($data['birthDate']) ? new DateTime(trimOrNull($data['birthDate'])) : null;
            $email = trimOrNull($data['email']);
            $password = trimOrNull($data['password']);
            $gender = (trimOrNull($data['gender']) ? Gender::tryFrom(trimOrNull($data['gender'])) : null);
            $role = (trimOrNull($data['role']) ? Role::tryFrom(trimOrNull($data['role'])) : null);
            $jobTitles = null;
            if (isset($data['jobTitles'])) {
                $jobTitles = new JobTitleContainer(
                    array_filter(
                        explode(',', trimOrNull($data['jobTitles'])),
                        fn($title) => trim($title) !== ''
                    )
                );
            }

            // Check Terms Agreement
            if (!$agreedToTerms) {
                throw new ValidationException('Registration Failed.', [
                    'You must agree to the terms and conditions to register.'
                ]);
            }

            // Validate Data
            $userValidator = new UserValidator();
            $userValidator->validateMultiple([
                'firstName' => $firstName,
                'middleName' => $middleName,
                'lastName' => $lastName,
                'gender' => $gender,
                'birthDate' => $birthDate,
                'role' => $role,
                'jobTitles' => $jobTitles,
                'contactNumber' => $contactNumber,
                'email' => $email,
                'password' => $password,
            ]);
            if ($userValidator->hasErrors()) {
                throw new ValidationException(
                    'Registration Failed.',
                    $userValidator->getErrors()
                );
            }

            // Check if email / contact number already exists
            $hasDuplicate = UserModel::hasDuplicateInfo($email, $contactNumber);
            if ($hasDuplicate['hasDuplicates']) {
                $duplicateErrors = [];
                if (isset($hasDuplicate['email']) && $hasDuplicate['email']) {
                    $duplicateErrors[] = 'Email is already in use by another user.';
                }
                if (isset($hasDuplicate['contactNumber']) && $hasDuplicate['contactNumber']) {
                    $duplicateErrors[] = 'Contact number is already in use by another user.';
                }
                if (count($duplicateErrors) > 0) {
                    throw new ValidationException('Registration Failed.', $duplicateErrors);
                }
            }

            // Create user
            $partialUser = User::createPartial([
                'firstName' => $firstName,
                'middleName' => $middleName,
                'lastName' => $lastName,
                'gender' => $gender,
                'birthDate' => $birthDate,
                'role' => $role,
                'jobTitles' => $jobTitles,
                'contactNumber' => $contactNumber,
                'email' => $email,
                'password' => $password,
                'createdAt' => new DateTime()
            ]);
            UserModel::create($partialUser); 
            
            $token = bin2hex(random_bytes(16));
            TemporaryLinkModel::create([
                'email' => $email,
                'token' => $token
            ]);
            $instance->service->sendLinkForEmailVerification($email, $token);

            Response::success([], 'Registration successful. Please verify your email before logging in.', 201);
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Registration Failed.', $e);
        }
    }

    /**
     * Logs out the current user by destroying the session and user context.
     *
     * This method performs the following actions:
     * - Destroys the current session using Session::destroy()
     * - Removes the current user context with Me::destroy()
     * - Returns a success response if logout is successful
     * - Handles exceptions and returns an error response if logout fails
     *
     * @return void
     */
    public static function logout(): void
    {
        try {
            Session::destroy();
            
            Me::destroy();

            Response::success([], 'Logout successful.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Logout Failed.', $e);
        }
    }

    /**
     * Confirms a user's email address using a confirmation token.
     *
     * This method validates the provided token, checks its expiration, and confirms the user's email.
     * If the token is invalid, expired, or the user does not exist, appropriate exceptions are thrown.
     * Expired tokens result in the deletion of the unconfirmed user and the token.
     *
     * Input data is expected as a JSON payload containing:
     *      - token: string The email confirmation token
     *
     * Process:
     * - Decodes input data and validates the token.
     * - Searches for a temporary link associated with the token.
     * - Retrieves the user's email from the temporary link.
     * - Finds the user by email.
     * - Checks if the confirmation link has expired (valid for 30 days).
     * - If expired, deletes the unconfirmed user and the token.
     * - If valid, confirms the user's email and deletes the token.
     * - Returns a success response if confirmation is successful.
     *
     * Exceptions:
     * - ValidationException: If input data or token is missing/invalid.
     * - NotFoundException: If the token or user is not found.
     * - ForbiddenException: If the confirmation link has expired.
     * - Exception: For unexpected errors.
     *
     * @return void
     */
    public static function confirmEmail(): void
    {
        try {
            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $token = trimOrNull($data['token']);
            if (!$token) {
                throw new ForbiddenException('Token is required.');
            }

            $isValid = TemporaryLinkModel::search($token);
            if (!$isValid) {
                throw new NotFoundException('Invalid token provided.');
            }

            $email = $isValid['userEmail'];
            if (!$email || !trimOrNull($email)) {
                throw new NotFoundException('Email not found.');
            }

            $user = UserModel::findByEmail($email);
            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            // Check if the link has expired (valid for 30 days)
            if ((new DateTime())->getTimestamp() - (new DateTime($isValid['createdAt']))->getTimestamp() > (86400 * 30)) {
                UserModel::hardDelete($user); // Delete unconfirmed user from the database
                TemporaryLinkModel::delete($token);
                throw new ForbiddenException('The email confirmation link has expired.');
            }

            // Confirm user's email
            UserModel::save([
                'id' => $user->getId(),
                'confirm' => true
            ]);
            TemporaryLinkModel::delete($token);

            Response::success([], 'Email confirmed successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Email Confirmation Failed.', $e);
        }
    }

    /**
     * Handles password reset requests by generating and sending a reset link to the user's email.
     *
     * This method performs the following steps:
     * - Protects against CSRF attacks.
     * - Decodes input data from the request body.
     * - Validates the provided email address.
     * - Checks if a user with the given email exists.
     * - Generates a secure random token for password reset.
     * - Stores the token and email in a temporary link model.
     * - Sends a password reset link to the user's email address.
     * - Cleans up temporary links if email sending fails.
     * - Returns a success response if the process completes, or an error response if any step fails.
     *
     * @throws ValidationException If the email is invalid or not found.
     * @throws Exception If email sending fails or other unexpected errors occur.
     *
     * @return void
     */
    public static function resetPassword(): void
    {
        try {
            Csrf::protect();
            $instance = new self();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            // Extract Data
            $email = trimOrNull($data['email']);

            // Validate Data
            $userValidator = new UserValidator();
            $userValidator->validateEmail($email);
            if ($userValidator->hasErrors()) {
                throw new ValidationException(
                    'Reset Password Failed.',
                    $userValidator->getErrors()
                );
            }

            // Check if user exists
            $user = UserModel::findByEmail($email);
            if (!$user) {
                throw new NotFoundException('Email not found.');
            }

            $token = bin2hex(random_bytes(16));
            TemporaryLinkModel::create([
                'email' => $email,
                'token' => $token
            ]);

            // Send reset password link to email 
            if (!$instance->service->sendLinkForPasswordReset($email, $token)) {
                throw new Exception('Failed to send reset password email.');
            }

            Response::success([], 'Reset password link has been sent to your email.');
        } catch (Throwable $e) {
            // Clean up the temporary link if email sending fails
            TemporaryLinkModel::delete($email); 
            ResponseExceptionHandler::handle('Reset Password Failed.', $e);
        }
    }

    /**
     * Changes the password for a user identified by a temporary reset email or session.
     *
     * This method performs the following steps:
     * - Protects against CSRF attacks.
     * - Extracts the user's email from session or authenticated user instance.
     * - Validates the existence of the user by email.
     * - Decodes and validates the new password from the request body.
     * - Updates the user's password in the database.
     * - Deletes any temporary password reset link associated with the email.
     * - Removes the temporary reset email from the session.
     * - Returns a success response if the password is changed successfully.
     * - Handles validation, forbidden, not found, and unexpected errors with appropriate responses.
     *
     * @throws ValidationException If validation fails for email or password.
     * @throws ForbiddenException If the operation is not permitted.
     * @throws NotFoundException If the user is not found.
     * @throws Exception For any other unexpected errors.
     *
     * @return void
     */
    public static function changePassword(): void
    {
        try {
            Csrf::protect();

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            $token = trimOrNull($data['token']);
            if (!$token) {
                throw new ForbiddenException('Token is required.');
            }

            // Verify token validity
            $isValid = TemporaryLinkModel::search($token);
            if (!$isValid) {
                throw new NotFoundException('Invalid token provided.');
            }

            $email = $isValid['userEmail'];
            if (!$email || !trimOrNull($email)) {
                throw new NotFoundException('Email not found.');
            }

            // Check if the link has expired (valid for 5 minutes)
            $createdAt = new DateTime($isValid['updatedAt'] ?? $isValid['createdAt']);
            if ((new DateTime())->getTimestamp() - $createdAt->getTimestamp() > 300) {
                TemporaryLinkModel::delete($token);
                throw new ForbiddenException('The password reset link has expired. Please request a new one.');
            }

            $user = UserModel::findByEmail($email);
            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            $newPassword = trimOrNull($data['password']);
            $validator = new UserValidator();
            $validator->validatePassword($newPassword);
            if ($validator->hasErrors()) {
                throw new ValidationException(
                    'Change Password Failed.',
                    $validator->getErrors()
                );
            }

            // Update password
            UserModel::save([
                'id'        => $user->getId(),
                'password'  => $newPassword
            ]);

            // Delete temporary link token in the database
            TemporaryLinkModel::delete($token);

            Response::success([], 'Password changed successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Change Password Failed.', $e);
        }
    }
}