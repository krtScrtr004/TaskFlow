<?php

namespace App\Entity;

use App\Core\UUID;
use App\Model\UserModel;
use App\Interface\Entity;
use App\Enumeration\Gender;
use App\Enumeration\WorkerStatus;
use App\Enumeration\Role;
use App\Dependent\Worker;
use App\Container\JobTitleContainer;
use App\Exception\ValidationException;
use App\Validator\UserValidator;
use App\Validator\UuidValidator;
use App\Validator\UrlValidator;
use DateTime;

require_once ENUM_PATH . 'role.php';

class User extends UserModel implements Entity
{
    private ?int $id;
    private ?UUID $publicId;
    protected string $firstName;
    protected ?string $middleName;
    protected string $lastName;
    protected ?Gender $gender;
    protected ?DateTime $birthDate;
    protected ?Role $role;
    protected ?JobTitleContainer $jobTitles;
    protected ?string $contactNumber;
    protected ?string $email;
    private ?string $password;
    protected ?string $bio;
    protected ?string $profileLink;
    protected ?DateTime $createdAt;
    protected array $additionalInfo;

    protected UserValidator $userValidator;

    /**
     * User constructor.
     * 
     * Creates a new User instance with the provided details.
     * All parameters are validated through UserValidator before assignment.
     * 
     * @param int $id The unique identifier for the user in the database
     * @param UUID $publicId The public identifier for the user
     * @param string $firstName User's first name
     * @param string|null $middleName User's middle name (optional)
     * @param string $lastName User's last name
     * @param Gender $gender User's gender (enum)
     * @param DateTime $birthDate User's date of birth
     * @param Role $role User's role in the system (enum)
     * @param JobTitleContainer $jobTitles Container for user's job titles
     * @param string $contactNumber User's contact phone number
     * @param string $email User's email address
     * @param string|null$bio User's biography or description (optional)
     * @param string|null $profileLink Link to user's profile (optional)
     * @param DateTime $createdAt Timestamp when the user was created
     * @param string|null $password User's password (optional)
     * @param array $additionalInfo Additional information about the user (optional)
     * 
     * @throws ValidationException If any of the provided data fails validation
     */
    public function __construct(
        int $id,
        UUID $publicId,
        string $firstName,
        ?string $middleName,
        string $lastName,
        Gender $gender,
        DateTime $birthDate,
        Role $role,
        JobTitleContainer $jobTitles,
        string $contactNumber,
        string $email,
        ?string $bio,
        ?string $profileLink,
        DateTime $createdAt,
        ?string $password = null,
        array $additionalInfo = []
    ) {
        try {
            $this->userValidator = new UserValidator();
            $this->userValidator->validateMultiple([
                'firstName' => $firstName,
                'middleName' => $middleName,
                'lastName' => $lastName,
                'gender' => $gender,
                'birthDate' => $birthDate,
                'role' => $role,
                'jobTitles' => $jobTitles,
                'contactNumber' => $contactNumber,
                'email' => $email,
                'bio' => $bio,
                'profileLink' => $profileLink,
                'createdAt' => $createdAt,
                'password' => $password,
                'additionalInfo' => $additionalInfo
            ]);
        } catch (ValidationException $th) {
            throw $th;
        }

        $this->id = $id ?? null;
        $this->publicId = $publicId ?? null;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->lastName = $lastName;
        $this->gender = $gender;
        $this->birthDate = $birthDate;
        $this->role = $role;
        $this->jobTitles = $jobTitles;
        $this->contactNumber = $contactNumber;
        $this->email = $email;
        $this->bio = $bio ?? null;
        $this->profileLink = $profileLink ?? null;
        $this->createdAt = $createdAt;
        $this->password = $password ?? null;
        $this->additionalInfo = $additionalInfo;
    }

    // GETTERS

    /**
     * Gets the unique identifier of the user.
     *
     * @return int|null The internal ID of the user or null if not set
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Gets the public identifier of the user.
     *
     * @return UUID|null The UUID object representing the public ID or null if not set
     */
    public function getPublicId(): ?UUID
    {
        return $this->publicId;
    }

    /**
     * Gets the first name of the user.
     *
     * @return string The user's first name
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Gets the middle name of the user.
     *
     * @return string|null The user's middle name or null if not provided
     */
    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    /**
     * Gets the last name of the user.
     *
     * @return string The user's last name
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Gets the gender of the user.
     *
     * @return Gender|null The Gender enum representing the user's gender or null if not set
     */
    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    /**
     * Gets the birth date of the user.
     *
     * @return DateTime|null The DateTime object representing the user's birth date or null if not set
     */

