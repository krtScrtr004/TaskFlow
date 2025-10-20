<?php

namespace App\Entity;

use App\Interface\Entity;
use App\Enumeration\WorkStatus;
use App\Container\TaskContainer;
use App\Container\WorkerContainer;
use App\Container\PhaseContainer;
use App\Entity\User;
use DateTime;

class Project implements Entity {
    private int $id;
    private $publicId;
    private string $name;
    private string $description;
    private User $manager;
    private int $budget; // In cents to avoid floating point issues
    private ?TaskContainer $tasks;
    private WorkerContainer $workers;
    private ?PhaseContainer $phases;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;
    private DateTime $createdDateTime;

    public function __construct(
        int $id,
        $publicId,
        string $name,
        string $description,
        User $manager,
        int $budget,
        ?TaskContainer $tasks,
        WorkerContainer $workers,
        ?PhaseContainer $phases,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status,
        DateTime $createdDateTime
    ) {
        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = $name;
        $this->description = $description;
        $this->manager = $manager;
        $this->budget = $budget;
        $this->tasks = $tasks;
        $this->workers = $workers;
        $this->phases = $phases;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
        $this->createdDateTime = $createdDateTime;
    }

    // Getters 

    public function getId() {
        return $this->id;
    }

    public function getPublicId() {
        return $this->publicId;
    }
    
    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getTasks(): ?TaskContainer {
        return $this->tasks;
    }

    public function getWorkers(): WorkerContainer {
        return $this->workers;
    }

    public function getPhases(): ?PhaseContainer {
        return $this->phases;
    }

    public function getManager(): User {
        return $this->manager;
    }

    public function getBudget(): int {
        return $this->budget;
    }

    public function getStartDateTime(): DateTime {
        return $this->startDateTime;
    }

    public function getCompletionDateTime(): DateTime {
        return $this->completionDateTime;
    }

    public function getActualCompletionDateTime(): ?DateTime {
        return $this->actualCompletionDateTime;
    }

    public function getStatus(): WorkStatus {
        return $this->status;
    }

    public function getCreatedDateTime(): DateTime {
        return $this->createdDateTime;
    }

    // Setters

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setPublicId($publicId): void {
        $this->publicId = $publicId;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function setManager(User $manager): void {
        $this->manager = $manager;
    }

    public function setBudget(int $budget): void {
        $this->budget = $budget;
    }

    public function setTasks(?TaskContainer $tasks): void {
        $this->tasks = $tasks;
    }

    public function setWorkers(WorkerContainer $workers): void {
        $this->workers = $workers;
    }

    public function setPhases(?PhaseContainer $phases): void {
            $this->phases = $phases;
    }

    public function setStartDateTime(DateTime $startDateTime): void {
        $this->startDateTime = $startDateTime;
    }

    public function setCompletionDateTime(DateTime $completionDateTime): void {
        $this->completionDateTime = $completionDateTime;
    }

    public function setActualCompletionDateTime(?DateTime $actualCompletionDateTime): void {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    public function setStatus(WorkStatus $status): void {
        $this->status = $status;
    }

    public function setCreatedDateTime(DateTime $createdDateTime): void {
        $this->createdDateTime = $createdDateTime;
    }

    // Other methods

    public function toArray(): array {

        return [
            'id' => $this->publicId,
            'name' => $this->name,
            'description' => $this->description,
            'manager' => $this->manager->toArray(),
            'budget' => $this->budget,
            'tasks' => $this->tasks->toArray() ?? [],
            'workers' => $this->workers->toArray() ?? [],
            'phases' => $this->phases->toArray() ?? [],
            'startDateTime' => $this->startDateTime->format(DateTime::ATOM),
            'completionDateTime' => $this->completionDateTime->format(DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime->format(DateTime::ATOM),
            'status' => $this->status->getDisplayName(),
            'createdDateTime' => $this->createdDateTime->format(DateTime::ATOM)
        ];
    }

    public static function fromArray(array $data): self {
        return new Project(
            $data['id'],
            $data['publicId'],
            $data['name'],
            $data['description'],
            User::fromArray($data['manager']),
            $data['budget'],
            TaskContainer::fromArray($data['tasks']),
            WorkerContainer::fromArray($data['workers']),
            PhaseContainer::fromArray($data['phases']),
            new DateTime($data['startDateTime']),
            new DateTime($data['completionDateTime']),
            new DateTime($data['actualCompletionDateTime']),
            WorkStatus::fromString($data['status']),
            new DateTime($data['createdDateTime'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}
