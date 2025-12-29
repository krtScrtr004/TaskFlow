<?php

namespace App\Container;

use App\Abstract\Container;
use App\Dependent\Phase;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class PhaseContainer extends Container
{
    private array $pending = [];
    private array $ongoing = [];
    private array $completed = [];
    private array $delayed = [];
    private array $cancelled = [];

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
            case WorkStatus::ON_GOING:
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
        $this->items[$id] = $item;
    }

   
    /**
     * Removes a Phase instance from the container.
     *
     * This method ensures that the provided argument is a Phase instance, retrieves the phase's
     * identifier via getId(), and removes the phase entry from the main items storage as well
     * as from any status-specific registries managed by the container.
     *
     * Behavior and side effects:
     * - Validates that the input is an instance of Phase and throws an exception if not.
     * - Retrieves the phase ID using $item->getId().
     * - Unsets the phase entry from $this->items indexed by the phase ID.
     * - Unsets the phase entry from status-specific arrays: $this->pending, $this->ongoing,
     *   $this->completed, $this->delayed, and $this->cancelled.
     * - Unsetting non-existent keys is a no-op (no error is thrown if the phase ID is not present).
     * - This method does not perform additional cleanup beyond removing references from the
     *   container's internal structures.
     *
     * @param mixed $item Phase instance to remove from the container
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

        $id = $item->getId();
        $status = $item->getStatus();
        switch ($status) {
            case WorkStatus::PENDING:
                unset($this->pending[$id]);
                break;
            case WorkStatus::ON_GOING:
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
        unset($this->items[$id]);
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
     *   - $this->ongoing for WorkStatus::ON_GOING
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
        $isPresentInAll = isset($this->items[$id]);

        $status = $item->getStatus();
        switch ($status) {
            case WorkStatus::PENDING:
                return isset($this->pending[$id]) && $isPresentInAll;
            case WorkStatus::ON_GOING:
                return isset($this->ongoing[$id]) && $isPresentInAll;
            case WorkStatus::COMPLETED:
                return isset($this->completed[$id]) && $isPresentInAll;
            case WorkStatus::DELAYED:
                return isset($this->delayed[$id]) && $isPresentInAll;
            case WorkStatus::CANCELLED:
                return isset($this->cancelled[$id]) && $isPresentInAll;
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
     * - Matches the provided WorkStatus against predefined cases: PENDING, ON_GOING,
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
            WorkStatus::ON_GOING => $this->ongoing,
            WorkStatus::COMPLETED => $this->completed,
            WorkStatus::DELAYED => $this->delayed,
            WorkStatus::CANCELLED => $this->cancelled,
            default => [],
        };
    }

    /**
     * Counts the number of pending items in the container.
     *
     * This method calculates the total number of items currently marked as pending
     * by counting the elements in the $this->pending array.
     *
     * Behavior and side effects:
     * - Returns the count of elements in the $this->pending array.
     * - Assumes $this->pending is an array and does not perform additional validation.
     * - If $this->pending is empty, the method returns 0.
     *
     * @return int The number of pending items in the container
     */
    public function countPending(): int
    {
        return count($this->pending);
    }

    /**
     * Counts the number of ongoing items in the container.
     *
     * This method calculates the total number of items currently marked as ongoing
     * by returning the count of the `$this->ongoing` array.
     *
     * Behavior and side effects:
     * - Returns the count of elements in the `$this->ongoing` array.
     * - Assumes `$this->ongoing` is an array and does not perform additional validation.
     * - If `$this->ongoing` is empty, the method returns 0.
     *
     * @return int The number of ongoing items in the container
     */
    public function countOnGoing(): int
    {
        return count($this->ongoing);
    }

    /**
     * Counts the number of completed items in the container.
     *
     * This method calculates the total number of completed items by returning the count
     * of the `$this->completed` array, which is expected to store the completed items.
     *
     * Behavior and side effects:
     * - Returns the count of elements in the `$this->completed` array.
     * - Assumes that `$this->completed` is an array and is properly maintained elsewhere in the class.
     * - Does not modify the state of the object or perform any additional operations.
     *
     * @return int The total number of completed items in the container.
     */
    public function countCompleted(): int
    {
        return count($this->completed);
    }

    /**
     * Counts the number of delayed items in the container.
     *
     * This method calculates the total number of items currently stored in the
     * delayed queue of the container.
     *
     * Behavior and side effects:
     * - Returns the count of elements in the $this->delayed array.
     * - If the delayed queue is empty, the method returns 0.
     * - This method does not modify the state of the container or its delayed queue.
     *
     * @return int The total number of delayed items in the container
     */
    public function countDelayed(): int
    {
        return count($this->delayed);
    }

    /**
     * Counts the number of cancelled items in the container.
     *
     * This method calculates the total number of items marked as cancelled
     * by counting the elements in the $this->cancelled array.
     *
     * Behavior and side effects:
     * - Returns the count of elements in the $this->cancelled array.
     * - Assumes $this->cancelled is an array and does not perform additional validation.
     * - If $this->cancelled is empty, the method returns 0.
     *
     * @return int The total number of cancelled items.
     */
    public function countCancelled(): int
    {
        return count($this->cancelled);
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
