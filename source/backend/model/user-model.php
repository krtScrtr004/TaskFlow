<?php

class UserModel implements Model
{

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
        if ($user instanceof self) {
            throw new InvalidArgumentException('Expected instance of UserModel');
        }
    }

    public static function all(): array
    {
        $users = [
            new User(
                uniqid(),
                'Alice',
                'B.',
                'Smith',
                Gender::FEMALE,
                new DateTime('1990-05-15'),
                Role::WORKER,
                '123-456-7890',
                'alice@example.com',
                'Experienced developer',
                null,
                new DateTime('2020-01-10')
            ),
            new User(
                uniqid(),
                'Bob',
                'C.',
                'Johnson',
                Gender::MALE,
                new DateTime('1985-08-22'),
                Role::WORKER,
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