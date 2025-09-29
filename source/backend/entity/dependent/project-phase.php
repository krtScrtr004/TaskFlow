<?php

class ProjectPhase implements Entity {
    private Project $project;
    private string $name;
    private string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private DateTime $actualCompletionDateTime;

    public function __construct(
        Project $project,
        string $name,
        string $description,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        DateTime $actualCompletionDateTime
    ) {
        $this->project = $project;
        $this->name = $name;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    // Getters

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

    // Setters

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

    // Other methods

    public function toArray(): array {
        return [
            'project' => $this->project->toArray(),
            'name' => $this->name,
            'description' => $this->description,
            'startDateTime' => $this->startDateTime->format(DateTime::ATOM),
            'completionDateTime' => $this->completionDateTime->format(DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime->format(DateTime::ATOM)
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            Project::fromArray($data['project']),
            $data['name'],
            $data['description'],
            new DateTime($data['startDateTime']),
            new DateTime($data['completionDateTime']),
            new DateTime($data['actualCompletionDateTime'])
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}