<?php

class PhaseContainer
{
    private array $phases;

    public function __construct(array $phases = [])
    {
        foreach ($phases as $phase) {
            if (!$phase instanceof Phase) {
                throw new InvalidArgumentException('All elements of tasks array must be instances of Task.');
            }
            $this->addPhase($phase);
        }
    }

    public function addPhase(Phase $phase): void
    {
        $this->phases[] = $phase;
    }

    public function getPhases(): array
    {
        return $this->phases;
    }

    public function getPhasesCount(): int
    {
        return count($this->phases);
    }

    public function toArray(): array
    {
        return array_map(fn($phase) => $phase->toArray(), $this->phases);
    }

    public static function fromArray(array $data): PhaseContainer
    {
        $phases = array_map(fn($phaseData) => Phase::fromArray($phaseData), $data);
        return new PhaseContainer($phases);
    }
}
