<?php

namespace App\Controller;

use App\Entity\User;
use App\Enumeration\Role;
use App\Exception\DatabaseException;
use App\Interface\Controller;
use App\Middleware\Response;
use App\Validator\UserValidator;
use App\Model\UserModel;
use App\Core\Me;
use App\Core\Session;
use App\Enumeration\Gender;
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

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $validator = new UserValidator();
        $validator->validateEmail($email);
        $validator->validatePassword($password);
        if ($validator->hasErrors()) {
            Response::error('Login Failed.', $validator->getErrors());
        }

        // Verify credentials
        $find = UserModel::findByEmail($email);
        if (!$find || !password_verify($password, $find['password'])) {
            Response::error('Login Failed.', [
                'Invalid email or password.'
            ]);
        }

        // TODO: Check if user has current project assigned

        if (!Me::getInstance() === null) {
            Me::instantiate($find);
        }

        if (!Session::isSet()) {
            Session::create();
        }

        if (!Session::has('user_id')) {
            Session::set('user_id', Me::getInstance()->getId());
        }

        Response::success([
            'projectId' => null
        ], 'Login successful.');
    }

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
            $firstName = isset($data['firstName']) ? (trim($data['firstName']) ?: null) : null;
            $middleName = isset($data['middleName']) ? (trim($data['middleName']) ?: null) : null;
            $lastName = isset($data['lastName']) ? (trim($data['lastName']) ?: null) : null;
            $contactNumber = isset($data['contactNumber']) ? (trim($data['contactNumber']) ?: null) : null;
            $birthDate = isset($data['birthDate']) ? new DateTime(trim($data['birthDate'])) : null;
            $jobTitles = isset($data['jobTitles']) ? new JobTitleContainer(explode(',', trim($data['jobTitles']))) : null;
            $email = isset($data['email']) ? (trim($data['email']) ?: null) : null;
            $password = isset($data['password']) ? (trim($data['password']) ?: null) : null;
            $gender = isset($data['gender']) ? (trim($data['gender']) ? Gender::tryFrom(trim($data['gender'])) : null) : null;
            $role = isset($data['role']) ? (trim($data['role']) ? Role::tryFrom(trim($data['role'])) : null) : null;

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
            UserModel::create(new User(
                id: null,
                publicId: null,
                firstName: $firstName,
                middleName: $middleName,
                lastName: $lastName,
                gender: $gender,
                birthDate: $birthDate,
                role: $role,
                jobTitles: $jobTitles,
                contactNumber: $contactNumber,
                email: $email,
                password: $password,
                profileLink: null,
                bio: null,
                createdAt: new DateTime(),
            ));

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
