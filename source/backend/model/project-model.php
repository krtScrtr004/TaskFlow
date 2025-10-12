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
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of ProjectModel');
        }
    }

    public static function all(): ProjectContainer
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

        $projects = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $start = new DateTime('2023-01-01 12:00:00');
            $end = new DateTime('2023-12-31 23:59:59');
            $completed = new DateTime('2023-11-30 18:30:00');
            $status = WorkStatus::getStatusFromDates($start, $end);
            
            $projects[] = new Project(
            random_int(1, 1000),
            uniqid(),
            'New Project ' . $i,
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc.',
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
        }
        
        return new ProjectContainer($projects);
    }

    public static function find($id): ?self
    {
        return null;
    }

}