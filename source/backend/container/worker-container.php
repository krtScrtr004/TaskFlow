<?php 

class WorkerContainer {
    private array $workers;

    public function __construct(array $workers = []) {
        foreach ($workers as $worker) {
            if (!($worker instanceof User)) {
                throw new InvalidArgumentException("All elements of workers array must be instances of User.");
            }
            $this->addWorker($worker);
        }
    }

    public function addWorker(User $worker): void {
        if (!Role::isWorker($worker)) {
            throw new InvalidArgumentException("Only users with the 'worker' role can be added as project workers.");
        }   
        $this->workers[] = $worker;
    }

    public function getWorkers(): array {
        return $this->toArray();
    }

    public function getWorkerCount(): int {
        return count($this->workers);
    }

    public function toArray(): array {
        return $this->workers;
    }

    public static function fromArray(array $workersArray): WorkerContainer {
        return new WorkerContainer($workersArray);
    }
}