<?php

class TaskModel implements Model {

    public function save(): bool {
        return true;
    }

    public function delete(): bool {
        return true;
    }

    public static function create(mixed $data): void {
        if ($data instanceof self) {
            throw new InvalidArgumentException('Expected instance of TaskModel');
        }
    }

    public static function all(): TaskContainer {
        $tasks = new TaskContainer();
        $tasks->add(new Task(
            uniqid(),
            'Task 1',
            'This is the first task.',
            new DateTime('2023-01-02 09:00:00'),
            new DateTime('2023-01-04 17:00:00'),
            new DateTime('2023-01-12 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::DELAYED,
            new DateTime('2023-01-05 10:00:00'),
        ));
        $tasks->add(new Task(
            uniqid(),
            'Task 2',
            'This is the second task.',
            new DateTime('2023-02-01 09:00:00'),
            new DateTime('2023-02-05 17:00:00'),
            new DateTime('2023-02-06 14:00:00'),
            TaskPriority::MEDIUM,
            WorkStatus::DELAYED,
            new DateTime('2023-01-15 11:00:00'),
        ));
        return $tasks;
    }

    public static function find($id): ?self {
        return null;
    }

}