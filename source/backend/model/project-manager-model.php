<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\JobTitleContainer;
use App\Container\PhaseContainer;
use App\Container\ProjectContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Dependent\Phase;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Enumeration\TaskPriority;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use DateTime;
use PDOException;

class ProjectManagerModel extends Model
{

    
    /**
     * Finds project manager records matching a WHERE clause.
     *
     * Builds and executes a query to retrieve project manager rows that satisfy the
     * provided SQL WHERE clause and bound parameters. The $options array can be used
     * to influence query execution and result hydration (for example limit, offset,
     * orderBy, fetch mode, etc).
     *
     * Note: This method is currently not implemented and will return null (no use case).
     *
     * @param string $whereClause SQL WHERE clause fragment (without the "WHERE" keyword). Defaults to an empty string for no filtering.
     * @param array $params Positional or named parameters to bind to the query.
     * @param array $options Execution and result options. Common keys may include:
     *      - limit: int Maximum number of rows to return
     *      - offset: int Row offset for pagination
     *      - orderBy: string ORDER BY clause fragment (without the "ORDER BY" keyword)
     *      - fetch: string How to fetch results ("all"|"one")
     *      - hydrate: string Result hydration mode ("array"|"object")
     *      - cache: bool Whether to use query caching
     *
     * @return array|null Array of result rows (each row as an associative array) or null if no results or the method is not implemented
     */
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?array
    {
        // Not implemented (No use case)
        return null;
    }

