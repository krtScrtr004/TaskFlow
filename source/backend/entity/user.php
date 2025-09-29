<?php

require_once __DIR__ . '/../core/role.php';

class User implements Entity {
    private $id;
    private string $firstName;
    private string $middleName;
    private string $lastName;
    private Gender $gender;
    private DateTime $birthDate;
    private Role $role;
    private string $contactNumber;
    private string $email;
    private ?string $bio;
    private ?string $profileLink;
    private DateTime $joinedDateTime;


    public function __construct(
        int $id,
        string $firstName,
        string $middleName,
        string $lastName,
        Gender $gender,
        DateTime $birthDate,
        Role $role,
        string $contactNumber,
        string $email,
        ?string $bio,
        ?string $profileLink,
        DateTime $joinedDateTime
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->middleName = $middleName;
        $this->lastName = $lastName;
        $this->gender = $gender;    
        $this->birthDate = $birthDate;
        $this->role = $role;
        $this->contactNumber = $contactNumber;
        $this->email = $email;
        $this->bio = $bio;
        $this->profileLink = $profileLink;
        $this->joinedDateTime = $joinedDateTime;
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getFirstName(): string {
        return $this->firstName;
    }

    public function getMiddleName(): string {
        return $this->middleName;
    }

    public function getLastName(): string {
        return $this->lastName;
    }

    public function getGender(): Gender {
        return $this->gender;
    }

    public function getBirthDate(): DateTime {
        return $this->birthDate;
    }

    public function getRole(): Role {
        return $this->role;
    }

    public function getContactNumber(): string {
        return $this->contactNumber;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getBio(): string {
        return $this->bio;
    }

    public function getProfileLink(): string {
        return $this->profileLink;
    }

    public function getJoinedDateTime(): DateTime {
        return $this->joinedDateTime;
    }

    // Setters
    public function setFirstName(string $firstName): void {
        $this->firstName = $firstName;
    }

    public function setMiddleName(string $middleName): void {
        $this->middleName = $middleName;
    }

    public function setLastName(string $lastName): void {
        $this->lastName = $lastName;
    }

    public function setGender(Gender $gender): void {
        $this->gender = $gender;
    }

    public function setBirthDate(DateTime $birthDate): void {
        $this->birthDate = $birthDate;
    }

    public function setRole(Role $role): void {
        $this->role = $role;
    }

    public function setContactNumber(string $contactNumber): void {
        $this->contactNumber = $contactNumber;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function setBio(?string $bio): void {
        $this->bio = $bio;
    }

    public function setProfileLink(?string $profileLink): void {
        $this->profileLink = $profileLink;
    }

    public function setJoinedDateTime(DateTime $joinedDateTime): void {
        $this->joinedDateTime = $joinedDateTime;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'middleName' => $this->middleName,
            'lastName' => $this->lastName,
            'gender' => $this->gender->getDisplayName(),
            'birthDate' => $this->birthDate->format('Y-m-d'),
            'role' => $this->role,
            'contactNumber' => $this->contactNumber,
            'email' => $this->email,
            'bio' => $this->bio,
            'profileLink' => $this->profileLink,
            'joinedDateTime' => $this->joinedDateTime->format('Y-m-d H:i:s')
        ];
    }   

    public static function fromArray(array $data): self {
        return new User(
            $data['id'],
            $data['firstName'],
            $data['middleName'],
            $data['lastName'],
            $data['gender'],
            new DateTime($data['birthDate']),
            $data['role'],
            $data['contactNumber'],
            $data['email'],
            $data['bio'],
            $data['profileLink'],
            new DateTime($data['joinedDateTime'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}