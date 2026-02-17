<?php

namespace App\Service;

use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Entity\Worker;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use InvalidArgumentException;

class ProjectWorkerService
{
    private ProjectModel $projectModel;
    private ProjectWorkerModel $projectWorkerModel;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectWorkerModel = new ProjectWorkerModel();
    }

    public function get(
        int|UUID|array $workerId,
        array $options = [
            'projectId'             => null,

            'projectHistory'        => false,
            'projectHistoryOptions' => [
                'phases' => false,
                'tasks'  => false,
                'limit'         => 10,
                'offset'        => 0,
            ],
        ]
    ): Worker|WorkerContainer|null {
        $isBatch = \is_array($workerId);
        $workerIds = $isBatch ? array_values($workerId) : [$workerId];

        if (empty($workerIds))
            throw new InvalidArgumentException('At least one worker ID must be provided');

        $firstIsInt = \is_int($workerIds[0]);
        foreach ($workerIds as $item) {
            if (!\is_int($item) && !($item instanceof UUID))
                throw new InvalidArgumentException('Worker ID must be an integer or UUID');

            if ($firstIsInt !== \is_int($item))
                throw new InvalidArgumentException('Worker IDs must be of the same type (all int or all UUID)');

            if (\is_int($item) && $item < 1)
                throw new InvalidArgumentException('Invalid worker ID provided');
        }

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException("Project ID must be an integer or UUID");

            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException("Invalid project ID provided");
        }

        // Fetch workers
        $workers = $this->projectWorkerModel->findById($workerIds, [
            'projectId' => $projectId
        ]);
        if (!$workers) return null;

        $includeHistory = (bool) ($options['projectHistory'] ?? false);
        if ($includeHistory) {
            $historyOptions = $options['projectHistoryOptions'] ?? [];
            if (!\is_array($historyOptions)) throw new InvalidArgumentException("Project history options must be an array");

            $historyIncludePhases = (bool) ($historyOptions['phases'] ?? false);
            $historyIncludeTasks = (bool) ($historyOptions['tasks'] ?? false);
            $historyLimit = (int) ($historyOptions['limit'] ?? 10);
            $historyOffset = (int) ($historyOptions['offset'] ?? 0);

            $internalIds = [];
            foreach ($workers as $worker) {
                if (!$worker instanceof Worker) continue;
                $internalIds[] = $worker->getId();
            }

            // Fetch project history for these workers
            $projectHistory = $this->projectModel->findWorkerHistory(
                $internalIds,
                [
                    'phases'    => $historyIncludePhases,
                    'tasks'     => $historyIncludeTasks,
                    'limit'     => $historyLimit,
                    'offset'    => $historyOffset,
                ]
            );

            $historyByWorkerPublicId = [];
            if ($projectHistory) {
                foreach ($projectHistory as $project) {
                    $userId = $project->getAdditionalInfo('userId');
                    if (!$userId instanceof UUID) continue;

                    $key = UUID::toString($userId);
                    if (!isset($historyByWorkerPublicId[$key]))
                        $historyByWorkerPublicId[$key] = new ProjectContainer();

                    $historyByWorkerPublicId[$key]->add($project);
                }
            }

            // Attach history to each worker
            foreach ($workers as $worker) {
                if (!$worker instanceof Worker) continue;
                $publicId = $worker->getPublicId();
                $key = $publicId ? UUID::toString($publicId) : null;

                $worker->addAdditionalInfo(
                    'projectHistory',
                    ($key && isset($historyByWorkerPublicId[$key]))
                        ? $historyByWorkerPublicId[$key]
                        : null
                );
            }
        }

        return $isBatch ? $workers : $workers->first();
    }
}
