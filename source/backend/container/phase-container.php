<?php

namespace App\Container;

use App\Abstract\Container;
use App\Entity\Phase;
use App\Enumeration\Priority;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class PhaseContainer extends Container
{
    /**
     * Phases indexed by status value then id
     * @var array<string, array>
     */
    private array $byStatus = [];

    private float $totalBudget = 0.0;

    /**
     * Constructs a PhaseContainer initialized with an optional array of Phase objects.
     *
     * This constructor iterates over the provided array and registers each entry into the container:
     * - Validates that each element is an instance of Phase.
     * - Throws InvalidArgumentException when a non-Phase element is encountered.
     * - Initializes the total budget to BUDGET_MIN.
     * - Adds each validated Phase to the container by calling $this->add($phase).
     *
     * @param Phase[] $phases Optional indexed array of Phase instances to seed the container (default: []).
     *
     * @throws InvalidArgumentException If any element of $phases is not an instance of Phase.
     */
    public function __construct(array $phases = [])
    {
        $this->totalBudget = BUDGET_MIN;
        foreach ($phases as $phase) {
            if (!$phase instanceof Phase)
                throw new InvalidArgumentException('All elements of phases array must be instances of Phase');
            $this->add($phase);
        }
    }

    /**
     * Adds a Phase instance to the container.
     *
     * This method enforces that the provided argument is a Phase instance, obtains the phase's
     * identifier and status, and stores the phase in the main items storage as well as in the
     * appropriate status-specific registry managed by the container.
     *
     * Behavior and side effects:
     * - Validates input is an instance of Phase and throws if not.
     * - Retrieves the phase ID using $item->getId().
     * - Retrieves the phase status using $item->getStatus().
     * - Stores the phase in $this->items indexed by the phase ID.
     * - Stores the phase in one of the status-specific arrays ($this->pending, $this->ongoing,
     *   $this->completed, $this->delayed, $this->cancelled) based on its status.
     * - Updates the total budget by adding the budget of the added phase.
     * - Overwrites any existing entry with the same ID in both the main items storage and the
     *   status-specific registry.
     *
     * @param mixed $item Phase instance to add to the container
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Phase
     *
     * @return void
     */
    public function add($item): void
    {
        if (!$item instanceof Phase)
            throw new InvalidArgumentException('Only Phase instances can be added to PhaseContainer');

        $id = $item->getId();
        $status = $item->getStatus();

        $this->byStatus[$status->value][$id] = $item;
        $this->items[$id] = $item;

        $this->totalBudget += $item->getBudget();
    }

    /**
     * Removes a Phase instance from the container.
     *
     * This method ensures that the provided argument is a Phase instance, retrieves the phase's
     * identifier via getId(), and removes the phase entry from the main items storage as well
     * as from any status-specific registries managed by the container.
     *
     * Behavior and side effects:nstance of Phase and throws an exception if not.
     * - Retrieves the phase ID using $item->getId().
     * - Unsets the phase entry from $this->items indexed by the phase ID.
     * - Unsets the phase entry from status-specific arrays: $this->pending, $this->ongoing,
     *   $this->completed, $this->delayed, and $this->cancelled.
     * - Unsetting non-existent keys is a no-op (no error is thrown if the phase ID is not present).
     * - Updates the total budget by subtracting the budget of the removed phase.
     * - This method does not perform additional cleanup beyond removing references from the
     *   container's internal structures.
     *
     * @param mixed $item Phase instance to remove from the container
     *
     * @throws InvalidArgumentException If the prov
     * - Validates that the input is an iided $item is not an instance of Phase
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Phase)
            throw new InvalidArgumentException('Only Phase instances can be removed from PhaseContainer');

        $id = $item->getId();
        $status = $item->getStatus();

        unset($this->byStatus[$status->value][$id]);
        unset($this->items[$id]);

        $this->totalBudget -= $item->getBudget();
    }

    /**
     * Checks if a Phase instance is contained within the container.
     *
     * This method verifies whether the provided Phase instance exists in the container's
     * internal storage and matches its associated status-specific registry.
     *
     * Behavior and side effects:
     * - Validates that the input is an instance of Phase and throws an exception if not.
     * - Retrieves the Phase ID using $item->getId().
     * - Checks if the Phase ID exists in the main items storage ($this->items).
     * - Depending on the Phase's status (retrieved via $item->getStatus()), checks if the
     *   Phase ID exists in the corresponding status-specific array:
     *   - $this->pending for WorkStatus::PENDING
     *   - $this->ongoing for WorkStatus::ONGOING
     *   - $this->completed for WorkStatus::COMPLETED
     *   - $this->delayed for WorkStatus::DELAYED
     *   - $this->cancelled for WorkStatus::CANCELLED
     * - Returns true if the Phase ID is present in both the main storage and the appropriate
     *   status-specific array; otherwise, returns false.
     *
     * @param mixed $item Phase instance to check for containment
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Phase
     *
     * @return bool True if the Phase instance is contained in the container, false otherwise
     */
    public function contains($item): bool
    {
        if (!$item instanceof Phase)
            throw new InvalidArgumentException('Only Phase instances can be checked in PhaseContainer');

        $id = $item->getId();
        return isset($this->items[$id]);
    }

    /**
     * Retrieves an array of items based on the provided work status.
     *
     * This method uses a match expression to return the corresponding array of items
     * for the given WorkStatus enum value. If the provided status does not match any
     * of the predefined cases, an empty array is returned by default.
     *
     * Behavior and side effects:
     * - Matches the provided WorkStatus against predefined cases: PENDING, ONGOING,
     *   COMPLETED, DELAYED, and CANCELLED.
     * - Returns the corresponding array property ($this->pending, $this->ongoing, etc.)
     *   based on the matched status.
     * - If the status does not match any predefined case, an empty array is returned.
     *
     * @param WorkStatus $status The work status to filter items by
     *
     * @return array The array of items corresponding to the given work status
     */
    public function getByStatus(WorkStatus $status): array
    {
        return $this->byStatus[$status->value] ?? [];
    }

    /**
     * Retrieves all items from the container.
     *
     * This method combines all items stored in the container across different statuses
     * into a single array and returns it. The combined array includes items from the
     * pending, ongoing, completed, delayed, and cancelled categories.
     *
     * Behavior and side effects:
     * - Merges the arrays of items from each status category into one array.
     * - The order of items in the returned array is determined by the order of merging.
     * - This method does not modify the internal state of the container.
     *
     * @return mixed An array containing all items from the container
     */
    public function getItems(): mixed
    {
        // Flatten all status buckets preserving keys where possible
        $merged = [];
        foreach ($this->byStatus as $bucket) {
            $merged = array_merge($merged, $bucket);
        }
        return $merged;
    }

    /**
     * Retrieves the total budget of all phases in the container.
     *
     * This method returns the cumulative budget of all Phase instances currently
     * stored in the container. The total budget is calculated by summing the
     * individual budgets of each Phase when they are added or removed from the container.
     *
     * Behavior and side effects:
     * - Returns the value of the $totalBudget property, which is a float.
     * - The total budget is updated whenever a Phase is added or removed from the container.
     * - This method does not modify the state of the container.
     *
     * @return float The total budget of all phases in the container
     */
    public function getTotalBudget(): float
    {
        return $this->totalBudget;
    }

    /**
     * Returns counts of all phases grouped by their status.
     *
     * This method provides an associative array representing a snapshot of phase counts
     * organized by work status. It is intended to give callers an easy way to inspect
     * how many phases exist for each status:
     * - Keys are status identifiers (e.g. string names like "pending", "ONGOING", etc.)
     * - Values are integers representing the number of phases for that status
     *
     * @return array<string,int> Associative array mapping status identifiers to phase counts
     */
    public function countAll(): array
    {
        return [
            WorkStatus::PENDING->value     => count($this->byStatus[WorkStatus::PENDING->value] ?? []),
            WorkStatus::ONGOING->value     => count($this->byStatus[WorkStatus::ONGOING->value] ?? []),
            WorkStatus::COMPLETED->value   => count($this->byStatus[WorkStatus::COMPLETED->value] ?? []),
            WorkStatus::DELAYED->value     => count($this->byStatus[WorkStatus::DELAYED->value] ?? []),
            WorkStatus::CANCELLED->value   => count($this->byStatus[WorkStatus::CANCELLED->value] ?? []),
        ];
    }

    /**
     * Retrieves the count of phases for a specific work status.
     *
     * @param WorkStatus $status The work status to get the phase count for
     *
     * @return int The count of phases corresponding to the given work status
     */
    public function countByStatus(WorkStatus $status): int
    {
        return count($this->byStatus[$status->value] ?? []);
    }

    /**
     * Reverses the order of phases for the specified status.
     *
     * This method modifies the internal storage of phases for the given status
     * by reversing their order. It returns the reversed array of phases.
     *
     * @param WorkStatus $status The work status whose phases should be reversed
     *
     * @return array The reversed array of phases for the specified status
     */
    public function reverserByStatus(WorkStatus $status): array
    {
        return $this->byStatus[$status->value] = array_reverse($this->byStatus[$status->value] ?? [], true);
    }

    /**
     * Clears all items across all statuses.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->byStatus = [];
        $this->items = [];
        $this->totalBudget = BUDGET_MIN;
    }
    
    /**
     * Clears all phases for the provided status and removes them from master items map.
     *
     * @param WorkStatus $status
     * @return void
     */
    public function clearByStatus(WorkStatus $status): void
    {
        if (!isset($this->byStatus[$status->value])) {
            return;
        }

        $ids = array_keys($this->byStatus[$status->value]);
        foreach ($ids as $id) {
            if (isset($this->items[$id])) {
                $this->totalBudget -= $this->items[$id]->getBudget();
                unset($this->items[$id]);
            }
        }

        unset($this->byStatus[$status->value]);
    }
    
    /**
     * Converts all phases in the container to an array representation.
     *
     * This method iterates over all phases and converts each to an array:
     * - Calls toArray() on each Phase instance
     * - Preserves the original order of phases
     *
     * @param bool $useSnakeCase Whether to use snake_case keys (true) or camelCase keys (false, default)
     * @return array<int, array<string, mixed>> Array of phases where each phase is represented as an associative array
     */
    public function toArray(bool $useSnakeCase = false): array
    {
        $phasesArray = [];
        foreach ($this->items as $phase) {
            $phasesArray[] = $phase->toArray($useSnakeCase);
        }
        return $phasesArray;
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
            if ($phaseData instanceof Phase) $phases->add($phaseData);
            else $phases->add(Phase::fromArray($phaseData));
        }
        return $phases;
    }
}
