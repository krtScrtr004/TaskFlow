<?php

namespace App\Controller;

use App\Entity\User;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Validator\UserValidator;
use App\Model\UserModel;
use App\Enumeration\Gender;
use App\Auth\SessionAuth;
use App\Container\JobTitleContainer;
use App\Exception\ValidationException;
use DateTime;
use Exception;

class AuthController implements Controller
{
    private function __construct()
    {
    }

    public static function index(array $args = []): void
    {
    }

    public static function login(): void
    {
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Login Failed.', [
                'An unexpected error occurred. Please try again.'
            ]);
        }

        $email = trimOrNull($data['email']);
        $password = trimOrNull($data['password']);

        try {
            $validator = new UserValidator();
            $validator->validateEmail($email);
            $validator->validatePassword($password);
            if ($validator->hasErrors()) {
                Response::error('Login Failed.', $validator->getErrors());
            }

            // Verify credentials
            $find = UserModel::findByEmail($email);

            if (!$find || !password_verify($password, $find->getPassword())) {
                Response::error('Login Failed.', [
                    'Invalid email or password.'
                ]);
            }

            // TODO: Check if user has current project assigned

            // Create user session
            SessionAuth::setAuthorizedSession($find);

            Response::success([
                'projectId' => null
            ], 'Login successful.');
        } catch (ValidationException $e) {
            Response::error(
                'Login Failed.',
                $e->getErrors()
            );
        } catch (DatabaseException $e) {
            Response::error('Login Failed.', [
                $e->getMessage()
            ]);
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
        $data = decodeData('php://input');
        if (!$data) {
            Response::error('Registration Failed.', [
                'An unexpected error occurred. Please try again.'
            ]);
        }

        try {
            // Extract Data
            $firstName = trimOrNull($data['firstName']);
            $middleName = trimOrNull($data['middleName']);
            $lastName = trimOrNull($data['lastName']);
            $contactNumber = trimOrNull($data['contactNumber']);
            $birthDate = isset($data['birthDate']) ? new DateTime(trimOrNull($data['birthDate'])) : null;
            $jobTitles = isset($data['jobTitles']) ? new JobTitleContainer(explode(',', trimOrNull($data['jobTitles']))) : null;
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
            SessionAuth::setAuthorizedSession($newUser);

            Response::success([], 'Registration successful. Please verify your email before logging in.', 201);
        } catch (ValidationException $e) {
            // Catch validation errors
            Response::error(
                'Registration Failed.',
                $e->getErrors()
            );
        } catch (DatabaseException $e) {
            // Catch database errors
            Response::error('Registration Failed.', [
                $e->getMessage()
            ]);
        } catch (Exception $e) {
            // Catch all other errors
            Response::error('Registration Failed.', [
                'An unexpected error occurred. Please try again.'
            ]);
        }
    }
}
