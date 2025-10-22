<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Worker;
use App\Entity\User;
use App\Enumeration\Role;
use InvalidArgumentException;

class WorkerContainer extends Container
{
    public function __construct(array $workers = [])
    {
        foreach ($workers as $worker) {
            if (!($worker instanceof User)) {
                throw new InvalidArgumentException("All elements of workers array must be instances of User.");
            }
            $this->add($worker);
        }
    }

    public function add($worker): void
    {
        if (!Role::isWorker($worker)) {
            throw new InvalidArgumentException("Only users with the 'worker' role can be added as project workers.");
        }
        $this->items[] = $worker;
    }

    public function remove($item): void
    {
        if (!$item instanceof User) {
            throw new InvalidArgumentException('Only User instances can be removed from WorkerContainer.');
        }

        $index = array_search($item, $this->items, true);
        if ($index !== false) {
            array_splice($this->items, $index, 1);
        }
    }

    /**
     * Creates a WorkerContainer instance from an array of worker data.
     *
     * This static factory method takes an array of worker data and converts each element
     * into a Worker object using the Worker::fromArray method. It then creates and returns
     * a new WorkerContainer containing these Worker objects.
     *
     * @param array $data Array of worker data arrays, where each element contains the data
     *                    necessary to create a Worker object
     * @return WorkerContainer New WorkerContainer instance containing Worker objects created from the provided data
     */
    public static function fromArray(array $data): WorkerContainer
    {
        $workersArray = array_map(fn($workerData) => Worker::fromArray($workerData), $data);
        return new WorkerContainer($workersArray);
    }
}
