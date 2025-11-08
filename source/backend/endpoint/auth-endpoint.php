<?php

namespace App\Endpoint;

use App\Core\Session;
use App\Core\UUID;
use App\Entity\User;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use App\Interface\Controller;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Validator\UserValidator;
use App\Model\UserModel;
use App\Enumeration\Gender;
use App\Auth\SessionAuth;
use App\Container\JobTitleContainer;
use App\Core\Me;
use App\Exception\ValidationException;
use DateTime;
use Exception;

class AuthEndpoint implements Controller
{
    private function __construct()
    {
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
                Response::error('Login Failed.', $validator->getErrors());
            }

            // Verify credentials
            $user = UserModel::findByEmail($email);
            if (!$user || !password_verify($password, $user->getPassword())) {
                Response::error('Login Failed.', [
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
                throw new \BadFunctionCallException('Cannot decode data.');
            }

            // Extract Data
            $firstName = trimOrNull($data['firstName']);
            $middleName = trimOrNull($data['middleName']);
            $lastName = trimOrNull($data['lastName']);
            $contactNumber = trimOrNull($data['contactNumber']);
            $birthDate = isset($data['birthDate']) ? new DateTime(trimOrNull($data['birthDate'])) : null;
            $jobTitles = isset($data['jobTitles']) ? new JobTitleContainer(array_filter(explode(',', trimOrNull($data['jobTitles'])), fn($title) => trim($title) !== '')) : null;
            $email = trimOrNull($data['email']);
            $password = trimOrNull($data['password']);
            $gender = (trimOrNull($data['gender']) ? Gender::tryFrom(trimOrNull($data['gender'])) : null);
            $role = (trimOrNull($data['role']) ? Role::tryFrom(trimOrNull($data['role'])) : null);

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

    public static function forgotPassword(): void
    {
        try {
            //code...
        } catch (\Throwable $th) {
            //throw $th;
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
}
