<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Phase;
use InvalidArgumentException;

class PhaseContainer extends Container
{
    /**
     * Constructs a PhaseContainer initialized with an optional array of Phase objects.
     *
     * This constructor iterates over the provided array and registers each entry into the container:
     * - Validates that each element is an instance of Phase.
     * - Throws InvalidArgumentException when a non-Phase element is encountered.
     * - Adds each validated Phase to the container by calling $this->add($phase).
     *
     * @param Phase[] $phases Optional indexed array of Phase instances to seed the container (default: []).
     *
     * @throws InvalidArgumentException If any element of $phases is not an instance of Phase.
     */
    public function __construct(array $phases = [])
    {
        foreach ($phases as $phase) {
            if (!$phase instanceof Phase) {
                throw new InvalidArgumentException('All elements of phases array must be instances of Phase.');
            }
            $this->add($phase);
        }
    }

    /**
     * Adds a Phase instance to the container.
     *
     * This method ensures only Phase objects are stored in the container.
     * - Validates that the provided $item is an instance of Phase and throws an InvalidArgumentException otherwise.
     * - Stores the Phase in the container's internal items array using the Phase's id as the key (obtained via $item->getId()).
     * - If an item with the same id already exists, it will be overwritten.
     *
     * @param Phase $item Phase instance to add to the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Phase
     *
     * @return void
     */
    public function add($item): void
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be added to PhaseContainer.');
        }

        $this->items[$item->getId()] = $item;
    }

    /**
     * Removes a Phase instance from the container.
     *
     * This method performs validation and removal:
     * - Verifies the provided item is an instance of Phase and throws InvalidArgumentException otherwise
     * - Obtains the Phase identifier via getId() and unsets the corresponding entry from the internal $items array
     * - If no entry exists for the given id the operation is a no-op
     *
     * @param Phase|mixed $item The Phase instance to remove
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Phase
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be removed from PhaseContainer.');
        }

        unset($this->items[$item->getId()]);
    }

    /**
     * Checks whether the given Phase is present in this container.
     *
     * This method performs the following:
     * - Validates that the provided item is an instance of Phase.
     * - Uses the Phase's getId() value to look up presence in the internal items map.
     * - Performs an isset() check on the internal array, providing O(1) membership testing.
     * - Throws an InvalidArgumentException if a non-Phase value is supplied.
     *
     * @param mixed $item Phase instance to check for membership. The Phase's getId() must return a string|int usable as an array key.
     *
     * @return bool True if a Phase with the same id exists in the container, false otherwise.
     *
     * @throws InvalidArgumentException If $item is not an instance of Phase.
     */
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
