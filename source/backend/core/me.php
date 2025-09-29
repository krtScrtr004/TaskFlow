<?php

class Me extends User {
    private Me $me;

    private function __construct() {}

    public static function getInstance(): self {
        // TODO: Implement actual user session retrieval logic
        return new User(
            id: uniqid(),
            firstName: 'Zing',
            middleName: 'Zang',
            lastName: 'Yang',
            gender: Gender::MALE,
            birthDate: new DateTime('2000-01-01'),
            role: Role::PROJECT_MANAGER,
            contactNumber: '123-456-7890',
            email: 'zing.zang@example.com',
            bio: null,
            profileLink: null,
            joinedDateTime: new DateTime('2023-01-01 12:00:00')
        );
    }
}