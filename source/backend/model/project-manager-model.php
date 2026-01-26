<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\PhaseContainer;
use App\Container\ProjectContainer;
use App\Container\TaskContainer;
use App\Core\UUID;
use App\Entity\Phase;
use App\Entity\ProjectManager;
use App\Entity\Project;
use App\Entity\Task;
use App\Enumeration\Gender;
use App\Enumeration\Role;
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
     * @return ProjectManager|null The ProjectManager instance representing the project manager, or null if not found.
     *
     * @throws DatabaseException If a database error occurs during the query execution.
     */
    public static function findById(
        int|UUID $managerId, 
        int|UUID|null $projectId = null, 
        bool $includeHistory = false
    ): ?ProjectManager {
        $instance = new self();
        try {
            // TODO: Separate project history fetching into its own method to avoid complex queries
            $projectHistory = $includeHistory
                ? "SELECT
                    JSON_ARRAYAGG(
                        JSON_OBJECT(
                            'id', p2.id,
                            'public_id', HEX(p2.public_id),
                            'name', p2.name,
                            'status', p2.status,
                            'start_date_time', p2.start_date_time,
                            'completion_date_time', p2.completion_date_time,
                            'actual_completion_date_time', p2.actual_completion_date_time,

                            'phases', COALESCE(
                                (
                                    SELECT JSON_ARRAYAGG(
                                        JSON_OBJECT(
                                            'id', ph2.id,
                                            'public_id', HEX(ph2.public_id),
                                            'name', ph2.name,
                                            'status', ph2.status,
                                            'start_date_time', ph2.start_date_time,
                                            'completion_date_time', ph2.completion_date_time,
                                            'actual_completion_date_time', ph2.actual_completion_date_time,

                                            'tasks', COALESCE(
                                                (
                                                    SELECT JSON_ARRAYAGG(
                                                        JSON_OBJECT(
                                                            'id', t2.id,
                                                            'public_id', HEX(t2.public_id),
                                                            'name', t2.name,
                                                            'status', t2.status,
                                                            'priority', t2.priority,
                                                            'start_date_time', t2.start_date_time,
                                                            'completion_date_time', t2.completion_date_time,
                                                            'actual_completion_date_time', t2.actual_completion_date_time
                                                        )
                                                    )
                                                    FROM 
                                                        `task` AS t2
                                                    WHERE 
                                                        t2.phase_id = ph2.id
                                                ),
                                                JSON_ARRAY()
                                            )
                                        )
                                    )
                                    FROM 
                                        `phase` AS ph2
                                    WHERE 
                                        ph2.project_id = p2.id
                                ),
                                JSON_ARRAY()
                            )
                        )
                    )
                FROM 
                    `project` AS p2
                WHERE 
                    p2.manager_id = u.id"
            : '';

            $whereClause = [];
            $params = [
                ':completedStatus' => WorkStatus::COMPLETED->value
            ];

            $whereClause[] = is_int($managerId)
                ? 'u.id = :managerId'
                : 'u.public_id = :managerId';

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

            $query = 
                "SELECT 
                    u.id,
                    u.public_id,
                    u.first_name,
                    u.middle_name,
                    u.last_name,
                    u.gender,
                    u.email,
                    u.birth_date,
                    u.contact_number,
                    u.bio,
                    u.profile_link,
                    u.created_at,
                    u.confirmed_at,
                    u.deleted_at,
                    GROUP_CONCAT(DISTINCT jt.title) AS job_titles,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `project` AS p2
                        WHERE 
                            p2.manager_id = u.id
                    ) AS total_projects,
                    (
                        SELECT 
                            COUNT(*)
                        FROM 
                            `project` AS p3
                        WHERE 
                            p3.manager_id = u.id 
                        AND 
                            p3.status = :completedStatus
                    ) AS completed_projects,
                    COALESCE (($projectHistory), JSON_ARRAY()) AS project_history
                FROM 
                    `user` AS u
                LEFT JOIN
                    `project` AS p
                ON 
                    p.manager_id = u.id
                LEFT JOIN
                    `job_title` AS jt
                ON 
                    jt.user_id = u.id
                WHERE 
                    $where
                GROUP BY
                    u.id
                LIMIT 1";
            
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetch();

            if (!$instance->hasData($result)) {
                return null;
            }

            $result['role'] = Role::PROJECT_MANAGER;
            $result['additionalInfo'] = [
                'totalProjects' => (int)$result['total_projects'],
                'completedProjects' => (int)$result['completed_projects'],
            ];
            $projectManager = ProjectManager::createPartial($result);

                        // Process project history if included
            if ($includeHistory) {
                $projectContainer = new ProjectContainer();
                $projects = json_decode($result['project_history'], true);

                // Process projects
                foreach ($projects as $project) {
                    $phaseContainer = new PhaseContainer();

                    // Process phases
                    $phases = $project['phases'];
                    foreach ($phases as $phase) {
                        $taskContainer = new TaskContainer();

                        // Process tasks
                        $tasks = $phase['tasks'];
                        foreach ($tasks as $task) {
                            $task['public_id'] = UUID::fromHex($task['public_id']);
                            $taskContainer->add(Task::createPartial($task));
                        }

                        $phase['public_id'] = UUID::fromHex($phase['public_id']);
                        $phase['tasks'] = $taskContainer;
                        $phaseContainer->add(Phase::createPartial($phase));
                    }

                    $project['public_id'] = UUID::fromHex($project['public_id']);
                    $project['phases'] = $phaseContainer;
                    $projectContainer->add(Project::createPartial($project));
                }
                
                $projectManager->addAdditionalInfo('projectHistory', $projectContainer);
            }

            return $projectManager;
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