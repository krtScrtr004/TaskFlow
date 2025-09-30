<?php

class Phase implements Entity {
    private string $name;
    private string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;

    public function __construct(
        string $name,
        string $description,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
    }

    // Getters

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

    public function getActualCompletionDateTime(): ?DateTime {
        return $this->actualCompletionDateTime;
    }

    public function getStatus(): WorkStatus {
        return $this->status;
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

    public function setStatus(WorkStatus $status): void {
        $this->status = $status;
    }

    // Other methods

    public function toArray(): array {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'startDateTime' => $this->startDateTime->format(DateTime::ATOM),
            'completionDateTime' => $this->completionDateTime->format(DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime->format(DateTime::ATOM),
            'status' => $this->status->value
        ];
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['name'],
            $data['description'],
            new DateTime($data['startDateTime']),
            new DateTime($data['completionDateTime']),
            new DateTime($data['actualCompletionDateTime']),
            $data['status']
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}