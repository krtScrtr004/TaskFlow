<?php

class Task implements Entity {
    private $id;
    private Project $project;
    private string $name;
    private string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private DateTime $actualCompletionDateTime;
    private TaskPriority $priority;
    private ProjectTaskStatus $status;
    private DateTime $createdDateTime;

    public function __construct(
        int $id,
        Project $project,
        string $name,
        string $description,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        DateTime $actualCompletionDateTime,
        TaskPriority $priority,
        ProjectTaskStatus $status,
        DateTime $createdDateTime
    ) {
        $this->id = $id;
        $this->project = $project;
        $this->name = $name;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->priority = $priority;
        $this->status = $status;
        $this->createdDateTime = $createdDateTime;
    }

    // Getters

    public function getId(): int {
        return $this->id;
    }   

    public function getProject(): Project {
        return $this->project;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getStartDateTime(): DateTime {
        return $this->startDateTime;
    }

    public function getCompletionDateTime(): DateTime {
        return $this->completionDateTime;
    }

    public function getActualCompletionDateTime(): DateTime {
        return $this->actualCompletionDateTime;
    }

    public function getPriority(): TaskPriority {
        return $this->priority;
    }
    public function getStatus(): ProjectTaskStatus {
        return $this->status;
    }

    public function getCreatedDateTime(): DateTime {
        return $this->createdDateTime;
    }

    // Setter

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setProject(Project $project): void {
        $this->project = $project;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function setStartDateTime(DateTime $startDateTime): void {
        $this->startDateTime = $startDateTime;
    }

    public function setCompletionDateTime(DateTime $completionDateTime): void {
        $this->completionDateTime = $completionDateTime;
    }

    public function setActualCompletionDateTime(DateTime $actualCompletionDateTime): void {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    public function setPriority(TaskPriority $priority): void {
        $this->priority = $priority;
    }

    public function setStatus(ProjectTaskStatus $status): void {
        $this->status = $status;
    }

    public function setCreatedDateTime(DateTime $createdDateTime): void {
        $this->createdDateTime = $createdDateTime;
    }

    // Other methods

    public function toArray(): array {
        return [
            'id' => $this->id,
            'project' => $this->project->toArray(),
            'name' => $this->name,
            'description' => $this->description,
            'startDateTime' => $this->startDateTime->format(DateTime::ATOM),
            'completionDateTime' => $this->completionDateTime->format(DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime->format(DateTime::ATOM),
            'priority' => $this->priority->getDisplayName(),
            'status' => $this->status->getDisplayName(),
            'createdDateTime' => $this->createdDateTime->format(DateTime::ATOM)
        ];
    }

    public static function fromArray(array $data): self {
        return new Task(
            $data['id'],
            Project::fromArray($data['project']),
            $data['name'],
            $data['description'],
            new DateTime($data['startDateTime']),
            new DateTime($data['completionDateTime']),
            new DateTime($data['actualCompletionDateTime']),
            $data['priority'],
            $data['status'],
            new DateTime($data['createdDateTime'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}