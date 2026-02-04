<?php

namespace App\Service;

use App\Core\UUID;
use App\Entity\Worker;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use InvalidArgumentException;

class ProjectWorkerService
{
    private ProjectModel $projectModel;
    private ProjectWorkerModel $projectWorkerModel;

    private function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectWorkerModel = new ProjectWorkerModel();
    }

    public static function get(
        int|UUID $workerId,
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
    ): Worker|null {
        if (!\is_int($workerId) && $workerId < 1)
            throw new InvalidArgumentException("Invalid worker ID provided");

        $instance = new self();

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException("Project ID must be an integer or UUID");

            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException("Invalid project ID provided");
        }

        $worker = $instance->projectWorkerModel->findById($workerId, ['projectId' => $projectId]);
        if (!$worker) return null;

        $workerId = $worker->getId();

        $includeHistory = (bool) ($options['projectHistory'] ?? false);
        if ($includeHistory) {
            $historyOptions = $options['projectHistoryOptions'] ?? [];
            if (!\is_array($historyOptions)) throw new InvalidArgumentException("Project history options must be an array");

            $historyIncludePhases = (bool) ($historyOptions['phases'] ?? false);
            $historyIncludeTasks = (bool) ($historyOptions['tasks'] ?? false);
            $historyLimit = (int) ($historyOptions['limit'] ?? 10);
            $historyOffset = (int) ($historyOptions['offset'] ?? 0);

            $projectHistory = $instance->projectModel->findWorkerHistory(
                $workerId,
                [
                    'phases'    => $historyIncludePhases,
                    'tasks'     => $historyIncludeTasks,
                    'limit'     => $historyLimit,
                    'offset'    => $historyOffset,
                ]
            );
            $worker->addAdditionalInfo('projectHistory', $projectHistory);
        }
        return $worker;
    }
}
