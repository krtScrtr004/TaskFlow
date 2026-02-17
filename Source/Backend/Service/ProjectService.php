<?php

namespace App\Service;

use App\Container\PhaseContainer;
use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Model\TaskModel;
use PDO;
use App\Core\Connection;
use App\Core\UUID;
use App\Entity\Phase;
use App\Entity\Worker;
use App\Entity\Project;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use InvalidArgumentException;
use Throwable;

class ProjectService
{
    private PDO $connection;

    private ProjectModel $projectModel;
    private ProjectWorkerModel $projectWorkerModel;
    private PhaseModel $phaseModel;

    public function __construct()
    {
        $this->connection = Connection::getInstance();

        $this->projectModel = new ProjectModel();
        $this->projectWorkerModel = new ProjectWorkerModel();
        $this->phaseModel = new PhaseModel();
    }

    /**
     * Creates a new project and persists its phases and workers within a database transaction.
     *
     * This method begins a transaction, delegates project creation to ProjectModel::create,
     * then persists associated phases and workers via PhaseModel::createMultiple and
     * ProjectWorkerModel::createMultiple. If all operations succeed the transaction is committed
     * and the created Project (with its assigned ID) is returned. On any failure the transaction
     * is rolled back and the original exception/error is re-thrown.
     *
     * @param Project $project Project entity containing data to persist along with phases and workers
     * @return Project The persisted Project instance (including assigned ID)
     * @throws \Throwable Re-throws any exception or error encountered during creation
     */
    public function create(
        Project|ProjectContainer $project,
        array $options = [
            'phases'    => false,
            'workers'   => false
        ]
    ): Project {
        $isBatch = $project instanceof ProjectContainer;
        $projects = $isBatch ? $project : new ProjectContainer([$project]);

        $executePhases = $options['phases'] ?? false;
        $executeWorkers = $options['workers'] ?? false;

        try {
            $this->connection->beginTransaction();

            foreach ($projects as $item) {
                $createProject = $this->projectModel->create($item);
                $projectId = $createProject->getId();

                if ($executePhases) {
                    $phases = $item->getPhases();
                    if ($phases && $phases->count() > 0)
                        $this->phaseModel->create($projectId, $item->getPhases());
                }

                if ($executeWorkers) {
                    $workers = $item->getWorkers();
                    if ($workers && $workers->count() > 0)
                        $this->projectWorkerModel->create($projectId, $item->getWorkers());
                }
            }

            $this->connection->commit();
            return $createProject;
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Saves a project and its related phases and workers within a single database transaction.
     *
     * This method:
     *  - Persists the project using ProjectModel::save().
     *  - Creates new phases found in $project['phases']['toAdd'] by converting each item
     *    with Phase::createPartial() and calling PhaseModel::createMultiple().
     *  - Updates or cancels phases found in $project['phases']['toEdit'] and
     *    $project['phases']['toCancel'] via PhaseModel::saveMultiple().
     *  - Creates new project-worker relations from $project['workers']['toAdd'] by
     *    converting each item with Worker::createPartial() and calling
     *    ProjectWorkerModel::createMultiple().
     *  - Updates or removes project-worker relations found in
     *    $project['workers']['toEdit'] and $project['workers']['toRemove'] via
     *    ProjectWorkerModel::saveMultiple().
     *
     * The entire operation is transactional: it begins a transaction, commits on success,
     * and rolls back if any Throwable is thrown (which is then re-thrown).
     *
     * @param array $project Associative array describing the project. Expected structure:
     *      - id: int Project identifier (should be set/updated by ProjectModel::save())
     *      - phases: array|null Optional associative array with keys:
     *          - toAdd: array List of phase data to create
     *          - toEdit: array List of phase data to update
     *          - toCancel: array List of phase data to cancel
     *      - workers: array|null Optional associative array with keys:
     *          - toAdd: array List of worker data to add to the project
     *          - toEdit: array List of worker data to update
     *          - toRemove: array List of worker data to remove from the project
     *
     * @return void
     * @throws Throwable If any error occurs; transaction will be rolled back before re-throwing.
     */
    public function save(array $project): void
    {
        $isBatch = array_keys($project) === range(0, count($project) - 1);
        $projects = $isBatch ? $project : [$project];

        try {
            $this->connection->beginTransaction();

            foreach ($projects as $item) {
                $this->projectModel->save($item);
                $projectId = $item['id'];

                $addedPhases = $item['phases']['toAdd'] ?? [];
                $editedPhases = $item['phases']['toEdit'] ?? [];
                $cancelledPhases = $item['phases']['toCancel'] ?? [];

                if (\count($addedPhases) > 0) {
                    $phases = new PhaseContainer();
                    foreach ($addedPhases as $phase) {
                        $phases->add(Phase::createPartial($phase));
                    }
                    $this->phaseModel->create($projectId, $phases);
                }
                if (\count($editedPhases) > 0 || \count($cancelledPhases) > 0) {
                    $phases = array_merge($editedPhases, $cancelledPhases);
                    $this->phaseModel->save($phases);
                }

                $addedWorkers = $item['workers']['toAdd'] ?? [];
                $editedWorkers = $item['workers']['toEdit'] ?? [];
                $removedWorkers = $item['workers']['toRemove'] ?? [];

                if (\count($addedWorkers) > 0) {
                    $workers = new WorkerContainer();
                    foreach ($addedWorkers as $worker) {
                        $workers->add(Worker::createPartial($worker));
                    }
                    $this->projectWorkerModel->create($projectId, $workers);
                }
                if (\count($editedWorkers) > 0 || \count($removedWorkers) > 0) {
                    $workers = array_merge($editedWorkers, $removedWorkers);
                    $this->projectWorkerModel->save($projectId, $workers);
                }
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function get(
        int|UUID|array $projectId,
        array $options = [
            'phases'    => false,
            'tasks'     => false,
            'workers'   => false
        ]
    ): Project|null {
        $isBatch = \is_array($projectId);
        $projectIds = $isBatch ? array_values($projectId) : [$projectId];

        if (\is_int($projectId) && $projectId <= 0)
            throw new InvalidArgumentException('Invalid project ID');

        $includePhases = $options['phases'] ?? false;
        $includeTasks = $options['tasks'] ?? false;
        $includeWorkers = $options['workers'] ?? false;

        $projects = new ProjectContainer();
        foreach ($projectIds as $item) {
            if (!\is_int($item) && !($item instanceof UUID))
                throw new InvalidArgumentException('Project ID must be an integer or UUID');

            if (\is_int($item) && $item < 1)
                throw new InvalidArgumentException('Invalid project ID provided');

            $project = $this->projectModel->findById($item);
            if (!$project) return null;

            $projectId = $project->getId();

            if ($includePhases) {
                $phases = $this->phaseModel->findByProjectId($projectId);
                $project->setPhases($phases);
            }

            if ($includeWorkers) {
                $workers = $this->projectWorkerModel->findByProjectId($projectId);
                $project->setWorkers($workers);
            }

            if ($includeTasks) {
                $taskModel = new TaskModel();
                $tasks = $taskModel->findByProjectId($projectId);
                $project->setTasks($tasks);
            }

            $projects->add($project);
        }

        return $isBatch ? $projects : $projects->first();
    }
}
