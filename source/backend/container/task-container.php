<?php

class TaskContainer
{
    private array $tasks;
    private int $totalTask = 0;
    private array $taskCountByStatus = [
        ProjectTaskStatus::PENDING->value => 0,
        ProjectTaskStatus::ON_GOING->value => 0,
        ProjectTaskStatus::COMPLETED->value => 0,
        ProjectTaskStatus::DELAYED->value => 0,
        ProjectTaskStatus::CANCELLED->value => 0,
    ];
    private array $taskCountByPriority = [
        TaskPriority::LOW->value => 0,
        TaskPriority::MEDIUM->value => 0,
        TaskPriority::HIGH->value => 0,
    ];

    public function __construct(array $tasks = [])
    {
        foreach ($tasks as $task) {
            if (!($task instanceof Task)) {
                throw new InvalidArgumentException("All elements of tasks array must be instances of Task.");
            }

            $this->addTask($task);
            $this->updateTaskCount($task);
        }
    }

    public function addTask($task): void
    {
        $this->tasks[] = $task;
        $this->totalTask++;
    }

    public function getTasks(): array
    {
        return $this->toArray();
    }

    public function updateTaskCount(Task $task): void
    {
        $this->totalTask++;

        $status = $task->getStatus()->value;
        if (array_key_exists($status, $this->taskCountByStatus)) {
            $this->taskCountByStatus[$status]++;
        }

        $priority = $task->getPriority()->value;
        if (array_key_exists($priority, $this->taskCountByPriority)) {
            $this->taskCountByPriority[$priority]++;
        }
    }

    public function getTaskCount(): int
    {
        return $this->totalTask;
    }

    public function getTaskCountByStatus(ProjectTaskStatus $status): int
    {
        $statusValue = $status->value;
        return $this->taskCountByStatus[$statusValue] ?? 0;
    }

    public function getAllTaskCountByStatus(): array
    {
        return $this->taskCountByStatus;
    }

    public function getTaskCountByPriority(TaskPriority $priority): int
    {
        $priorityValue = $priority->value;
        return $this->taskCountByPriority[$priorityValue] ?? 0;
    }

    public function getAllTaskCountByPriority(): array
    {
        return $this->taskCountByPriority;
    }

    public function toArray(): array
    {
        return array_map(fn($task) => $task->toArray(), $this->tasks);
    }

    public static function fromArray(array $data): TaskContainer
    {
        $tasks = array_map(fn($taskData) => Task::fromArray($taskData), $data);
        return new TaskContainer($tasks);
    }
}
