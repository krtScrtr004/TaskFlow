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

    public function __construct(array $projects = [])
    {
        foreach ($projects as $project) {
            if (!($project instanceof Project)) {
                throw new InvalidArgumentException("All elements of projects array must be instances of Project.");
            }

            $this->add($project);
        }
    }

    public function add($project): void
    {
        if (!$project instanceof Project) {
            throw new InvalidArgumentException("Only Project instances can be added to ProjectContainer.");
        }
        $this->increaseCount($project);
        $this->items[$project->getId()] = $project;
    }

    public function remove($item): void
    {
        if (!$item instanceof Project) {
            throw new InvalidArgumentException('Only Project instances can be removed from ProjectContainer.');
        }

        $this->decreaseCount($item);
        unset($this->items[$item->getId()]);
    }

    public function contains($item): bool
    {
        if (!$item instanceof Project) {
            throw new InvalidArgumentException('Only Project instances can be checked in ProjectContainer.');
        }
        return isset($this->items[$item->getId()]);
    }

    private function increaseCount(Project $project): void
    {
        $status = $project->getStatus()->value;
        if (array_key_exists($status, $this->projectCountByStatus)) {
            $this->projectCountByStatus[$status]++;
        }
    }

    private function decreaseCount(Project $project): void
        {
        $status = $project->getStatus()->value;
        if (array_key_exists($status, $this->projectCountByStatus)) {
            $this->projectCountByStatus[$status]--;
        }
    }

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
