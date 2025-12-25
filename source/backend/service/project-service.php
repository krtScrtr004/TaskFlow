<?php

namespace App\Service;

use App\Exception\DatabaseException;
use PDO;
use App\Core\Connection;
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
}