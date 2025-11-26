<?php

namespace App\Container;

use App\Abstract\Container;
use App\Entity\Project;
use App\Enumeration\WorkStatus;
use InvalidArgumentException;

class ProjectContainer extends Container
{
    private array $projectCountByStatus = [
        WorkStatus::PENDING->value => 0,
        WorkStatus::ON_GOING->value => 0,
        WorkStatus::COMPLETED->value => 0,
        WorkStatus::DELAYED->value => 0,
        WorkStatus::CANCELLED->value => 0,
    ];

    /**
     * Constructs the ProjectContainer with an optional list of projects.
     *
     * This constructor accepts an array of projects and populates the container by calling add()
     * for each element. It validates each element to ensure it is a Project instance and fails fast
     * by throwing an exception if any element does not meet the expected type.
     *
     * - Validates that every element in $projects is an instance of Project
     * - Adds each validated Project to the container via add()
     * - Throws an exception immediately when a non-Project element is encountered
     *
     * @param Project[] $projects Array of Project instances to initialize the container with (optional)
     *
     * @throws InvalidArgumentException If any element of $projects is not an instance of Project
     */
    public function __construct(array $projects = [])
    {
        foreach ($projects as $project) {
            if (!($project instanceof Project)) {
                throw new InvalidArgumentException("All elements of projects array must be instances of Project.");
            }

            $this->add($project);
        }
    }

    /**
     * Adds a Project instance to the container.
     *
     * This method enforces type safety and updates the container state:
     * - Validates that the provided value is an instance of Project; otherwise an InvalidArgumentException is thrown.
     * - Calls increaseCount($project) to update any internal counters or bookkeeping.
     * - Stores the Project in the container's internal items array keyed by $project->getId().
     * - If an item with the same id already exists, it will be overwritten by the new Project.
     *
     * @param Project $project Project instance to add to the container.
     *      The project's getId() value is used as the array key (expected to be a scalar such as int|string).
     *
     * @throws InvalidArgumentException If the provided $project is not an instance of Project.
     *
     * @return void
     */
    public function add($project): void
    {
        if (!$project instanceof Project) {
            throw new InvalidArgumentException("Only Project instances can be added to ProjectContainer.");
        }
        $this->increaseCount($project);
        $this->items[$project->getId()] = $project;
    }

    /**
     * Removes a Project instance from the container.
     *
     * This method performs the following actions:
     * - Ensures the provided item is an instance of Project.
     * - Throws an InvalidArgumentException when a non-Project is passed.
     * - Calls decreaseCount() to update internal counters related to the project.
     * - Unsets the project from the internal items array using $item->getId().
     *
     * @param Project $item Project instance to be removed from the container.
     *
     * @throws InvalidArgumentException If the provided $item is not a Project instance.
     *
     * @return void
     */
    public function remove($item): void
    {
        if (!$item instanceof Project) {
            throw new InvalidArgumentException('Only Project instances can be removed from ProjectContainer.');
        }

        $this->decreaseCount($item);
        unset($this->items[$item->getId()]);
    }

    /**
     * Determines whether the container contains the given Project.
     *
     * This method verifies the provided item is an instance of Project and then
     * checks the container's internal items array for an entry keyed by the
     * project's identifier (as returned by $item->getId()).
     *
     * Behavior details:
     * - Validates that $item is a Project instance; otherwise an exception is thrown.
     * - Uses a direct array key lookup (isset) on $this->items with the project's id,
     *   resulting in an O(1) membership check.
     * - Returns true if an entry with the project's id exists in the container,
     *   false otherwise.
     *
     * @param Project $item The Project instance to check for membership in the container.
     *
     * @throws InvalidArgumentException If the provided $item is not an instance of Project.
     *
     * @return bool True if the project is contained in the container, false otherwise.
     */
    public function contains($item): bool
    {
        if (!$item instanceof Project) {
            throw new InvalidArgumentException('Only Project instances can be checked in ProjectContainer.');
        }
        return isset($this->items[$item->getId()]);
    }

    /**
     * Increments the stored count for the given project's status.
     *
     * This method reads the project's status enum/value and, if an entry for that
     * status exists in the container's internal projectCountByStatus array, it
     * increments that counter by one. If no entry exists for the status, the
     * method performs no action.
     *
     * Notes:
     * - Expects Project::getStatus() to return an enum-like object with a public
     *   ->value property (scalar suitable for array keys).
     * - Mutates $this->projectCountByStatus by incrementing the integer count at
     *   the key corresponding to the project's status value.
     * - No value is returned.
     *
     * @param Project $project Project whose status counter should be incremented.
     *
     * @return void
     */
    private function increaseCount(Project $project): void
    {
        $status = $project->getStatus()->value;
        if (array_key_exists($status, $this->projectCountByStatus)) {
            $this->projectCountByStatus[$status]++;
        }
    }

    /**
     * Decrements the cached count for the given project's status.
     *
     * This method:
     * - Retrieves the project's status backing value via $project->getStatus()->value
     * - Checks whether that status exists as a key in $this->projectCountByStatus
     * - Decrements the corresponding count by one when the key exists
     *
     * Notes:
     * - If the status key is not present in $this->projectCountByStatus, the method is a no-op.
     * - The method does not enforce a non-negative lower bound; counts may become negative if not managed elsewhere.
     * - The status backing value is expected to be a scalar (string|int) suitable for array keys.
     *
     * @param Project $project Project instance whose status determines which counter to decrement.
     *
     * @return void
     */
    private function decreaseCount(Project $project): void
        {
        $status = $project->getStatus()->value;
        if (array_key_exists($status, $this->projectCountByStatus)) {
            $this->projectCountByStatus[$status]--;
        }
    }

    /**
     * Returns the number of projects for the given work status.
     *
     * This method uses the provided WorkStatus enum to look up a precomputed count
     * in the internal $projectCountByStatus mapping. It reads the enum's scalar
     * value and uses it as the lookup key. If the mapping does not contain an
     * entry for that key, the method returns 0.
     *
     * Notes:
     * - The WorkStatus enum's ->value is used as the array key.
     * - The internal mapping is expected to hold integer counts indexed by status value.
     *
     * @param WorkStatus $status WorkStatus enum instance whose value will be used as the lookup key.
     *
     * @return int Integer count of projects matching the provided status, or 0 if none are recorded.
     */
    public function getCountByStatus(WorkStatus $status): int
    {
        $statusValue = $status->value;
        return $this->projectCountByStatus[$statusValue] ?? 0;
    }


    public function toArray(): array
    {
        $projectsArray = [];
        foreach ($this->items as $project) {
            $projectsArray[] = $project->toArray();
        }
        return $projectsArray;
    }

    /**
     * Creates a ProjectContainer instance from an array of project data.
     *
     * This method transforms an array of project data into a ProjectContainer by:
     * - Converting each element in the array to a Project object using Project::fromArray()
     * - Creating a new ProjectContainer with the resulting array of Project objects
     *
     * @param array $data An array of project data arrays, where each element is an instance of Project 
     *              or an array containing the necessary data to create a Project instance
     * 
     * @return ProjectContainer A new ProjectContainer instance containing the projects
     */
    public static function fromArray(array $data): ProjectContainer
    {
        $projects = new self();
        foreach ($data as $projectData) {
            if ($projectData instanceof Project) {
                $projects->add($projectData);
            } else {
                $projects->add(Project::fromArray($projectData));
            }
        }
        return $projects;
    }
}
