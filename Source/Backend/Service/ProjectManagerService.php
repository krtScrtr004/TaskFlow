<?php

namespace App\Service;

use App\Container\ProjectContainer;
use App\Core\UUID;
use App\Entity\ProjectManager;
use App\Model\ProjectManagerModel;
use App\Model\ProjectModel;
use InvalidArgumentException;

class ProjectManagerService 
{
    private ProjectManagerModel $projectManagerModel;
    private ProjectModel $projectModel;

    public function __construct() {
        $this->projectManagerModel = new ProjectManagerModel();
        $this->projectModel = new ProjectModel();
    }

    public function get(
        int|UUID|array $projectManagerId,
        array $options = [
            'projectId'             => null,

            'projectHistory'        => false,
            'projectHistoryOptions' => [
                'phases' => false,
                'tasks'  => false,
                'limit'  => 10,
                'offset' => 0,
            ],
        ]
    ): ProjectManager|array|null {
        $isBatch = \is_array($projectManagerId);
        $projectManagerIds = $isBatch ? array_values($projectManagerId) : [$projectManagerId];
        if (empty($projectManagerIds))
            throw new InvalidArgumentException('At least one project manager ID must be provided');

        $firstIsInt = \is_int($projectManagerIds[0]);
        foreach ($projectManagerIds as $item) {
            if (!\is_int($item) && !($item instanceof UUID))
                throw new InvalidArgumentException('Project manager ID must be an integer or UUID');

            if ($firstIsInt !== \is_int($item))
                throw new InvalidArgumentException('Project manager IDs must be of the same type (all int or all UUID)');

            if (\is_int($item) && $item < 1)
                throw new InvalidArgumentException('Invalid project manager ID provided');
        }

        $projectId = $options['projectId'] ?? null;
        if ($projectId) {
            if (!\is_int($projectId) && !($projectId instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');

            if (\is_int($projectId) && $projectId < 1)
                throw new InvalidArgumentException('Invalid project ID provided');
        }

        $projectManagers = [];
        foreach ($projectManagerIds as $managerId) {
            $manager = $this->projectManagerModel->findById($managerId, $projectId);
            if (!$manager) return null;
            $projectManagers[] = $manager;
        }

        $includeHistory = (bool) ($options['projectHistory'] ?? false);
        if ($includeHistory) {
            $historyOptions = $options['projectHistoryOptions'] ?? [];
            if (!\is_array($historyOptions))
                throw new InvalidArgumentException('Project history options must be an array');

            $historyIncludePhases = (bool) ($historyOptions['phases'] ?? false);
            $historyIncludeTasks = (bool) ($historyOptions['tasks'] ?? false);
            $historyLimit = (int) ($historyOptions['limit'] ?? 10);
            $historyOffset = (int) ($historyOptions['offset'] ?? 0);

            $internalIds = [];
            foreach ($projectManagers as $manager) {
                if (!$manager instanceof ProjectManager) continue;
                $internalIds[] = $manager->getId();
            }

            $projectHistory = $this->projectModel->findManagerHistory(
                $internalIds,
                [
                    'phases' => $historyIncludePhases,
                    'tasks'  => $historyIncludeTasks,
                    'limit'  => $historyLimit,
                    'offset' => $historyOffset,
                ]
            );

            $historyByManagerPublicId = [];
            if ($projectHistory) {
                foreach ($projectHistory as $project) {
                    $userId = $project->getAdditionalInfo('userId');
                    if (!$userId instanceof UUID) continue;

                    $key = UUID::toString($userId);
                    if (!isset($historyByManagerPublicId[$key]))
                        $historyByManagerPublicId[$key] = new ProjectContainer();

                    $historyByManagerPublicId[$key]->add($project);
                }
            }

            foreach ($projectManagers as $manager) {
                if (!$manager instanceof ProjectManager) continue;
                $publicId = $manager->getPublicId();
                $key = $publicId ? UUID::toString($publicId) : null;

                $manager->addAdditionalInfo(
                    'projectHistory',
                    ($key && isset($historyByManagerPublicId[$key]))
                        ? $historyByManagerPublicId[$key]
                        : null
                );
            }
        }

        return $isBatch ? $projectManagers : $projectManagers[0];
    }
}