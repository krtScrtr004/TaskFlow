<?php

class ProjectModel implements Model
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
        if ($data instanceof self) {
            throw new InvalidArgumentException('Expected instance of ProjectModel');
        }
    }

    public static function all(): array
    {
        $workers = new WorkerContainer();
        $users = UserModel::all();
        foreach ($users as $user) {
            if (Role::isWorker($user)) {
                $workers->add(Worker::fromUser($user));
            }
        }

        $tasks = TaskModel::all();
        $phases = PhaseModel::all();

        $start = new DateTime('2023-01-01 12:00:00');
        $end = new DateTime('2023-12-31 23:59:59');
        $completed = new DateTime('2023-11-30 18:30:00');
        $status = WorkStatus::getStatusFromDates($start, $end);

        $project = new Project(
            uniqid(),
            'New Project',
            'This is a new project created for testing purposes.',
            Me::getInstance(),
            10000000,
            $tasks,
            $workers,
            $phases,
            $start,
            $end,
            $completed,
            $status,
            new DateTime()
        );
        return [$project];
    }

    public static function find($id): ?self
    {
        return null;
    }

}