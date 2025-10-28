<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\ProjectContainer;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\WorkerStatus;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use Exception;
use InvalidArgumentException;
use PDOException;

class ProjectWorkerModel extends Model
{
    protected static function find(string $whereClause = '', array $params = [], array $options = []): ?WorkerContainer
	{
		$instance = new self();
        try {
            $queryString = "
                SELECT 
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.bio,
                    u.gender,
                    u.email,
                    u.contactNumber,
                    u.profileLink,
                    pw.status,
                    GROUP_CONCAT(ujt.title) AS jobTitles,
                    (
                        SELECT COUNT(*) 
                        FROM projectWorker AS pw2 
                        WHERE pw2.workerId = u.id
                    ) AS totalProjects,
                    (
                        SELECT COUNT(*) 
                        FROM projectWorker AS pw3
                        INNER JOIN project AS p2 ON pw3.projectId = p2.id
                        WHERE pw3.workerId = u.id AND p2.status = '" . WorkStatus::COMPLETED->value . "'
                    ) AS completedProjects
                FROM
                    `user` AS u
                INNER JOIN
                    `projectWorker` AS pw 
                ON 
                    u.id = pw.workerId
                INNER JOIN
                    `project` AS p
                ON
                    pw.projectId = p.id
                LEFT JOIN
                    `userJobTitle` AS ujt
                ON 
                    u.id = ujt.userId
            ";
            $query = $instance->appendOptionsToFindQuery(
                $instance->appendWhereClause($queryString, $whereClause), 
                $options);

            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (!$instance->hasData($result)) {
                return null;
            }

            $workers = new WorkerContainer();
            foreach ($result as $row) {
                $row['jobTitles'] = explode(',', $row['jobTitles']);
                $row['additionalInfo'] = [
                    'totalProjects'      => (int)$row['totalProjects'],
                    'completedProjects'  => (int)$row['completedProjects'],
                ];
                $workers->add(Worker::fromArray($row));
            }
            return $workers;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage());
        }
	}

    /**
     * Searches for workers associated with a specific project using a full-text search key.
     *
     * This method performs a full-text search on user fields (first name, middle name, last name, bio, email)
     * for workers assigned to the given project. It returns a WorkerContainer of matching workers, including
     * their job titles, project status, and additional statistics.
     *
     * @param int|UUID $projectId The project identifier (integer or UUID) to filter workers by project.
     * @param string $key The search key used for full-text search against user fields.
     * @param array $options (optional) Search options:
     *      - limit: int (default 10) Maximum number of results to return.
     *      - offset: int (default 0) Number of results to skip (for pagination).
     *
     * @throws InvalidArgumentException If the project ID is invalid or the search key is empty.
     * @throws DatabaseException If a database error occurs during the search.
     *
     * @return WorkerContainer|null A container of Worker objects matching the search, or null if no results found.
     */
    public static function search(
        int|UUID $projectId, 
        string $key,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]): ?WorkerContainer
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        if (trimOrNull($key) === null) {
            throw new InvalidArgumentException('Search key cannot be empty.');
        }

        $params = [];
            $params[':key'] = $key;
            $params[':projectId'] = ($projectId instanceof UUID)
                ? UUID::toBinary($projectId)
                : $projectId;

        try {
            $result = self::find("MATCH(u.firstName, u.middleName, u.lastName, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)
                    AND " . (is_int($projectId) ? "p.id" : "p.id") . " = :projectId",
                $params, 
                [
                    'limit'     => $options['limit'] ?? 10,
                    'offset'    => $options['offset'] ?? 0,
                    'groupBy'   => 'u.id'
                ]);
            return $result ? $result->getItems() ?? null : null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds a Worker associated with a specific Project by worker and project IDs.
     *
     * This method retrieves a Worker instance that is linked to the given project,
     * supporting both integer and UUID identifiers for project and worker. It also
     * allows for pagination through the options parameter.
     *
     * @param int|UUID $projectId The project identifier (integer or UUID).
     * @param int|UUID $workerId The worker identifier (integer or UUID).
     * @param array $options Optional query options:
     *      - limit: int (default 10) Maximum number of results to return.
     *      - offset: int (default 0) Number of results to skip.
     * 
     * @throws InvalidArgumentException If an invalid project ID is provided.
     * @throws Exception If an error occurs during the query.
     * 
     * @return Worker|null The Worker instance if found, or null if not found.
     */
    public static function findByWorkerId(
        int|UUID $projectId, 
        int|UUID $workerId,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]): ?Worker
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $params = [];
        $params[':projectId'] = ($projectId instanceof UUID) 
            ? UUID::toBinary($workerId)
            : $workerId;
        $params[':workerId'] = ($workerId instanceof UUID) 
            ? UUID::toBinary($workerId)
            : $workerId;

        try {
            $result = self::find((is_int($workerId) ? "u.id" : "u.publicId") . " = :workerId 
                        AND " . (is_int($projectId) ? "p.id" : "p.publicId") . " = :projectId",
                $params, 
                [
                    'limit'     => $options['limit'] ?? 10,
                    'offset'    => $options['offset'] ?? 0,
                    'groupBy'   => 'u.id'
                ]);
            return $result->getItems() ?? null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Finds and retrieves all workers assigned to a specific project, including their job titles and project statistics.
     *
     * This method queries the database to fetch all users who are assigned as workers to the specified project by joining
     * the user, projectWorker, and project tables. It also LEFT JOINs userJobTitle to aggregate job titles for each worker.
     *
     * For each worker, the following additional statistics are included:
     *   - totalProjects: The total number of projects the worker is assigned to (across all projects)
     *   - completedProjects: The number of projects the worker is assigned to that have status 'completed'
     *
     * The method supports both integer and UUID project IDs. The returned WorkerContainer contains Worker objects with
     * job titles and additionalInfo fields populated.
     *
     * @param int|UUID $projectId The project ID (int) or public UUID (UUID) to find workers for
     * @param array $options Optional settings:
     *      - limit: int (default: 10) Maximum number of workers to return
     *      - offset: int (default: 0) Number of workers to skip
     * @return WorkerContainer|null Container with Worker objects if workers are found, null if no workers are associated
     * @throws InvalidArgumentException If projectId is invalid
     * @throws DatabaseException If a database error occurs during the query execution
     *
     * SQL Details:
     * - Joins user, projectWorker, project, and userJobTitle tables
     * - Uses subqueries to count total and completed projects for each worker
     * - GROUP_CONCAT is used to aggregate job titles
     * - GROUP BY u.id ensures one row per worker
     */
    public static function findByProjectId(
        int|UUID $projectId, 
        array $options = [
            'limit' => 10,
            'offset' => 0
        ]): ?WorkerContainer
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        $params = [];
        $params[':id'] = ($projectId instanceof UUID) 
            ? UUID::toBinary($projectId)
            : $projectId;

        try {
            return self::find((is_int($projectId) ? "p.id" : "p.publicId ") . " = :id", 
                $params, 
                [
                    'limit'     => $options['limit'] ?? 10,
                    'offset'    => $options['offset'] ?? 0,
                    'groupBy'   => 'u.id'
                ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Retrieves a paginated list of all workers.
     *
     * This method fetches a collection of workers from the data source, supporting pagination
     * through the use of offset and limit parameters. The results are ordered by creation date
     * in descending order.
     *
     * @param int $offset The number of records to skip before starting to collect the result set. Must be zero or positive.
     * @param int $limit The maximum number of records to return. Must be at least 1.
     *
     * @throws InvalidArgumentException If the offset is negative or the limit is less than 1.
     * @throws Exception If an error occurs during data retrieval.
     *
     * @return WorkerContainer|null A container with the retrieved workers, or null if no workers are found.
     */
    public static function all(int $offset = 0, int $limit = 10): ?WorkerContainer
    {
        if ($offset < 0) {
            throw new InvalidArgumentException('Invalid offset value.');
        }

        if ($limit < 1) {
            throw new InvalidArgumentException('Invalid limit value.');
        }

        try {
            return self::find('', [], [
                'offset' => $offset,
                'limit' => $limit,
                'orderBy' => 'u.createdAt DESC',
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function createMultiple(int|UUID $projectId, array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('No data provided.');
        }

        $projectId = ($projectId instanceof UUID)
            ? UUID::toBinary($projectId)
            : $projectId;

        $instance = new self();
        try {
            $instance->connection->beginTransaction();

            $insertQuery = "
                INSERT INTO `projectWorker` (
                    projectId, 
                    workerId, 
                    status
                ) VALUES (
                    (
                        SELECT id 
                        FROM `project` 
                        WHERE publicId = :projectId
                    ),
                    (
                        SELECT id 
                        FROM `user` 
                        WHERE publicId = :workerId
                    ),
                    :status
                )";
            $statement = $instance->connection->prepare($insertQuery);
            foreach ($data as $id) {    
                $statement->execute([
                    ':projectId'    => $projectId,
                    ':workerId'     => ($id instanceof UUID)
                        ? UUID::toBinary($id)
                        : $id,
                    ':status'       => WorkerStatus::ASSIGNED->value
                ]);
            }

            $instance->connection->commit();
        } catch (PDOException $e) {
            $instance->connection->rollBack();
            throw new DatabaseException($e->getMessage());
        }
    }




	public static function create(mixed $data): mixed
	{
		// TODO: Implement method logic
		return null;
	}

	protected function delete(): bool
	{
		// TODO: Implement method logic
		return false;
	}



	public static function save(array $data): bool
	{
		// TODO: Implement method logic
		return false;
	}
}
