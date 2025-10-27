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

        $this->items[$item->getId()] = $item;
    }

    public function remove($item): void
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be removed from PhaseContainer.');
        }

        unset($this->items[$item->getId()]);
    }

    public function contains($item): bool
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be checked in PhaseContainer.');
        }
        return isset($this->items[$item->getId()]);
    }

    /**
     * Creates a PhaseContainer instance from an array of phase data.
     *
     * This method takes an array of phase data and maps each element to a Phase object
     * using the Phase::fromArray() method. It then constructs and returns a new
     * PhaseContainer containing these Phase objects.
     *
     * @param array $data Array of phase data where each element is an instance of Phase or array representing a Phase
     * @return mixed A new PhaseContainer instance containing Phase objects
     */
    public static function fromArray(array $data): mixed
    {
        $phases = new self();
        foreach ($data as $phaseData) {
            if ($phaseData instanceof Phase) {
                $phases->add($phaseData);
            } else {
                $phases->add(Phase::fromArray($phaseData));
            }
        }
        return $phases;
    }
}
