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
use DateTime;
use Exception;

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
            if (!$user || !password_verify($password, $user->getPassword())) {
                throw new ValidationException('Login Failed.', [
                    'Invalid email or password.'
                ]);
            }

            // Regenerate session ID to prevent session fixation attacks
            Session::regenerate(true);

            // Create user session
            SessionAuth::setAuthorizedSession($user);

            Response::success([], 'Login successful.');
        } catch (ValidationException $e) {
            Response::error('Login Failed.', $e->getErrors(), 422);
        } catch (Exception $e) {
            Response::error('Login Failed.', [
                'An unexpected error occurred. Please try again.'
            ]);
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

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
            }

            // Extract Data
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

            // Check if user already exists
            if (UserModel::findByEmail($email)) {
                throw new ValidationException('Registration Failed.', [
                    'Email is already in use.'
                ]);
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
            $newUser = UserModel::create($partialUser);

            // Regenerate session ID to prevent session fixation attacks
            Session::regenerate(true);
            
            SessionAuth::setAuthorizedSession($newUser);

            Response::success([], 'Registration successful. Please verify your email before logging in.', 201);
        } catch (ValidationException $e) {
            // Catch validation errors
            Response::error('Registration Failed.',$e->getErrors(),422);
        } catch (Exception $e) {
            // Catch all other errors
            Response::error('Registration Failed.', ['An unexpected error occurred. Please try again.'], 500);
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
        } catch (Exception $e) {
            Response::error('Logout Failed.', [
                'An unexpected error occurred. Please try again.'
            ], 500);
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
                throw new ValidationException('Reset Password Failed.', ['Email not found.']);
            }

            $token = bin2hex(random_bytes(16));
            TemporaryLinkModel::create([
                'email' => $email,
                'token' => hash('sha256', $token)
            ]);

            // Send reset password link to email 
            if (!$instance->service->sendTemporaryLink($email, $token)) {
                throw new Exception('Failed to send reset password email.');
            }
            Session::set('temporaryResetEmail', $email);

            Response::success([], 'Reset password link has been sent to your email.');
        } catch (ValidationException $e) {
            // Clean up the temporary link if email sending fails
            TemporaryLinkModel::delete($email); 

            Response::error('Reset Password Failed.',$e->getErrors(),422);
        } catch (Exception $e) {
            // Clean up the temporary link if email sending fails
            TemporaryLinkModel::delete($email); 

            Response::error('Reset Password Failed.', ['An unexpected error occurred. Please try again.'], 500);
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

            // Extract email from session or Me instance
            $email = Session::get('temporaryResetEmail') ?? Me::getInstance()?->getEmail() ?? null;
            if (!$email || !trimOrNull($email)) {
                throw new ValidationException('Email not found for password reset.');
            }

            $user = UserModel::findByEmail($email);
            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            $data = decodeData('php://input');
            if (!$data) {
                throw new ValidationException('Cannot decode data.');
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
            TemporaryLinkModel::delete($email);

            // Remove temporary reset email from session
            Session::remove('temporaryResetEmail');

            Response::success([], 'Password changed successfully.');
        } catch (ValidationException $e) {
            Response::error('Change Password Failed.', $e->getErrors(), 422);
        } catch (ForbiddenException $e) {
            Response::error('Change Password Failed.', $e->getErrors(), 403);
        } catch (NotFoundException $e) {
            Response::error('Change Password Failed.', ['User not found.'], 404);
        } catch (Exception $e) {
            Response::error('Change Password Failed.', ['An unexpected error occurred. Please try again.'], 500);
        }
    }
}
