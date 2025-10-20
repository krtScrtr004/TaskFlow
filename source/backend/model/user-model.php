<?php

class UserModel implements Model
{
    public function get() {}


    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $user): void
    {
        if (!($user instanceof self)) {
            throw new InvalidArgumentException('Expected instance of UserModel');
        }
    }

    public static function all(): array
    {
        $users = [
            new User(
                random_int(1, 1000),
                uniqid(),
                'Alice',
                'B.',
                'Smith',
                Gender::FEMALE,
                new DateTime('1990-05-15'),
                Role::WORKER,
                new JobTitleContainer(['Software Engineer', 'Team Lead', 'Architect']),
                '123-456-7890',
                'alice@example.com',
                'Experienced developer',
                null,
                new DateTime('2020-01-10')
            ),
            new User(
                random_int(1, 1000),
                uniqid(),
                'Bob',
                'C.',
                'Johnson',
                Gender::MALE,
                new DateTime('1985-08-22'),
                Role::WORKER,
                new JobTitleContainer(['Designer', 'Illustrator', 'Photographer']),
                '987-654-3210',
                'bob@example.com',
                'Skilled designer',
                null,
                new DateTime('2019-03-25')
            )
        ];
        return $users;
    }

    public static function find($id): ?self
    {
        return null;
    }

}