    /**
     * Finds a project manager by their ID, optionally filtering by a specific project and including project history.
     *
     * This method retrieves a User instance representing a project manager from the database. It can optionally
     * filter the results to ensure the manager is associated with a specific project. If $includeHistory is true,
     * it includes a detailed history of all projects managed by the user, including their phases and tasks.
     * The returned User object includes additional information such as total projects and completed projects.
     * If project history is included, it is added as a ProjectContainer in the additionalInfo array.
     *
     * @param int|UUID $managerId The ID of the project manager to find (integer or UUID object).
     * @param int|UUID|null $projectId Optional project ID to filter the manager by (integer, UUID object, or null).
     * @param bool $includeHistory Whether to include the project history with phases and tasks (default: false).
     *
     * @return User|null The User instance representing the project manager, or null if not found.
     *
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public static function findById(int|UUID $managerId, int|UUID|null $projectId = null, bool $includeHistory = false): ?User
    {
        $instance = new self();
        try {

            $projectHistory = $includeHistory
            ? ", COALESCE(
                    (
                        SELECT CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT(
                                'projectId', p2.id,
                                'projectPublicId', HEX(p2.publicId),
                                'projectName', p2.name,
                                'projectStatus', p2.status,
                                'projectStartDate', p2.startDateTime,
                                'projectCompletionDate', p2.completionDateTime,
                                'projectActualCompletionDate', p2.actualCompletionDateTime,
                                'projectPhases', COALESCE(
                                    (
                                        SELECT CONCAT('[', GROUP_CONCAT(
                                            JSON_OBJECT(
                                                'phaseId', pp2.id,
                                                'phasePublicId', HEX(pp2.publicId),
                                                'phaseName', pp2.name,
                                                'phaseStatus', pp2.status,
                                                'phaseStartDate', pp2.startDateTime,
                                                'phaseCompletionDate', pp2.completionDateTime,
                                                'phaseActualCompletionDate', pp2.actualCompletionDateTime,
                                                'phaseTasks', COALESCE(
                                                    (
                                                        SELECT CONCAT('[', GROUP_CONCAT(
                                                            JSON_OBJECT(
                                                                'taskId', pt2.id,
                                                                'taskPublicId', HEX(pt2.publicId),
                                                                'taskName', pt2.name,
                                                                'taskStatus', pt2.status,
                                                                'taskPriority', pt2.priority,
                                                                'taskStartDate', pt2.startDateTime,
                                                                'taskCompletionDate', pt2.completionDateTime,
                                                                'taskActualCompletionDate', pt2.actualCompletionDateTime
                                                            ) ORDER BY pt2.startDateTime ASC SEPARATOR ','
                                                        ), ']')
                                                        FROM 
                                                            `phaseTask` AS pt2
                                                        WHERE 
                                                            pt2.phaseId = pp2.id
                                                    ), 
                                                    '[]'
                                                )
                                            ) ORDER BY pp2.startDateTime ASC SEPARATOR ','
                                        ), ']')
                                        FROM 
                                            `projectPhase` AS pp2
                                        WHERE 
                                            pp2.projectId = p2.id
                                    ), 
                                    '[]'
                                )
                            ) ORDER BY p2.startDateTime ASC SEPARATOR ','
                        ), ']')
                        FROM 
                            `project` AS p2
                        WHERE 
                            p2.managerId = u.id
                    ), 
                    '[]'
                ) AS 'projectHistory'"
            : '';

            $whereClause = [];
            $params = [
                ':completedStatus' => WorkStatus::COMPLETED->value
            ];

            $whereClause[] = is_int($managerId)
                ? 'u.id = :managerId'
                : 'u.publicId = :managerId';

            $params[':managerId'] = is_int($managerId)
                ? $managerId
                : UUID::toBinary($managerId);

            $whereClause[] = 'u.role = :projectManagerRole';
            $params[':projectManagerRole'] = Role::PROJECT_MANAGER->value;

            if ($projectId) {
                $whereClause[] = 'p.id = :projectId';
                $params[':projectId'] = is_int($projectId)
                    ? $projectId
                    : UUID::toBinary($projectId);
            }
            $where = implode(' AND ', $whereClause);

            $query = "
                SELECT 
                    u.id,
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.gender,
                    u.email,
                    u.birthDate,
                    u.contactNumber,
                    u.bio,
                    u.profileLink,
                    u.createdAt,
                    u.confirmedAt,
                    u.deletedAt,
                    GROUP_CONCAT(ujt.title) AS jobTitles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `project` AS p2
                        WHERE 
                            p2.managerId = u.id
                    ) AS totalProjects,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `project` AS p3
                        WHERE 
                            p3.managerId = u.id AND p3.status = :completedStatus
                    ) AS completedProjects
                    $projectHistory
                FROM 
                    `user` AS u
                LEFT JOIN
                    `project` AS p
                ON 
                    p.managerId = u.id
                LEFT JOIN
                    `userJobTitle` AS ujt
                ON 
                    ujt.userId = u.id
                WHERE 
                    $where
                GROUP BY 
                    u.id
            ";
            
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            $user = new User(
                id: (int)$result['id'],
                publicId: UUID::fromBinary($result['publicId']),
                firstName: $result['firstName'],
                middleName: $result['middleName'],
                lastName: $result['lastName'],
                role: Role::PROJECT_MANAGER,
                gender: Gender::from($result['gender']),
                birthDate: new DateTime($result['birthDate']),
                contactNumber: $result['contactNumber'],
                email: $result['email'],
                bio: $result['bio'],
                profileLink: $result['profileLink'],
                jobTitles: new JobTitleContainer(explode(',', $result['jobTitles'])),
                createdAt: new DateTime($result['createdAt']),
                confirmedAt: new DateTime($result['confirmedAt']),
                deletedAt: new DateTime($result['deletedAt']),
                additionalInfo: [
                    'totalProjects' => (int)$result['totalProjects'],
                    'completedProjects' => (int)$result['completedProjects'],
                ]
            );

                        // Process project history if included
            if ($includeHistory) {
                $projectContainer = new ProjectContainer();
                $projects = json_decode($result['projectHistory'], true);

                // Process projects
                foreach ($projects as $project) {
                    $phaseContainer = new PhaseContainer();

                    // Process phases
                    $phases = json_decode($project['projectPhases'], true);
                    foreach ($phases as $phase) {
                        $taskContainer = new TaskContainer();

                        // Process tasks
                        $tasks = json_decode($phase['phaseTasks'], true);
                        foreach ($tasks as $task) {
                            $taskContainer->add(Task::createPartial([
                                'id' => (int)$task['taskId'],
                                'publicId' => UUID::fromHex($task['taskPublicId']),
                                'name' => $task['taskName'],
                                'status' => WorkStatus::from($task['taskStatus']),
                                'priority' => TaskPriority::from($task['taskPriority']),
                                'startDateTime' => new DateTime($task['taskStartDate']),
                                'completionDateTime' => new DateTime($task['taskCompletionDate']),
                                'actualCompletionDateTime' => new DateTime($task['taskActualCompletionDate']),
                            ]));
                        }

                        $phaseContainer->add(Phase::createPartial([
                            'id' => (int)$phase['phaseId'],
                            'publicId' => UUID::fromHex($phase['phasePublicId']),
                            'name' => $phase['phaseName'],
                            'status' => WorkStatus::from($phase['phaseStatus']),
                            'tasks' => $taskContainer,
                            'startDateTime' => new DateTime($phase['phaseStartDate']),
                            'completionDateTime' => new DateTime($phase['phaseCompletionDate']),
                            'actualCompletionDateTime' => new DateTime($phase['phaseActualCompletionDate']),
                        ]));
                    }

                    $projectContainer->add(Project::createPartial([
                        'id' => (int)$project['projectId'],
                        'publicId' => UUID::fromHex($project['projectPublicId']),
                        'name' => $project['projectName'],
                        'status' => WorkStatus::from($project['projectStatus']),
                        'phases' => $phaseContainer,
                        'startDateTime' => new DateTime($project['projectStartDate']),
                        'completionDateTime' => new DateTime($project['projectCompletionDate']),
                        'actualCompletionDateTime' => new DateTime($project['projectActualCompletionDate']),
                    ]));
                }
                
                $user->addAdditionalInfo('projectHistory', $projectContainer);
            }

            return $user;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Retrieves all project managers with pagination support.
     *
     * This method is intended to fetch a list of all project managers from the data source,
     * applying offset and limit for pagination. Currently not implemented as there is no use case.
     *
     * @param int $offset The starting point for the results (default: 0)
     * @param int $limit The maximum number of results to return (default: 10)
     * 
     * @return array|null An array of project manager data or null if not implemented
     */
    public static function all(int $offset = 0, int $limit = 10): ?array
    {
        // Not implemented (No use case)
        return null;
    }

    /**
     * Creates a new project manager record.
     *
     * This method is intended to create a new project manager in the data source.
     * Currently not implemented as there is no use case.
     *
     * @param mixed $data The data required to create a new project manager
     * 
     * @return mixed|null The created project manager data or null if not implemented
     */
    public static function create(mixed $data): mixed
    {
        // Not implemented (No use case)
        return null;
    }

    /**
     * Deletes a project manager record.
     *
     * This method is intended to delete a project manager from the data source.
     * Currently not implemented as there is no use case.
     *
     * @param mixed $data The identifier or data required to delete the project manager
     * 
     * @return bool False indicating deletion is not implemented
     */
    protected static function delete(mixed $data): bool
    {
        // Not implemented (No use case)
        return false;
    }

    /**
     * Saves updates to a project manager record.
     *
     * This method is intended to save changes to an existing project manager in the data source.
     * Currently not implemented as there is no use case.
     *
     * @param array $data The data to update the project manager
     * 
     * @return bool False indicating save is not implemented
     */
    public static function save(array $data): bool
    {
        // Not implemented (No use case)
        return false;
    }
}