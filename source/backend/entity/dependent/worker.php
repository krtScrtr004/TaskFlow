<?php

class Worker extends User {
    private WorkerStatus $status;

    public function __construct( $id,
        string $firstName,
        string $middleName,
        string $lastName,
        Gender $gender,
        DateTime $birthDate,
        string $contactNumber,
        string $email,
        ?string $bio,
        ?string $profileLink,
        WorkerStatus $status,
        DateTime $joinedDateTime
    ) {
        parent::__construct(
            $id,
            $firstName,
            $middleName,
            $lastName,
            $gender,
            $birthDate,
            Role::WORKER,
            $contactNumber,
            $email,
            $bio,
            $profileLink,
            $joinedDateTime
        );
        $this->status = $status;
    }

    // Getters 

    public function getWorker(): User {
        return $this;
    }

    public function getStatus(): WorkerStatus {
        return $this->status;
    }

    // Setter

    public function setStatus(WorkerStatus $status): void {
        $this->status = $status;
    }

    // Other methods

    public function toArray(): array {
        $worker = parent::toArray();
        $worker['role'] = Role::WORKER->value;
        
        return $worker;
    }

    public static function fromArray(array $data): self {
        $user = User::fromArray($data['worker']);
        return new Worker(
            $user->getId(),
            $user->getFirstName(),
            $user->getMiddleName(),
            $user->getLastName(),
            $user->getGender(),
            $user->getBirthDate(),
            $user->getContactNumber(),
            $user->getEmail(),
            $user->getBio(),
            $user->getProfileLink(),
            WorkerStatus::from($data['status']),
            $user->getJoinedDateTime()
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}