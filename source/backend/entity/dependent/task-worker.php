<?php

class TaskWorker implements Entity {
    private Task $task;
    private User $worker;
    private WorkerStatus $status;

    public function __construct(Task $task, User $worker, WorkerStatus $status) {
        $this->task = $task;
        $this->worker = $worker;
        $this->status = $status;
    }

    // Getters 

    public function getTask(): Task {
        return $this->task;
    }

    public function getWorker(): User {
        return $this->worker;
    }

    public function getStatus(): WorkerStatus {
        return $this->status;
    }

    // Setter

    public function setTask(Task $task): void {
        $this->task = $task;
    }

    public function setWorker(User $worker): void {
        $this->worker = $worker;
    }

    public function setStatus(WorkerStatus $status): void {
        $this->status = $status;
    }

    // Other methods

    public function toArray(): array {
        return [
            'task' => $this->task->toArray(),
            'worker' => $this->worker->toArray(),
            'status' => $this->status->value
        ];
    }

    public static function fromArray(array $data): self {
        return new TaskWorker(
            Task::fromArray($data['task']),
            User::fromArray($data['worker']),
            WorkerStatus::from($data['status'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}