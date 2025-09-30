<?php

class ProjectPhaseContainer
{
    private array $projectPhase;

    public function __construct(array $phases = [])
    {
        foreach ($phases as $phase) {
            if (!$phase instanceof ProjectPhase) {
                throw new InvalidArgumentException('All elements of tasks array must be instances of Task.');
            }
            $this->addPhase($phase);
        }
    }

    public function addPhase(ProjectPhase $phase): void
    {
        $this->projectPhase[] = $phase;
    }

    public function getPhases(): array
    {
        return $this->projectPhase;
    }

    public function getProjectPhasesCount(): int
    {
        return count($this->projectPhase);
    }

    public function toArray(): array
    {
        return array_map(fn($phase) => $phase->toArray(), $this->projectPhase);
    }

    public static function fromArray(array $data): ProjectPhaseContainer
    {
        $phases = array_map(fn($phaseData) => ProjectPhase::fromArray($phaseData), $data);
        return new ProjectPhaseContainer($phases);
    }
}
