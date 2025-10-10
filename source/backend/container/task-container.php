<?php

class TaskContainer extends Container
{
    private array $taskCountByStatus = [
        WorkStatus::PENDING->value => 0,
        WorkStatus::ON_GOING->value => 0,
        WorkStatus::COMPLETED->value => 0,
        WorkStatus::DELAYED->value => 0,
        WorkStatus::CANCELLED->value => 0,
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

            $this->add($task);
            $this->increaseTaskCount($task);
        }
    }

    public function add($task): void
    {
        if (!$task instanceof Task) {
            throw new InvalidArgumentException("Only Task instances can be added to TaskContainer.");
        }
        $this->items[] = $task;
        $this->increaseTaskCount($task);
    }

    public function remove($item): void
    {
        if (!$item instanceof Task) {
            throw new InvalidArgumentException('Only Task instances can be removed from TaskContainer.');
        }

        $index = array_search($item, $this->items, true);
        if ($index !== false) {
            array_splice($this->items, $index, 1);
        }
        $this->decreaseTaskCount($item);
    }

    private function increaseTaskCount(Task $task): void
    {
        $status = $task->getStatus()->value;
        if (array_key_exists($status, $this->taskCountByStatus)) {
            $this->taskCountByStatus[$status]++;
        }

        $priority = $task->getPriority()->value;
        if (array_key_exists($priority, $this->taskCountByPriority)) {
            $this->taskCountByPriority[$priority]++;
        }
    }

    private function decreaseTaskCount(Task $task): void
    {
        $status = $task->getStatus()->value;
        if (array_key_exists($status, $this->taskCountByStatus) && $this->taskCountByStatus[$status] > 0) {
            $this->taskCountByStatus[$status]--;
        }

        $priority = $task->getPriority()->value;
        if (array_key_exists($priority, $this->taskCountByPriority) && $this->taskCountByPriority[$priority] > 0) {
            $this->taskCountByPriority[$priority]--;
        }
    }

    public function getTaskCountByStatus(WorkStatus $status): int
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
        return array_map(fn($task) => $task->toArray(), $this->items);
    }

    public static function fromArray(array $data): TaskContainer
    {
        $tasks = array_map(fn($taskData) => Task::fromArray($taskData), $data);
        return new TaskContainer($tasks);
    }
}
