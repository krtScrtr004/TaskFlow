<?php

namespace App\Container;

use App\Abstract\Container;
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



    public static function fromArray(array $workersArray): WorkerContainer
    {
        return new WorkerContainer($workersArray);
    }
}
