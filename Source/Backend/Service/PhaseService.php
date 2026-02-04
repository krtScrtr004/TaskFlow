<?php

namespace App\Service;

use App\Container\PhaseContainer;
use App\Core\UUID;
use App\Entity\Phase;
use App\Model\PhaseModel;
use App\Model\TaskModel;

use App\Container\TaskContainer;
use InvalidArgumentException;

class PhaseService
{
    private PhaseModel $phaseModel;
    private TaskModel $taskModel;

    private function __construct()
    {
        $this->phaseModel = new PhaseModel();
        $this->taskModel = new TaskModel();
    }

    public static function get(
        int|UUID $phaseId,
        array $options = [
            'tasks' => true,
        ]
    ): Phase|null {
        $instance = new self();

        $phase = $instance->phaseModel->findById($phaseId);
        if (!$phase) return null;

        $phaseId = $phase->getId();

        $includeTasks = $options['tasks'] ?? true;
        if ($includeTasks) {
            $tasks = $instance->taskModel->findByPhaseId($phaseId);
            $phase->setTasks($tasks);
        }

        return $phase;
    }

    /**
     * Retrieves phases for a project with optional tasks attached.
     *
     * This method efficiently loads phases and their associated tasks in two queries
     * instead of using a subquery or N+1 queries, improving performance.
     *
     * @param int|UUID $projectId The project identifier
     * @param bool $includeTasks Whether to load tasks for each phase
     * @param array $options Query options (limit, offset)
     * 
     * @return PhaseContainer|null Container with phases, or null if none found
     * 
     * @throws InvalidArgumentException If project ID is invalid
     */
    public static function getByProjectId(
        int|UUID $projectId,
        array $options = ['tasks' => false]
    ): PhaseContainer|null {
        $instance = new self();

        // Fetch phases without tasks
        $phases = $instance->phaseModel->findByProjectId($projectId, $options);
        
        if (!$phases || $phases->count() === 0) return null;

        // If tasks are needed, fetch all tasks for these phases in one query
        $includeTasks = $options['tasks'] ?? false;
        if ($includeTasks) {
            $phaseIds = [];
            foreach ($phases as $phase) {
                $phaseIds[] = $phase->getId();
            }

            $allTasks = $instance->taskModel->findByPhaseIds($phaseIds, ['limit' => null]);

            if ($allTasks && $allTasks->count() > 0) {
                // Group tasks by phase_id
                $tasksByPhaseId = [];
                foreach ($allTasks as $task) {
                    $phaseId = $task->getAdditionalInfo('phaseId') ?? null;
                    if ($phaseId) {
                        // Find matching phase object to get internal ID
                        foreach ($phases as $phase) {
                            if (UUID::toString($phase->getPublicId()) === UUID::toString($phaseId)) {
                                $internalPhaseId = $phase->getId();

                                // Add task to the corresponding phase's task container
                                if (!isset($tasksByPhaseId[$internalPhaseId])) 
                                    $tasksByPhaseId[$internalPhaseId] = new TaskContainer();
                                $tasksByPhaseId[$internalPhaseId]->add($task);
                                break;
                            }
                        }
                    }
                }

                // Attach tasks to their respective phases
                foreach ($phases as $phase) {
                    $phaseId = $phase->getId();
                    if (isset($tasksByPhaseId[$phaseId]))
                        $phase->setTasks($tasksByPhaseId[$phaseId]);
                }
            }
        }

        return $phases;
    }
}