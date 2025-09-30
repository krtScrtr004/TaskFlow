<?php

class Worker extends User {
    private User $worker;
    private WorkerStatus $status;

    public function __construct(User $worker, WorkerStatus $status) {
        $this->worker = $worker;
        $this->status = $status;
    }

    // Getters 

    public function getWorker(): User {
        return $this->worker;
    }

    public function getStatus(): WorkerStatus {
        return $this->status;
    }

    // Setter

    public function setWorker(User $worker): void {
        $this->worker = $worker;
    }

    public function setStatus(WorkerStatus $status): void {
        $this->status = $status;
    }

    // Other methods

    public function toArray(): array {
        return [
            'worker' => $this->worker->toArray(),
            'status' => $this->status->value
        ];
    }

    public static function fromArray(array $data): self {
        return new Worker(
            User::fromArray($data['worker']),
            WorkerStatus::from($data['status'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}