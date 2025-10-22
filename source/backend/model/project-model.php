<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\TaskContainer;
use App\Model\UserModel;
use App\Model\TaskModel;
use App\Core\UUID;
use App\Enumeration\WorkStatus;
use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\Task;
use App\Dependent\Worker;
use App\Enumeration\Role;
use App\Core\Me;
use App\Core\Connection;
use App\Exception\ValidationException;
use App\Exception\DatabaseException;
use App\Validator\UuidValidator;
use InvalidArgumentException;
use DateTime;
use PDOException;

class ProjectModel extends Model
{
    public function findByPublicId(UUID $publicId): ?Project 
    {
        $uuidValidator = new UuidValidator();
        $uuidValidator->validateUuid($publicId);
        if ($uuidValidator->hasErrors()) {
            throw new ValidationException(
                'Invalid Project ID',
                $uuidValidator->getErrors()
            );
        }
        
        $binaryUuid = UUID::toBinary($publicId);
        try {

            $query = "
                SELECT
                    p.id AS projectId,
                    p.publicId AS projectPublicId,
                    p.name AS projectName,
                    p.description AS projectDescription,
                    p.budget AS projectBudget,
                    p.startDateTime AS projectStartDateTime,
                    p.completionDateTime AS projectCompletionDateTime,
                    p.actualCompletionDateTime AS projectActualCompletionDateTime,
                    p.status AS projectStatus,
                    p.createdAt AS projectCreatedAt,
                    u.id AS managerId,
                    u.publicId AS managerPublicId,
                    u.firstName AS managerFirstName,
                    u.middleName AS managerMiddleName,
                    u.lastName AS managerLastName,
                    u.profileLink AS managerProfileLink
                FROM 
                    `project` AS P
                INNER JOIN
                    `user` AS u
                ON
                    p.managerId = u.id
                WHERE 
                    publicId = :publicId
                LIMIT 1
            ";
            $statement = $this->connection->prepare($query);
            $statement->execute([':publicId' => $binaryUuid]);
            $result = $statement->fetch();

            if (empty($result)) {
                return null;
            }

            return Project::fromArray($result);
        } catch (PDOException $th) {
            throw new DatabaseException();
        }
    }


    /**
     * Finds a project where a specific worker is assigned to
     * 
     * This method searches for a project that has the specified worker assigned and 
     * is either in 'pending' or 'onGoing' status.
     *
     * @param int $workerId The ID of the worker to search for
     * @return Project|null Returns a Project object if found, or null if no matching project exists
     * @throws InvalidArgumentException If the worker ID is less than 1
     * @throws DatabaseException If a database error occurs during the query
     */
    public static function findWorkerByWorkerId(int $workerId): ?Project
    {
        if ($workerId < 1) {
            throw new InvalidArgumentException('Invalid worker ID provided.');
        }

        try {
            $query = "
                SELECT 
                    p.publicId
                FROM 
                    `project` as p
                INNER JOIN
                    `projectWorker` as pw
                ON
                    p.id = pw.projectId
                WHERE 
                    pw.workerId = :workerId AND
                    (p.status = 'pending' OR
                    p.status = 'onGoing')
                LIMIT 1
            ";

            $statement = Connection::getInstance()->prepare($query);
            $statement->execute([':workerId' => $workerId]);
            $result = $statement->fetch();

            return $result ? Project::fromArray($result) : null;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }











    public function save(): bool
    {
        return true;
    }

    public function delete(): bool
    {
        return true;
    }

    public static function create(mixed $data): void
    {
        if (!($data instanceof self)) {
            throw new InvalidArgumentException('Expected instance of ProjectModel');
        }
    }

    public static function all(): ProjectContainer
    {
        $workers = new WorkerContainer();
        $users = UserModel::all();
        foreach ($users as $user) {
            if (Role::isWorker($user)) {
            $workers->add(Worker::fromUser($user));
            }
        }

        $tasks = TaskModel::all();
        $phases = PhaseModel::all();

        $projects = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $start = new DateTime('2023-01-01 12:00:00');
            $end = new DateTime('2023-12-31 23:59:59');
            $completed = new DateTime('2023-11-30 18:30:00');
            $status = WorkStatus::getStatusFromDates($start, $end);
            
            $projects[] = new Project(
            random_int(1, 1000),
            uniqid(),
            'New Project ' . $i,
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ac ex nec nunc gravida tincidunt. Donec euismod, nisl eget consectetur sagittis, nisl nunc.',
            Me::getInstance(),
            10000000,
            $tasks,
            $workers,
            $phases,
            $start,
            $end,
            $completed,
            $status,
            new DateTime()
            );
        }
        
        return new ProjectContainer($projects);
    }

    public static function find($id): ?self
    {
        return null;
    }

}