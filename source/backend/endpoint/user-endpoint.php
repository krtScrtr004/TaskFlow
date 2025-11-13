<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Container\JobTitleContainer;
use App\Core\Me;
use App\Core\Session;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\ValidationException;
use App\Middleware\Csrf;
use App\Middleware\Response;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Model\UserModel;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Enumeration\Gender;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\NotFoundException;
use App\Utility\PictureUpload;
use App\Utility\ResponseExceptionHandler;
use App\Utility\WorkerPerformanceCalculator;
use App\Validator\UserValidator;
use DateTime;
use Exception;
use Throwable;
use ValueError;

class UserEndpoint
{
    /**
     * Retrieves a user by their unique identifier.
     *
     * This method attempts to fetch a user from the database using the provided user ID.
     * It performs validation on the input, handles exceptions, and returns an appropriate
     * HTTP response based on the outcome:
     * - Validates that a user ID is provided and is a valid UUID.
     * - Returns a 404 error if the user is not found.
     * - Returns a 422 error if validation fails.
     * - Returns a 403 error if access is forbidden.
     * - Returns a 500 error for unexpected exceptions.
     * - On success, returns the user data with a success message.
     *
     * @param array $args Associative array of arguments with the following key:
     *      - userId: string The UUID string of the user to retrieve.
     *
     * @return void Outputs a JSON response with user data or error message.
     */
    public static function getById(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $userId = isset($args['userId'])
                ? UUID::fromString($args['userId'])
                : null;
            if (!$userId) {
                throw new ForbiddenException('User ID is required.');
            }

            $user = UserModel::findById($userId);
            if (!$user) {
                Response::error('User not found.', [], 404);
            } else {
                Response::success([$user], 'User fetched successfully.');
            }
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Get User Failed.', $e);
        }
    }

    /**
     * Retrieves a list of users filtered by a search key or returns all users.
     *
     * This endpoint supports searching for users by a provided key or fetching all users if no key is specified.
     * It enforces GET request method and requires an authorized user session.
     * Supports pagination via 'limit' and 'offset' query parameters.
     *
     * Query Parameters:
     *      - key: string (optional) Search term to filter users
     *      - limit: int (optional) Maximum number of users to return (default: 10)
     *      - offset: int (optional) Number of users to skip for pagination (default: 0)
     *
     * Responses:
     *      - 200: Success, returns an array of user data or an empty array if no users found
     *      - 403: Forbidden, if the request method is not GET or the session is unauthorized
     *      - 422: Validation failed, returns validation errors
     *      - 500: Unexpected error, returns a generic error message
     *
     * @throws ValidationException If validation of input parameters fails
     * @throws ForbiddenException If the request method is not GET or session is unauthorized
     * @throws Exception For any other unexpected errors
     *
     * @return void Outputs a JSON response with user data or error information
     */
    public static function getByKey(): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            $filter = null;
            if (isset($_GET['filter']) && trim($_GET['filter']) !== '' && $_GET['filter'] !== 'all') {
                try {
                    $filter = Role::from($_GET['filter']);
                } catch (ValueError $e) {
                    $filter = WorkStatus::from($_GET['filter']);
                }
            }

            $users = UserModel::search(
                isset($_GET['key']) ? trim($_GET['key']) : '',
                $filter instanceof Role ? $filter : null,
                $filter instanceof WorkStatus ? $filter : null,
                [
                    'limit' => isset($_GET['limit']) ? (int) $_GET['limit'] : 10,
                    'offset' => isset($_GET['offset']) ? (int) $_GET['offset'] : 0
                ]
            );

            if (!$users) {
                Response::success([], 'No users found.');
            } else {
                $return = [];
                foreach ($users as $user) {
                    $return[] = $user;
                }
                Response::success($return, 'Users fetched successfully.');
            }
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Get Users Failed.', $e);
        }
    }

    /**
     * Edits the current user's profile information.
     *
     * This method performs the following actions:
     * - Checks if the user session is authorized to edit profile data.
     * - Protects against CSRF attacks.
     * - Decodes input data from the request body.
     * - Collects and trims profile fields such as first name, middle name, last name, gender, email, contact number, bio, job titles, and password.
     * - Handles profile picture upload if provided.
     * - Validates the collected profile data using UserValidator.
     * - Throws a ValidationException if validation fails.
     * - Updates the user's profile in the database if there are changes.
     * - Resets and updates the session data with the new user information.
     * - Returns a success response if the profile is edited successfully.
     * - Handles and returns appropriate error responses for validation, not found, forbidden, and unexpected exceptions.
     *
     * No parameters are accepted; all data is retrieved from the request body and session.
     *
     * @throws ValidationException If profile data validation fails.
     * @throws NotFoundException If the user profile is not found.
     * @throws ForbiddenException If the user session is not authorized.
     * @throws Exception For any unexpected errors during the process.
     *
     * @return void
     */
    public static function edit(): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            try {
                $data = decodeData('php://input');
            } catch (Exception $e) {
                $data = [];
            }

            $profileData = [];
            if (isset($data['firstName'])) {
                $profileData['firstName'] = trim($data['firstName']);
            }

            if (isset($data['middleName'])) {
                $profileData['middleName'] = trim($data['middleName']);
            }

            if (isset($data['lastName'])) {
                $profileData['lastName'] = trim($data['lastName']);
            }

            if (isset($data['gender'])) {
                $profileData['gender'] = Gender::from($data['gender']);
            }

            if (isset($data['email'])) {
                $profileData['email'] = trim($data['email']);
            }

            if (isset($data['contactNumber'])) {
                $profileData['contactNumber'] = trim($data['contactNumber']);
            }

            if (isset($data['bio'])) {
                $profileData['bio'] = trim($data['bio']);
            }

            if (isset($data['birthDate'])) {
                $profileData['birthDate'] = new DateTime(trim($data['birthDate']));
            }

            if (isset($data['jobTitles'])) {
                $profileData['jobTitles']['toAdd'] = JobTitleContainer::fromArray($data['jobTitles']['toAdd'] ?? []);
                $profileData['jobTitles']['toRemove'] = JobTitleContainer::fromArray($data['jobTitles']['toRemove'] ?? []);
            }

            if (isset($data['password'])) {
                $profileData['password'] = $data['password'];
            }

            if (isset($data['confirm'])) {
                $profileData['confirm'] = (bool) $data['confirm'];
            }

            if (count($_FILES) > 0 && isset($_FILES['profilePicture'])) {
                // Handle profile picture upload
                $profileLink = PictureUpload::upload($_FILES['profilePicture']);
                $profileData['profileLink'] = $profileLink;
            }

            if (isset($profileData['contactNumber'])) {
                // Check for duplicate email or contact number
                $duplicates = UserModel::hasDuplicateInfo(
                    null,
                    $profileData['contactNumber'] ?? null,
                    Me::getInstance()->getId()
                );

                $duplicateErrors = [];
                if (isset($duplicates['contactNumber']) && $duplicates['contactNumber']) {
                    throw new ValidationException('Profile Edit failed.', ['Contact number is already in use by another user.']);
                }
            }

            // Validate and update profile data
            $validator = new UserValidator();
            $validator->validateMultiple([
                'firstName' => $profileData['firstName'] ?? null,
                'middleName' => $profileData['middleName'] ?? null,
                'lastName' => $profileData['lastName'] ?? null,
                'birthDate' => $profileData['birthDate'] ?? null,
                'contactNumber' => $profileData['contactNumber'] ?? null,
                'bio' => $profileData['bio'] ?? null,
                'password' => $profileData['password'] ?? null
            ]);
            if (isset($profileData['jobTitles']['toAdd']) && $profileData['jobTitles']['toAdd']->count() > 0) {
                $validator->validateJobTitles($profileData['jobTitles']['toAdd']);
            }
            if (isset($profileData['jobTitles']['toRemove']) && $profileData['jobTitles']['toRemove']->count() > 0) {
                $validator->validateJobTitles($profileData['jobTitles']['toRemove']);
            }
            if ($validator->hasErrors()) {
                throw new ValidationException('Profile Edit failed.', $validator->getErrors());
            }

            if (count($profileData) > 0) {
                $myId = Me::getInstance()->getId();

                $profileData['id'] = $myId;
                UserModel::save($profileData);

                // Reset the Me instance and session data
                Me::destroy();
                Me::instantiate(UserModel::findById($myId));
                if (Session::has('userData')) {
                    $updatedUser = Me::getInstance();
                    Session::set('userData', [
                        'id'                => $updatedUser->getId(),
                        'publicId'          => UUID::toString($updatedUser->getPublicId()),
                        'firstName'         => $updatedUser->getFirstName(),
                        'middleName'        => $updatedUser->getMiddleName(),
                        'lastName'          => $updatedUser->getLastName(),
                        'gender'            => $updatedUser->getGender()->value,
                        'birthDate'         => $updatedUser->getBirthDate()?->format('Y-m-d'),
                        'role'              => $updatedUser->getRole()->value,
                        'jobTitles'         => implode(',', $updatedUser->getJobTitles()->toArray()),
                        'contactNumber'     => $updatedUser->getContactNumber(),
                        'bio'               => $updatedUser->getBio(),
                        'profileLink'       => $updatedUser->getProfileLink(),
                        'createdAt'         => $updatedUser->getCreatedAt()->format('Y-m-d H:i:s'),
                        'confirmedAt'       => $updatedUser->getConfirmedAt()?->format('Y-m-d H:i:s'),
                        'deletedAt'         => $updatedUser->getDeletedAt()?->format('Y-m-d H:i:s'),
                        'additionalInfo'    => $updatedUser->getAdditionalInfo()
                    ]);
                }
            }
            Response::success([], 'User edited successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Profile Edit Failed.', $e);
        }
    }

    /**
     * Deletes a user profile based on provided arguments.
     *
     * This method performs the following actions:
     * - Checks if the current session is authorized to delete profiles.
     * - Protects against CSRF attacks.
     * - Validates the presence and format of the user ID.
     * - Finds the user by ID and ensures the user exists.
     * - Checks if the user is assigned to any active projects (as manager or worker).
     * - Deletes the user profile if all checks pass.
     * - Destroys the session and user context upon successful deletion.
     * - Handles and responds to various exceptions (validation, not found, forbidden, unexpected errors).
     *
     * @param array $args Associative array of arguments with the following key:
     *      - userId: string UUID of the user to be deleted.
     *
     * @throws ValidationException If validation fails (e.g., missing user ID, user assigned to active projects).
     * @throws NotFoundException If the user is not found.
     * @throws ForbiddenException If the session is not authorized.
     * @throws Exception For unexpected errors during deletion.
     *
     * @return void
     */
    public static function delete(array $args = []): void
    {
        try {
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }
            Csrf::protect();

            $userId = isset($args['userId'])
                ? UUID::fromString($args['userId'])
                : null;
            if (!$userId) {
                throw new ForbiddenException('User ID is required.');
            }

            $user = UserModel::findById($userId);
            if (!$user) {
                throw new NotFoundException('User not found.');
            }

            // Check if user is assigned to any active projects
            $hasActiveProject = Role::isProjectManager($user)
                ? ProjectModel::findManagerActiveProjectByManagerId($user->getId())
                : ProjectModel::findWorkerActiveProjectByWorkerId($user->getId());
            if ($hasActiveProject) {
                throw new ForbiddenException('Your account cannot be deleted while assigned to an active projects.');
            }

            if (UserModel::delete($user)) {
                Response::success([], 'User deleted successfully.');

                Session::destroy();
                Me::destroy();
            } else {
                throw new Exception('User deletion failed.');
            }
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('User Deletion Failed.', $e);
        }
    }
}