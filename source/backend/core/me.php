<?php

namespace App\Core;

use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Container\JobTitleContainer;
use App\Entity\User;
use DateTime;

class Me extends User
{
    private Me $me;

    private function __construct()
    {
        // TODO: Implement actual user session retrieval logic
        parent::__construct(
            id: random_int(1, 1000),
            publicId: uniqid(),
            firstName: 'Zing',
            middleName: 'Zang',
            lastName: 'Yang',
            gender: Gender::MALE,
            birthDate: new DateTime('2000-01-01'),
            role: Role::PROJECT_MANAGER,
            jobTitles: new JobTitleContainer(['Project Manager']),
            contactNumber: '123-456-7890',
            email: 'zing.zang@example.com',
            bio: null,
            profileLink: null,
            createdAt: new DateTime('2023-01-01 12:00:00'),
            additionalInfo: [
                'terminationCount' => 3
            ]
        );
    }

    public static function instantiate(array $data): void
    {
        self::$me = parent::fromArray($data);
    }

    public static function getInstance(): self
    {
        return new self();
    }
}
