<?php

namespace App\Dependent;

use App\Interface\Entity;
use App\Enumeration\WorkStatus;
use DateTime;

class Phase implements Entity
{
    private int $id;
    private $publicId;
    private string $name;
    private string $description;
    private DateTime $startDateTime;
    private DateTime $completionDateTime;
    private ?DateTime $actualCompletionDateTime;
    private WorkStatus $status;

    public function __construct(
        int $id,
        $publicId,
        string $name,
        string $description,
        DateTime $startDateTime,
        DateTime $completionDateTime,
        ?DateTime $actualCompletionDateTime,
        WorkStatus $status
    ) {
        $this->id = $id;
        $this->publicId = $publicId;
        $this->name = $name;
        $this->description = $description;
        $this->startDateTime = $startDateTime;
        $this->completionDateTime = $completionDateTime;
        $this->actualCompletionDateTime = $actualCompletionDateTime;
        $this->status = $status;
    }

    // Getters

    public function getId(): int
    {
        return $this->id;
    }

    public function getPublicId()
    {
        return $this->publicId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartDateTime(): DateTime
    {
        return $this->startDateTime;
    }

    public function getCompletionDateTime(): DateTime
    {
        return $this->completionDateTime;
    }

    public function getActualCompletionDateTime(): ?DateTime
    {
        return $this->actualCompletionDateTime;
    }

    public function getStatus(): WorkStatus
    {
        return $this->status;
    }

    // Setters

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setPublicId($publicId): void
    {
        $this->publicId = $publicId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setStartDateTime(DateTime $startDateTime): void
    {
        $this->startDateTime = $startDateTime;
    }

    public function setCompletionDateTime(DateTime $completionDateTime): void
    {
        $this->completionDateTime = $completionDateTime;
    }

    public function setActualCompletionDateTime(DateTime $actualCompletionDateTime): void
    {
        $this->actualCompletionDateTime = $actualCompletionDateTime;
    }

    public function setStatus(WorkStatus $status): void
    {
        $this->status = $status;
    }

    // Other methods

    public function toArray(): array
    {
        return [
            'id' => $this->publicId ?? uniqid(),
            'name' => $this->name,
            'description' => $this->description,
            'startDateTime' => $this->startDateTime->format(DateTime::ATOM),
            'completionDateTime' => $this->completionDateTime->format(DateTime::ATOM),
            'actualCompletionDateTime' => $this->actualCompletionDateTime ? $this->actualCompletionDateTime->format(DateTime::ATOM) : null,
            'status' => $this->status->value
        ];
    }

    public static function fromArray(array $data): self
    {
        $startDateTime = $data['startDateTime'] instanceof DateTime
            ? $data['startDateTime']
            : new DateTime($data['startDateTime']);
        $completionDateTime = $data['completionDateTime'] instanceof DateTime
            ? $data['completionDateTime']
            : new DateTime($data['completionDateTime']);
        $actualCompletionDateTime = $data['actualCompletionDateTime'] instanceof DateTime
            || $data['actualCompletionDateTime'] === null
            ? $data['actualCompletionDateTime']
            : new DateTime($data['actualCompletionDateTime']);
        $status = $data['status'] instanceof WorkStatus
            ? $data['status']
            : WorkStatus::fromString($data['status']);

        return new self(
            $data['id'],
            $data['publicId'],
            $data['name'],
            $data['description'],
            $startDateTime,
            $completionDateTime,
            $actualCompletionDateTime,
            $status
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}