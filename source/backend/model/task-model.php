<?php

namespace App\Model;

use App\Interface\Model;
use App\Container\WorkerContainer;
use App\Container\TaskContainer;
use App\Model\UserModel;
use App\Enumeration\WorkStatus;
use App\Enumeration\TaskPriority;
use App\Entity\Task;
use DateTime;
use InvalidArgumentException;

class TaskModel implements Model
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
            throw new InvalidArgumentException('Expected instance of TaskModel');
        }
    }

    public static function all(): TaskContainer
    {
        $workers = UserModel::all();
        $workerContainer = new WorkerContainer();
        foreach ($workers as $worker) {
            $workerContainer->add($worker->toWorker());
        }

        $tasks = new TaskContainer();
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 1',
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            $workerContainer,
            new DateTime('2023-01-02 09:00:00'),
            new DateTime('2023-01-04 17:00:00'),
            new DateTime('2023-01-03 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-01-05 10:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 2',
            'This is the second task.',
            $workerContainer,
            new DateTime('2023-02-01 09:00:00'),
            new DateTime('2023-02-05 17:00:00'),
            new DateTime('2023-02-05 14:00:00'),
            TaskPriority::MEDIUM,
            WorkStatus::COMPLETED,
            new DateTime('2023-01-15 11:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 3',
            'Lorem ipsum dolor sit amet. consectetur adipiscing elit. Lorem ipsum dolor sit amet. consectetur adipiscing elit. Lorem ipsum dolor sit amet. consectetur adipiscing elit.',
            $workerContainer,
            new DateTime('2023-03-10 09:00:00'),
            new DateTime('2023-03-15 17:00:00'),
            new DateTime('2023-03-11 16:00:00'),
            TaskPriority::LOW,
            WorkStatus::COMPLETED,
            new DateTime('2023-03-01 12:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 4',
            'This is the fourth task.',
            $workerContainer,
            new DateTime('2023-04-01 09:00:00'),
            new DateTime('2023-04-10 17:00:00'),
            new DateTime('2023-04-05 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-03-20 13:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 5',
            'This is the fifth task.',
            $workerContainer,
            new DateTime('2023-05-01 09:00:00'),
            new DateTime('2023-05-07 17:00:00'),
            new DateTime('2023-05-07 16:00:00'),
            TaskPriority::MEDIUM,
            WorkStatus::COMPLETED,
            new DateTime('2023-04-25 14:00:00'),
        ));
        $tasks->add(new Task(
            random_int(1, 1000),
            uniqid(),
            'Task 6',
            'This is the sixth task.',
            $workerContainer,
            new DateTime('2023-06-01 09:00:00'),
            new DateTime('2023-06-05 17:00:00'),
            new DateTime('2023-06-04 16:00:00'),
            TaskPriority::HIGH,
            WorkStatus::COMPLETED,
            new DateTime('2023-05-20 15:00:00'),
        ));
        return $tasks;
    }

    public static function find($id): ?self
    {
        return null;
    }

}