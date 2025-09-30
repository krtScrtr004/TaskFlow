<?php

class Project implements Entity {
    private $id;
    private string $name;
    private string $description;
    private User $manager;
    private int $budget; // In cents to avoid floating point issues
    private ?TaskContainer $tasks;
    private ProjectWorkerContainer $workers;
    private ?ProjectPhaseContainer $phases;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private ProjectTaskStatus $status;
    private DateTime $createdDateTime;

    public function __construct(
        $id,
        string $name,
        string $description,
        User $manager,
        int $budget,
        ?TaskContainer $tasks,
        ProjectWorkerContainer $workers,
        ?ProjectPhaseContainer $phases,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        ProjectTaskStatus $status,
        DateTime $createdDateTime
    ) {
        $this->id = $id;
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
    
    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getTasks(): ?TaskContainer {
        return $this->tasks;
    }

    public function getWorkers(): ProjectWorkerContainer {
        return $this->workers;
    }

    public function getPhases(): ?ProjectPhaseContainer {
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

    public function getStatus(): ProjectTaskStatus {
        return $this->status;
    }

    public function getCreatedDateTime(): DateTime {
        return $this->createdDateTime;
    }

    // Setters

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

    public function setWorkers(ProjectWorkerContainer $workers): void {
        $this->workers = $workers;
    }

    public function setPhases(?ProjectPhaseContainer $phases): void {
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

    public function setStatus(ProjectTaskStatus $status): void {
        $this->status = $status;
    }

    public function setCreatedDateTime(DateTime $createdDateTime): void {
        $this->createdDateTime = $createdDateTime;
    }

    // Other methods

    public function toArray(): array {
        echo $this->id;

        return [
            'id' => $this->id,
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
            $data['name'],
            $data['description'],
            User::fromArray($data['manager']),
            $data['budget'],
            TaskContainer::fromArray($data['tasks']),
            ProjectWorkerContainer::fromArray($data['workers']),
            ProjectPhaseContainer::fromArray($data['phases']),
            new DateTime($data['startDateTime']),
            new DateTime($data['completionDateTime']),
            new DateTime($data['actualCompletionDateTime']),
            ProjectTaskStatus::fromString($data['status']),
            new DateTime($data['createdDateTime'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}
