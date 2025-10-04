<?php

class PhaseModel implements Model
{

    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $data): void
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of PhaseModel');
        }
    }

    public static function all(): PhaseContainer
    {
        $phases = new PhaseContainer([
            new Phase(
                random_int(1, 1000),
                uniqid(),
                'Phase 1',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                new DateTime('2024-12-30'),
                WorkStatus::COMPLETED
            ),
            new Phase(
                random_int(1, 1000),
                uniqid(),
                'Phase 2',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                null,
                WorkStatus::ON_GOING
            ),
            new Phase(
                random_int(1, 1000),
                uniqid(),
                'Phase 3',
                'Lorem123',
                new DateTime('2024-12-23'),
                new DateTime('2024-12-25'),
                new DateTime('2024-12-30'),
                WorkStatus::DELAYED
            )
        ]);
        return $phases;
    }

    public static function find($id): ?self
    {
        return null;
    }

}