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
    protected string $middleName;
    protected string $lastName;
    protected Gender $gender;
    protected DateTime $birthDate;
    protected Role $role;
    protected JobTitleContainer $jobTitles;
    protected string $contactNumber;
    protected string $email;
    protected ?string $bio;
    protected ?string $profileLink;
    protected DateTime $createdAt;
    protected array $additionalInfo;
    private ?string $password;

    protected UserValidator $userValidator;

    public function __construct(
        ?int $id,
        ?UUID $publicId,
        string $firstName,
        string $middleName,
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

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getPublicId(): UUID
    {
        return $this->publicId;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getGender(): Gender
    {
        return $this->gender;
    }

    public function getBirthDate(): DateTime
    {
        return $this->birthDate;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getJobTitles(): JobTitleContainer
    {
        return $this->jobTitles;
    }

    public function getContactNumber(): string
    {
        return $this->contactNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function getProfileLink(): ?string
    {
        return $this->profileLink;
    }

    public function getJoinedDateTime(): DateTime
    {
        return $this->createdAt;
    }

    public function getAdditionalInfo(): array
    {
        return $this->additionalInfo;
    }

    // Setters
    public function setId(int $id): void
    {
        if ($id < 0) {
            throw new ValidationException("Invalid ID");
        }
        $this->id = $id;
    }

    public function setPublicId(UUID $publicId): void
    {
        $validator = new UuidValidator();

        $validator->validateUuid($publicId);
        if ($validator->hasErrors()) {
            throw new ValidationException("Invalid Public ID", $validator->getErrors());
        }
        $this->publicId = $publicId;
    }

    public function setFirstName(string $firstName): void
    {
        $this->userValidator->validateFirstName(trim($firstName));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid First Name", $this->userValidator->getErrors());
        }
        $this->firstName = $firstName;
    }

    public function setMiddleName(string $middleName): void
    {
        $this->userValidator->validateMiddleName(trim($middleName));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Middle Name", $this->userValidator->getErrors());
        }
        $this->middleName = $middleName;
    }

    public function setLastName(string $lastName): void
    {
        $this->userValidator->validateLastName($lastName);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Last Name", $this->userValidator->getErrors());
        }
        $this->lastName = $lastName;
    }

    public function setGender(Gender $gender): void
    {
        $this->userValidator->validateGender($gender);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Gender", $this->userValidator->getErrors());
        }
        $this->gender = $gender;
    }

    public function setBirthDate(DateTime $birthDate): void
    {
        $this->userValidator->validateBirthDate($birthDate);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Birth Date", $this->userValidator->getErrors());
        }
        $this->birthDate = $birthDate;
    }

    public function setRole(Role $role): void
    {
        $this->userValidator->validateRole($role);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Role", $this->userValidator->getErrors());
        }
        $this->role = $role;
    }

    public function setJobTitles(JobTitleContainer $jobTitles): void
    {
        $this->userValidator->validateJobTitles($jobTitles);
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Job Titles", $this->userValidator->getErrors());
        }
        $this->jobTitles = $jobTitles;
    }

    public function setContactNumber(string $contactNumber): void
    {
        $this->userValidator->validateContactNumber(trim($contactNumber));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Contact Number", $this->userValidator->getErrors());
        }
        $this->contactNumber = $contactNumber;
    }

    public function setEmail(string $email): void
    {
        $this->userValidator->validateEmail(trim($email));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Email", $this->userValidator->getErrors());
        }
        $this->email = $email;
    }

    public function setPassword(string $password): void
    {

        $this->password = $password;
    }

    public function setBio(string $bio): void
    {
        $this->userValidator->validateBio(trim($bio));
        if ($this->userValidator->hasErrors()) {
            throw new ValidationException("Invalid Bio", $this->userValidator->getErrors());
        }
        $this->bio = $bio;
    }

    public function setProfileLink(string $profileLink): void
    {
        $validator = new UrlValidator();

        $validator->validateUrl(trim($profileLink));
        if ($validator->hasErrors()) {
            throw new ValidationException("Invalid Profile Link", $validator->getErrors());
        }
        $this->profileLink = $profileLink;
    }

    public function setJoinedDateTime(DateTime $createdAt): void
    {
        if ($createdAt > new DateTime()) {
            throw new ValidationException("Invalid Created At Date");
        }
        $this->createdAt = $createdAt;
    }

    // public function setAdditionalInfo(array $additionalInfo): void {
    //     $this->additionalInfo = $additionalInfo;
    // }

    // public function addAdditionalInfo(string $key, $value): void {
    //     $this->additionalInfo[$key] = $value;
    // }

    public function toWorker(): Worker
    {
        return new Worker(
            $this->id,
            $this->publicId,
            $this->firstName,
            $this->middleName,
            $this->lastName,
            $this->gender,
            $this->birthDate,
            $this->jobTitles,
            $this->contactNumber,
            $this->email,
            $this->bio,
            $this->profileLink,
            WorkerStatus::ASSIGNED,
            new DateTime(),
            $this->additionalInfo
        );
    }

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

    public static function fromArray(array $data): self
    {
        return new User(
            $data['id'],
            $data['publicId'],
            $data['firstName'],
            $data['middleName'],
            $data['lastName'],
            $data['gender'],
            new DateTime($data['birthDate']),
            $data['role'],
            JobTitleContainer::fromArray($data['jobTitles']),
            $data['contactNumber'],
            $data['email'],
            $data['bio'],
            $data['profileLink'],
            new DateTime($data['createdAt']),
            $data['additionalInfo'] ?? []
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}