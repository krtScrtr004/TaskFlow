<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Phase;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class PhaseContainer extends Container
{
    private array $pending = [];
    private array $ongoing = [];
    private array $completed = [];
    private array $delayed = [];
    private array $cancelled = [];

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
            if (!$phase instanceof Phase) {
                throw new InvalidArgumentException('All elements of phases array must be instances of Phase.');
            }
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
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be added to PhaseContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();
        switch ($status) {
            case WorkStatus::PENDING:
                $this->pending[$id] = $item;
                break;
            case WorkStatus::ONGOING:
                $this->ongoing[$id] = $item;
                break;
            case WorkStatus::COMPLETED:
                $this->completed[$id] = $item;
                break;
            case WorkStatus::DELAYED:
                $this->delayed[$id] = $item;
                break;
            case WorkStatus::CANCELLED:
                $this->cancelled[$id] = $item;
                break;
        }
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
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be removed from PhaseContainer.');
        }

        $id = $item->getId();
        $status = $item->getStatus();
        switch ($status) {
            case WorkStatus::PENDING:
                unset($this->pending[$id]);
                break;
            case WorkStatus::ONGOING:
                unset($this->ongoing[$id]);
                break;
            case WorkStatus::COMPLETED:
                unset($this->completed[$id]);
                break;
            case WorkStatus::DELAYED:
                unset($this->delayed[$id]);
                break;
            case WorkStatus::CANCELLED:
                unset($this->cancelled[$id]);
                break;
        }
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
        if (!$item instanceof Phase) {
            throw new InvalidArgumentException('Only Phase instances can be checked in PhaseContainer.');
        }

        $id = $item->getId();

        $status = $item->getStatus();
        switch ($status) {
            case WorkStatus::PENDING:
                return isset($this->pending[$id]);
            case WorkStatus::ONGOING:
                return isset($this->ongoing[$id]);
            case WorkStatus::COMPLETED:
                return isset($this->completed[$id]);
            case WorkStatus::DELAYED:
                return isset($this->delayed[$id]);
            case WorkStatus::CANCELLED:
                return isset($this->cancelled[$id]);
            default:
                return false;
        }
    }

    /**
     * Retrieves the list of pending items from the container.
     *
     * This method provides access to the internal `$pending` property, which is expected
     * to contain an array of items that are currently marked as pending within the container.
     *
     * Behavior and side effects:
     * - Returns the current state of the `$pending` property.
     * - The returned array is a direct representation of the internal state and may be empty
     *   if no items are marked as pending.
     * - This method does not modify the internal state of the container.
     *
     * @return array The array of pending items.
     */
    public function getPending(): array
    {
        return $this->pending;
    }

    /**
     * Retrieves the ongoing phases from the container.
     *
     * This method returns an array containing the ongoing phases currently stored
     * in the container. The ongoing phases represent the phases that are actively
     * being processed or are in progress.
     *
     * Behavior and side effects:
     * - Returns the value of the $ongoing property, which is expected to be an array.
     * - The method does not modify the state of the container or the $ongoing property.
     * - The returned array may be empty if no ongoing phases are present.
     *
     * @return array An array of ongoing phases.
     */
    public function getOngoing(): array
    {
        return $this->ongoing;
    }

    /**
     * Retrieves the list of completed items from the container.
     *
     * This method provides access to the internal array of completed items stored
     * within the container. The returned array represents the current state of
     * completed items at the time of invocation.
     *
     * Behavior and side effects:
     * - Returns the $completed property, which is an array of completed items.
     * - The method does not modify the internal state of the container.
     * - The returned array is a direct representation of the internal state, so
     *   any modifications to the array outside this method will not affect the
     *   container's internal state.
     *
     * @return array The array of completed items.
     */
    public function getCompleted(): array
    {
        return $this->completed;
    }

    /**
     * Retrieves the delayed items from the container.
     *
     * This method returns an array containing all items that have been marked as delayed
     * within the container. The delayed items are stored internally and can be used for
     * deferred processing or other purposes as needed by the application.
     *
     * Behavior and side effects:
     * - Returns the current state of the $delayed property, which is an array.
     * - The method does not modify the internal state of the container or the $delayed property.
     * - The returned array is a snapshot of the delayed items at the time of the method call.
     *
     * @return array An array of delayed items stored in the container.
     */
    public function getDelayed(): array
    {
        return $this->delayed;
    }

    /**
     * Retrieves the list of cancelled items from the container.
     *
     * This method provides access to the internal array of cancelled items managed by the container.
     *
     * Behavior and side effects:
     * - Returns the $this->cancelled array, which contains the items marked as cancelled.
     * - The returned array is a direct reference to the internal storage, so modifications to it
     *   will affect the container's state.
     * - This method does not perform any validation or filtering on the returned data.
     *
     * @return array The array of cancelled items.
     */
    public function getCancelled(): array
    {
        return $this->cancelled;
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
        return match ($status) {
            WorkStatus::PENDING => $this->pending,
            WorkStatus::ONGOING => $this->ongoing,
            WorkStatus::COMPLETED => $this->completed,
            WorkStatus::DELAYED => $this->delayed,
            WorkStatus::CANCELLED => $this->cancelled,
            default => [],
        };
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
            WorkStatus::PENDING->value     => count($this->pending),
            WorkStatus::ONGOING->value    => count($this->ongoing),
            WorkStatus::COMPLETED->value   => count($this->completed),
            WorkStatus::DELAYED->value     => count($this->delayed),
            WorkStatus::CANCELLED->value   => count($this->cancelled),
        ];
    }

    /**
     * Retrieves the count of phases for a specific work status.
     *
     * This method uses a match expression to return the count of phases
     * corresponding to the provided WorkStatus enum value. It checks the
     * relevant internal array and returns its count.
     *
     * Behavior and side effects:
     * - Matches the provided WorkStatus against predefined cases: PENDING, ONGOING,
     *   COMPLETED, DELAYED, and CANCELLED.
     * - Returns the count of items in the corresponding array property
     *   ($this->pending, $this->ongoing, etc.) based on the matched status.
     *
     * @param WorkStatus $status The work status to get the phase count for
     *
     * @return int The count of phases corresponding to the given work status
     */
    public function countByStatus(WorkStatus $status): int
    {
        return match ($status) {
            WorkStatus::PENDING     => count($this->pending),
            WorkStatus::ONGOING    => count($this->ongoing),
            WorkStatus::COMPLETED   => count($this->completed),
            WorkStatus::DELAYED     => count($this->delayed),
            WorkStatus::CANCELLED   => count($this->cancelled),
        };
    }

    /**
     * Reverses the order of the pending items in the container.
     *
     * This method reverses the order of the elements in the $this->pending array while preserving
     * the original keys. The reversed array is then assigned back to $this->pending and returned.
     *
     * Behavior and side effects:
     * - Uses array_reverse() with the second parameter set to true to preserve the keys.
     * - Updates the $this->pending property with the reversed array.
     * - Returns the updated $this->pending array.
     *
     * @return array The reversed array of pending items.
     */
    public function reversePending(): array
    {
        return $this->pending = array_reverse($this->pending, true);
    }

    /**
     * Reverses the order of the ongoing items in the container.
     *
     * This method reverses the order of the elements in the $this->ongoing array while preserving
     * the original keys. The reversed array is then assigned back to $this->ongoing and returned.
     *
     * Behavior and side effects:
     * - The array_reverse function is used with the $preserve_keys parameter set to true, ensuring
     *   that the original keys of the array are maintained in the reversed array.
     * - The $this->ongoing property is updated with the reversed array.
     * - The method returns the updated $this->ongoing array.
     *
     * @return array The reversed $this->ongoing array with keys preserved.
     */
    public function reverseOngoing(): array
    {
        return $this->ongoing = array_reverse($this->ongoing, true);
    }

    /**
     * Reverses the order of the completed items in the container.
     *
     * This method reverses the order of the elements in the $completed array while preserving
     * the keys. The reversed array is then stored back in the $completed property and returned.
     *
     * Behavior and side effects:
     * - The $completed array is modified in place to reflect the reversed order.
     * - The keys of the array are preserved during the reversal.
     * - If the $completed array is empty, the method returns an empty array.
     *
     * @return array The reversed $completed array with keys preserved.
     */
    public function reverseCompleted(): array
    {
        return $this->completed = array_reverse($this->completed, true);
    }

    /**
     * Reverses the order of the delayed items in the container.
     *
     * This method reverses the internal $delayed array while preserving the keys.
     * The reversed array is then stored back into the $delayed property and returned.
     *
     * Behavior and side effects:
     * - The order of elements in the $delayed array is reversed.
     * - The keys of the array are preserved during the reversal.
     * - The $delayed property is updated with the reversed array.
     *
     * @return array The reversed array of delayed items.
     */
    public function reverseDelayed(): array
    {
        return $this->delayed = array_reverse($this->delayed, true);
    }

    /**
     * Reverses the order of the cancelled items in the container.
     *
     * This method reverses the order of the elements in the $cancelled array while
     * preserving the keys. The reversed array is then stored back in the $cancelled
     * property and returned.
     *
     * Behavior and side effects:
     * - Reverses the order of the $cancelled array while maintaining key associations.
     * - Updates the $cancelled property with the reversed array.
     * - Returns the reversed array.
     *
     * @return array The reversed array of cancelled items.
     */
    public function reverseCancelled(): array
    {
        return $this->cancelled = array_reverse($this->cancelled, true);
    }

    /**
     * Clears all pending items.
     *
     * @return void
     */
    public function clearPending(): void
    {
        $this->pending = [];
    }

    /**
     * Clears all ongoing items.
     *
     * @return void
     */
    public function clearOngoing(): void
    {
        $this->ongoing = [];
    }

    /**
     * Clears all completed items.
     *
     * @return void
     */
    public function clearCompleted(): void
    {
        $this->completed = [];
    }

    /**
     * Clears all delayed items.
     *
     * @return void
     */
    public function clearDelayed(): void
    {
        $this->delayed = [];
    }

    /**
     * Clears all cancelled items.
     *
     * @return void
     */
    public function clearCancelled(): void
    {
        $this->cancelled = [];
    }

    /**
     * Clears all items across all statuses.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->pending = [];
        $this->ongoing = [];
        $this->completed = [];
        $this->delayed = [];
        $this->cancelled = [];
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
            if ($phaseData instanceof Phase) {
                $phases->add($phaseData);
            } else {
                $phases->add(Phase::fromArray($phaseData));
            }
        }
        return $phases;
    }
}