    public function getBirthDate(): ?DateTime
    {
        return $this->birthDate;
    }

    /**
     * Gets the role of the user.
     *
     * @return Role|null The Role enum representing the user's role or null if not set
     */

    public function getRole(): ?Role
    {
        return $this->role;
    }

    /**
     * Gets the job titles associated with the user.
     *
     * @return JobTitleContainer|null The container with the user's job titles or null if not set
     */
    public function getJobTitles(): ?JobTitleContainer
    {
        return $this->jobTitles;
    }

    /**
     * Gets the contact number of the user.
     *
     * @return string|null The user's contact number or null if not provided
     */

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    /**
     * Gets the email address of the user.
     *
     * @return string|null The user's email address or null if not provided
     */

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Gets the password of the user.
     *
     * @return string|null The user's password (likely hashed) or null if not set
     */

    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Gets the biography of the user.
     *
     * @return string|null The user's biography or null if not provided
     */
    public function getBio(): ?string
    {
        return $this->bio;
    }

    /**
     * Gets the profile link of the user.
     *
     * @return string|null The user's profile link or null if not provided
     */
    public function getProfileLink(): ?string
    {
        return $this->profileLink;
    }

    /**
     * Gets the creation timestamp of the user account.
     *
     * @return DateTime The DateTime object representing when the user was created
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Gets the additional information associated with the user.
     *
     * @return array An array containing any additional user information
     */
    public function getAdditionalInfo(): array
    {
        return $this->additionalInfo;
    }

    // SETTERS

    /**
     * Sets the user ID.
     *
     * @param int $id The user ID to set
     * @throws ValidationException If the ID is negative
     * @return void
     */
    public function setId(int $id): void
    {
        if ($id < 0) {
            throw new ValidationException("Invalid ID");
        }
        $this->id = $id;
    }

    /**
     * Sets the user's public ID.
     *
     * @param UUID $publicId The UUID to set as public ID
     * @throws ValidationException If the UUID is invalid
     * @return void
     */
    public function setPublicId(UUID $publicId): void
    {
        $validator = new UuidValidator();

        $validator->validateUuid($publicId);
        if ($validator->hasErrors()) {
            throw new ValidationException("Invalid Public ID", $validator->getErrors());
        }
        $this->publicId = $publicId;
    }

