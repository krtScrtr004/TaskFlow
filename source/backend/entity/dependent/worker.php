<?php

class Worker extends User {
    private WorkerStatus $status;

    public function __construct( 
        int $id,
        $publicId,
        string $firstName,
        string $middleName,
        string $lastName,
        Gender $gender,
        DateTime $birthDate,
        JobTitleContainer $jobTitles,
        string $contactNumber,
        string $email,
        ?string $bio,
        ?string $profileLink,
        WorkerStatus $status,
        DateTime $joinedDateTime,
        array $additionalInfo = []
    ) {
        parent::__construct(
            $id,
            $publicId,
            $firstName,
            $middleName,
            $lastName,
            $gender,
            $birthDate,
            Role::WORKER,
            $jobTitles,
            $contactNumber,
            $email,
            $bio,
            $profileLink,
            $joinedDateTime,
            $additionalInfo
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

    public static function toUser(Worker $worker): User {
        return new User(
            $worker->getId(),
            $worker->getPublicId(),
            $worker->getFirstName(),
            $worker->getMiddleName(),
            $worker->getLastName(),
            $worker->getGender(),
            $worker->getBirthDate(),
            Role::WORKER,
            $worker->getJobTitles(),
            $worker->getContactNumber(),
            $worker->getEmail(),
            $worker->getBio(),
            $worker->getProfileLink(),
            $worker->getJoinedDateTime(),
            $worker->getAdditionalInfo()
        );
    }

    public function toArray(): array {
        $worker = parent::toArray();
        $worker['role'] = Role::WORKER->value;
        
        return $worker;
    }

    public static function fromUser(User $user): Worker {
        if (!Role::isWorker($user)) {
            throw new InvalidArgumentException('User must have the Worker role to be converted to Worker.');
        }

        return new Worker(
            $user->getId(),
            $user->getPublicId(),
            $user->getFirstName(),
            $user->getMiddleName(),
            $user->getLastName(),
            $user->getGender(),
            $user->getBirthDate(),
            $user->getJobTitles(),
            $user->getContactNumber(),
            $user->getEmail(),
            $user->getBio(),
            $user->getProfileLink(),
            WorkerStatus::ASSIGNED,
            $user->getJoinedDateTime(),
            $user->getAdditionalInfo()
        );
    }

    public static function fromArray(array $data): self {
        $user = User::fromArray($data['worker']);
        return new Worker(
            $user->getId(),
            $user->getPublicId(),
            $user->getFirstName(),
            $user->getMiddleName(),
            $user->getLastName(),
            $user->getGender(),
            $user->getBirthDate(),
            $user->getJobTitles(),
            $user->getContactNumber(),
            $user->getEmail(),
            $user->getBio(),
            $user->getProfileLink(),
            WorkerStatus::from($data['status']),
            $user->getJoinedDateTime(),
            $user->getAdditionalInfo()
        );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
}