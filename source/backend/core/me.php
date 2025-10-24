<?php

namespace App\Core;

use App\Core\UUID;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Entity\User;
use DateTime;

class Me extends User
{
    private static Me $me;

    public static function instantiate(User|array $data): void
    {
        if ($data instanceof User) {
            self::$me = new self(
                id: $data->getId(),
                publicId: $data->getPublicId(),
                firstName: $data->getFirstName(),
                middleName: $data->getMiddleName(),
                lastName: $data->getLastName(),
                gender: $data->getGender(),
                birthDate: $data->getBirthDate(),
                role: $data->getRole(),
                jobTitles: $data->getJobTitles(),
                contactNumber: $data->getContactNumber(),
                email: $data->getEmail(),
                bio: $data->getBio(),
                profileLink: $data->getProfileLink(),
                createdAt: $data->getCreatedAt(),
                additionalInfo: $data->getAdditionalInfo()
            );
        } else {
            self::$me = new self(
                id: $data['id'],
                publicId: UUID::fromString($data['publicId']),
                firstName: $data['firstName'],
                middleName: $data['middleName'] ?? null,
                lastName: $data['lastName'],
                gender: Gender::from($data['gender']),
                birthDate: isset($data['birthDate']) ? new DateTime($data['birthDate']) : null,
                role: Role::from($data['role']),
                jobTitles: new JobTitleContainer(explode(',', $data['jobTitles'] ?? '')),
                contactNumber: $data['contactNumber'],
                email: $data['email'],
                bio: $data['bio'] ?? null,
                profileLink: $data['profileLink'] ?? null,
                createdAt: new DateTime($data['createdAt']),
                additionalInfo: $data['additionalInfo'] ?? null
            );
        }
    }

    public static function getInstance(): ?self
    {
        return self::$me ?? null;
    }
}
