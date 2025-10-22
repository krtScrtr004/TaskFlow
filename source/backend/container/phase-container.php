<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Phase;
use InvalidArgumentException;

class PhaseContainer extends Container
{
    public function __construct(array $phases = [])
    {
        foreach ($phases as $phase) {
            if (!$phase instanceof Phase) {
                throw new InvalidArgumentException('All elements of phases array must be instances of Phase.');
            }
            $this->add($phase);
        }
    }

    public function add($item): void
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be added to PhaseContainer.');
        }
        $this->items[] = $item;
    }

    public function remove($item): void
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be removed from PhaseContainer.');
        }

        $index = array_search($item, $this->items, true);
        if ($index !== false) {
            array_splice($this->items, $index, 1);
        }
    }

    /**
     * Creates a PhaseContainer instance from an array of phase data.
     *
     * This method takes an array of phase data and maps each element to a Phase object
     * using the Phase::fromArray() method. It then constructs and returns a new
     * PhaseContainer containing these Phase objects.
     *
     * @param array $data Array of phase data where each element is an array representing a Phase
     * @return mixed A new PhaseContainer instance containing Phase objects
     */
    public static function fromArray(array $data): mixed
    {
        $phases = array_map(fn($phaseData) => Phase::fromArray($phaseData), $data);
        return new PhaseContainer($phases);
    }
}
