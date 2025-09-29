<?php

class ProjectWorker implements Entity {
    private Project $project;
    private User $worker;
    private WorkerStatus $status;

    public function __construct(Project $project, User $worker, WorkerStatus $status) {
        $this->project = $project;
        $this->worker = $worker;
        $this->status = $status;
    }

    // Getters 

    public function getProject(): Project {
        return $this->project;
    }

    public function getWorker(): User {
        return $this->worker;
    }

    public function getStatus(): WorkerStatus {
        return $this->status;
    }

    // Setter

    public function setProject(Project $project): void {
        $this->project = $project;
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
            'project' => $this->project->toArray(),
            'worker' => $this->worker->toArray(),
            'status' => $this->status->value
        ];
    }

    public static function fromArray(array $data): self {
        return new ProjectWorker(
            Project::fromArray($data['project']),
            User::fromArray($data['worker']),
            WorkerStatus::from($data['status'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}