    /**
     * Sets the user's first name.
     *
     * @param string $firstName The first name to set (will be trimmed)
     * @throws ValidationException If the first name is invalid
     * @return void
     */
    public function setFirstName(string $firstName): void
    {
        $this->userValidator->validateFirstName(trim($firstName));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid First Name", $this->userValidator->getErrors());
        }
        $this->firstName = $firstName;
    }

    /**
     * Sets the user's middle name.
     *
     * @param string $middleName The middle name to set (will be trimmed)
     * @throws ValidationException If the middle name is invalid
     * @return void
     */
    public function setMiddleName(string $middleName): void
    {
        $this->userValidator->validateMiddleName(trim($middleName));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Middle Name", $this->userValidator->getErrors());
        }
        $this->middleName = $middleName;
    }

    /**
     * Sets the user's last name.
     *
     * @param string $lastName The last name to set
     * @throws ValidationException If the last name is invalid
     * @return void
     */
    public function setLastName(string $lastName): void
    {
        $this->userValidator->validateLastName($lastName);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Last Name", $this->userValidator->getErrors());
        }
        $this->lastName = $lastName;
    }

    /**
     * Sets the user's gender.
     *
     * @param Gender $gender The gender enum value to set
     * @throws ValidationException If the gender is invalid
     * @return void
     */
    public function setGender(Gender $gender): void
    {
        $this->userValidator->validateGender($gender);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Gender", $this->userValidator->getErrors());
        }
        $this->gender = $gender;
    }

    /**
     * Sets the user's birth date.
     *
     * @param DateTime $birthDate The birth date to set
     * @throws ValidationException If the birth date is invalid
     * @return void
     */
    public function setBirthDate(DateTime $birthDate): void
    {
        $this->userValidator->validateBirthDate($birthDate);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Birth Date", $this->userValidator->getErrors());
        }
        $this->birthDate = $birthDate;
    }

    /**
     * Sets the user's role.
     *
     * @param Role $role The role enum value to set
     * @throws ValidationException If the role is invalid
     * @return void
     */
    public function setRole(Role $role): void
    {
        $this->userValidator->validateRole($role);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Role", $this->userValidator->getErrors());
        }
        $this->role = $role;
    }


    /**
     * Sets the user's job titles.
     *
     * @param JobTitleContainer $jobTitles Container of job titles to set
     * @throws ValidationException If the job titles are invalid
     * @return void
     */
    public function setJobTitles(JobTitleContainer $jobTitles): void
    {
        $this->userValidator->validateJobTitles($jobTitles);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Job Titles", $this->userValidator->getErrors());
        }
        $this->jobTitles = $jobTitles;
    }

    /**
     * Sets the user's contact number.
     *
     * @param string $contactNumber The contact number to set (will be trimmed)
     * @throws ValidationException If the contact number is invalid
     * @return void
     */
    public function setContactNumber(string $contactNumber): void
    {
        $this->userValidator->validateContactNumber(trim($contactNumber));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Contact Number", $this->userValidator->getErrors());
        }
        $this->contactNumber = $contactNumber;
    }

    /**
     * Sets the user's email address.
     *
     * @param string $email The email address to set (will be trimmed)
     * @throws ValidationException If the email is invalid
     * @return void
     */
    public function setEmail(string $email): void
    {
        $this->userValidator->validateEmail(trim($email));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Email", $this->userValidator->getErrors());
        }
        $this->email = $email;
    }


    /**
     * Sets the user's password.
     *
     * @param string $password The password to set
     * @throws ValidationException If the password is invalid
     * @return void
     */
    public function setPassword(?string $password): void
    {
        if ($password === null) {
            $this->password = null;
            return;
        }

        $this->userValidator->validatePassword($password);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Password", $this->userValidator->getErrors());
        }
        $this->password = $password;
    }

    /**
     * Sets the user's biography.
     *
     * @param string $bio The biography to set (will be trimmed)
     * @throws ValidationException If the bio is invalid
     * @return void
     */
    public function setBio(string $bio): void
    {
        $this->userValidator->validateBio(trim($bio));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Bio", $this->userValidator->getErrors());
        }
        $this->bio = $bio;
    }

    /**
     * Sets the user's profile link.
     *
     * @param string $profileLink The profile link to set (will be trimmed)
     * @throws ValidationException If the profile link is not a valid URL
     * @return void
     */
    public function setProfileLink(string $profileLink): void
    {
        $validator = new UrlValidator();

        $validator->validateUrl(trim($profileLink));
        if ($validator->hasErrors()) {
            throw new ValidationException("Invalid Profile Link", $validator->getErrors());
        }
        $this->profileLink = $profileLink;
    }

    /**
     * Sets additional information for the user.
     *
     * @param array $additionalInfo Associative array of additional user information
     * @return void
     */
    public function setAdditionalInfo(array $additionalInfo): void
    {
        $this->additionalInfo = $additionalInfo;
    }

    /**
     * Sets the user creation timestamp.
     *
     * @param DateTime $createdAt The creation timestamp to set
     * @throws ValidationException If the creation date is in the future
     * @return void
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        if ($createdAt > new DateTime()) {
            throw new ValidationException("Invalid Created At Date");
        }
        $this->createdAt = $createdAt;
    }

    // OTHER METHODS (UTILITY)

    /**
     * Adds or updates a key-value pair in the user's additional information.
     *
     * This method stores custom data in the additionalInfo array property,
     * which can be used for storing user metadata or preferences that
     * don't fit into the standard user properties.
     *
     * @param string $key The key identifier for the information
     * @param mixed $value The value to store (can be any type that's serializable)
     * @return void
     */
    public function addAdditionalInfo(string $key, $value): void
    {
        $this->additionalInfo[$key] = $value;
    }

    /**
     * Creates a User instance from an array of data with partial information.
     *
     * This method provides a flexible way to create a User instance without requiring
     * all fields to be present, supplying default values where necessary. It also
     * handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Ensures gender is a Gender enum
     * - Converts birthDate string to DateTime
     * - Ensures role is a Role enum
     * - Ensures jobTitles is a JobTitleContainer object
     * - Converts createdAt string to DateTime
     *
     * @param array $data Associative array containing user data with following possible keys:
     *      - id: int|null User ID
     *      - publicId: string|UUID|null Public identifier
     *      - firstName: string User's first name
     *      - middleName: string|null User's middle name
     *      - lastName: string User's last name
     *      - gender: string|Gender|null User's gender
     *      - birthDate: string|DateTime|null User's birth date
     *      - role: string|Role|null User's role
     *      - jobTitles: array|JobTitleContainer|null User's job titles
     *      - contactNumber: string|null User's contact number
     *      - email: string|null User's email address
     *      - bio: string|null User's biography
     *      - profileLink: string|null User's profile link
     *      - createdAt: string|DateTime|null User creation timestamp
     *      - password: string|null User's password
     *      - additionalInfo: array Additional user information
     * 
     * @return self New User instance created from provided data with defaults for missing values
     */
    public static function createPartial(array $data): self
    {
        // Provide default values for required fields
        $defaults = [
            'id' => $data['id'] ?? 0,
            'publicId' => $data['publicId'] ?? UUID::get(),
            'firstName' => $data['firstName'] ?? 'Unknown',
            'middleName' => $data['middleName'] ?? null,
            'lastName' => $data['lastName'] ?? 'User',
            'gender' => $data['gender'] ?? Gender::MALE,
            'birthDate' => $data['birthDate'] ?? new DateTime('2000-01-01'),
            'role' => $data['role'] ?? Role::WORKER,
            'jobTitles' => $data['jobTitles'] ?? new JobTitleContainer(),
            'contactNumber' => $data['contactNumber'] ?? '00000000000',
            'email' => $data['email'] ?? 'unknown@user.com',
            'bio' => $data['bio'] ?? null,
            'profileLink' => $data['profileLink'] ?? null,
            'createdAt' => $data['createdAt'] ?? new DateTime(),
            'password' => $data['password'] ?? null,
            'additionalInfo' => $data['additionalInfo'] ?? []
        ];

        // Handle UUID conversion
        if (isset($data['publicId']) && !($data['publicId'] instanceof UUID)) {
            $defaults['publicId'] = is_string($data['publicId'])
                ? UUID::fromString($data['publicId'])
                : null;
        }

        // Handle DateTime conversions
        if (isset($data['birthDate']) && !($data['birthDate'] instanceof DateTime)) {
            $defaults['birthDate'] = new DateTime($data['birthDate']);
        }

        if (isset($data['createdAt']) && !($data['createdAt'] instanceof DateTime)) {
            $defaults['createdAt'] = new DateTime($data['createdAt']);
        }

        // Handle enum conversions
        if (isset($data['gender']) && !($data['gender'] instanceof Gender)) {
            $defaults['gender'] = Gender::from($data['gender']);
        }

        if (isset($data['role']) && !($data['role'] instanceof Role)) {
            $defaults['role'] = Role::from($data['role']);
        }

        // Handle JobTitleContainer conversion
        if (isset($data['jobTitles']) && !($data['jobTitles'] instanceof JobTitleContainer)) {
            $defaults['jobTitles'] = is_array($data['jobTitles'])
                ? JobTitleContainer::fromArray($data['jobTitles'])
                : new JobTitleContainer();
        }

        // Create instance bypassing full constructor validation
        $instance = new self(
            $defaults['id'],
            $defaults['publicId'],
            $defaults['firstName'],
            $defaults['middleName'],
            $defaults['lastName'],
            $defaults['gender'],
            $defaults['birthDate'],
            $defaults['role'],
            $defaults['jobTitles'],
            $defaults['contactNumber'],
            $defaults['email'],
            $defaults['bio'],
            $defaults['profileLink'],
            $defaults['createdAt'],
            $defaults['password'],
            $defaults['additionalInfo']
        );

        return $instance;
    }

    /**
     * Converts the User entity to a Worker entity.
     *
     * This method creates a new Worker instance using the current User's data.
     * The Worker is created with:
     * - All personal information from the User (names, gender, birth date, etc.)
     * - Status automatically set to WorkerStatus::ASSIGNED
     * - createdAt set to current DateTime
     * - All other properties transferred directly from the User
     *
     * @return Worker New Worker instance with data from this User and status set to ASSIGNED
     */
    public function toWorker(): Worker
    {
        return new Worker(
            id: $this->id,
            publicId: $this->publicId,
            firstName: $this->firstName,
            middleName: $this->middleName,
            lastName: $this->lastName,
            gender: $this->gender,
            birthDate: $this->birthDate,
            jobTitles: $this->jobTitles,
            contactNumber: $this->contactNumber,
            email: $this->email,
            bio: $this->bio,
            profileLink: $this->profileLink,
            status: WorkerStatus::ASSIGNED,
            createdAt: new DateTime(),
            additionalInfo: $this->additionalInfo
        );
    }

    /**
     * Converts the User entity to an array representation.
     *
     * This method serializes the User object into an associative array format
     * suitable for JSON responses or data transfer. It formats certain fields:
     * - Converts UUID publicId to string representation
     * - Extracts gender display name from Gender enum
     * - Formats birthDate as 'Y-m-d' string
     * - Converts role enum to string
     * - Serializes jobTitles collection to array
     * - Formats createdAt timestamp as 'Y-m-d H:i:s' string
     *
     * @return array Associative array containing user data with following keys:
     *      - id: string Public user identifier
     *      - firstName: string User's first name
     *      - middleName: string User's middle name
     *      - lastName: string User's last name
     *      - gender: string User's gender display name
     *      - birthDate: string User's birth date (Y-m-d format)
     *      - role: string User's role
     *      - jobTitles: array User's job titles as array
     *      - contactNumber: string User's contact number
     *      - email: string User's email address
     *      - bio: string User's biography
     *      - profileLink: string User's profile link
     *      - createdAt: string User creation timestamp (Y-m-d H:i:s format)
     *      - additionalInfo: array Additional user information
     */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'gender' => $this->gender->getDisplayName(),
            'birthDate' => $this->birthDate->format('Y-m-d'),
            'role' => $this->role,
            'jobTitles' => $this->jobTitles->toArray(),
            'contactNumber' => $this->contactNumber,
            'email' => $this->email,
            'bio' => $this->bio,
            'profileLink' => $this->profileLink,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'additionalInfo' => $this->additionalInfo
        ];
    }

    /**
     * Creates a User instance from an array of data.
     *
     * This method handles different data formats and converts them to appropriate types:
     * - Converts publicId to UUID object
     * - Ensures gender is a Gender enum
     * - Converts birthDate string to DateTime
     * - Ensures role is a Role enum
     * - Ensures jobTitles is a JobTitleContainer object
     * - Converts createdAt string to DateTime
     *
     * @param array $data Associative array containing user data with following keys:
     *      - id: int User ID
     *      - publicId: string|UUID|binary Public identifier
     *      - firstName: string User's first name
     *      - middleName: string User's middle name
     *      - lastName: string User's last name
     *      - gender: string|Gender User's gender
     *      - birthDate: string|DateTime User's birth date
     *      - role: string|Role User's role
     *      - jobTitles: array|JobTitleContainer User's job titles
     *      - contactNumber: string User's contact number
     *      - email: string User's email address
     *      - bio: string User's biography
     *      - profileLink: string User's profile link
     *      - createdAt: string|DateTime User creation timestamp
     *      - additionalInfo: array (optional) Additional user information
     *      - password: string|null (optional) User's password
     * 
     * @return self New User instance created from provided data
     */
    public static function fromArray(array $data): self
    {
        $publicId = null;
        if ($data['publicId'] instanceof UUID) {
            $publicId = $data['publicId'];
        } else if (is_string($data['publicId'])) {
            $publicId = UUID::fromBinary($data['publicId']);
        }

        $gender = (!($data['gender'] instanceof Gender))
            ? Gender::from($data['gender'])
            : $data['gender'];

        $birthDate = (is_string($data['birthDate']))
            ? new DateTime($data['birthDate'])
            : $data['birthDate'];

        $role = (!($data['role'] instanceof Role))
            ? Role::tryFrom($data['role'])
            : $data['role'];

        $jobTitles = (!($data['jobTitles'] instanceof JobTitleContainer))
            ? JobTitleContainer::fromArray($data['jobTitles'])
            : $data['jobTitles'];

        $createdAt = (is_string($data['createdAt']))
            ? new DateTime($data['createdAt'])
            : $data['createdAt'];

        return new User(
            id: $data['id'],
            publicId: $publicId,
            firstName: $data['firstName'],
            middleName: $data['middleName'],
            lastName: $data['lastName'],
            gender: $gender,
            birthDate: $birthDate,
            role: $role,
            jobTitles: $jobTitles,
            contactNumber: $data['contactNumber'],
            email: $data['email'],
            bio: $data['bio'],
            profileLink: $data['profileLink'],
            createdAt: $createdAt,
            additionalInfo: $data['additionalInfo'] ?? [],
            password: $data['password'] ?? null
        );
    }

    /**
     * Serializes the user object to JSON by converting it to an array.
     * 
     * This method implements the JsonSerializable interface, allowing the object
     * to be properly encoded when using json_encode().
     * 
     * @return array The array representation of the user object
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}