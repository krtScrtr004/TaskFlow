<?php

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
        $this->items[] = $project;
        $this->increaseCount($project);
    }

    public function remove($item): void
    {
        if (!$item instanceof Project) {
            throw new InvalidArgumentException('Only Project instances can be removed from ProjectContainer.');
        }

        $index = array_search($item, $this->items, true);
        if ($index !== false) {
            array_splice($this->items, $index, 1);
        }
        $this->decreaseCount($item);
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
        return array_map(fn($project) => $project->toArray(), $this->items);
    }

    public static function fromArray(array $data): ProjectContainer
    {
        $projects = array_map(fn($projectData) => Project::fromArray($projectData), $data);
        return new ProjectContainer($projects);
    }
}
