<?php

namespace App\Service;

use App\Container\PhaseContainer;
use App\Container\WorkerContainer;
use App\Exception\DatabaseException;
use PDO;
use App\Core\Connection;
use App\Dependent\Phase;
use App\Dependent\Worker;
use App\Entity\Project;
use App\Model\PhaseModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use Throwable;

class ProjectService {
    private PDO $connection;

    private function __construct()
    {
        $this->connection = Connection::getInstance();
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
    public static function create(Project $project): Project
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $createProject = ProjectModel::create($project);
            $projectId = $createProject->getId();

            PhaseModel::createMultiple($projectId, $project->getPhases());
            ProjectWorkerModel::createMultiple($projectId, $project->getWorkers());

            $instance->connection->commit();
            return $createProject;
        } catch (Throwable $e) {
            $instance->connection->rollBack();
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
    public static function save(array $project): void
    {
        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            ProjectModel::save($project);
            $projectId = $project['id'];

            $addedPhases = $project['phases']['toAdd'] ?? [];
            $editedPhases = $project['phases']['toEdit'] ?? [];
            $cancelledPhases = $project['phases']['toCancel'] ?? [];

            if (count($addedPhases) > 0) {
                $phases = new PhaseContainer();
                foreach ($addedPhases as $phase) {
                    $phases->add(Phase::createPartial($phase));
                }
                PhaseModel::createMultiple($projectId, $phases);
            } 
            if (count($editedPhases) > 0 || count($cancelledPhases) > 0) {
                $phases = array_merge($editedPhases, $cancelledPhases);
                PhaseModel::saveMultiple($phases);
            }

            $addedWorkers = $project['workers']['toAdd'] ?? [];
            $editedWorkers = $project['workers']['toEdit'] ?? [];
            $removedWorkers = $project['workers']['toRemove'] ?? [];

            if (count($addedWorkers) > 0) {
                $workers = new WorkerContainer();
                foreach ($addedWorkers as $worker) {
                    $workers->add(Worker::createPartial($worker));
                }
                ProjectWorkerModel::createMultiple($projectId, $workers);
            } 
            if (count($editedWorkers) > 0 || count($removedWorkers) > 0) {
                $workers = array_merge($editedWorkers, $removedWorkers);
                ProjectWorkerModel::saveMultiple($projectId, $workers);
            }

            $instance->connection->commit();
        } catch (Throwable $e) {
            $instance->connection->rollBack();
            throw $e;
        }
    }
}