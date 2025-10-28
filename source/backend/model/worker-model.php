<?php

namespace App\Model;

use App\Abstract\Model;
use App\Container\WorkerContainer;
use App\Core\UUID;
use App\Dependent\Worker;
use App\Enumeration\WorkStatus;
use App\Exception\DatabaseException;
use InvalidArgumentException;
use PDOException;

class WorkerModel extends Model
{
    public static function searchProjectWorker(
        int|UUID $projectId, 
        string $key,
        array $options = [
            'limit' => 10,
            'offset' => 0,
        ]): mixed
    {
        if (is_int($projectId) && $projectId < 1) {
            throw new InvalidArgumentException('Invalid project ID provided.');
        }

        if (trimOrNull($key) === null) {
            throw new InvalidArgumentException('Search key cannot be empty.');
        }

        $instance = new self();
        try {
            $params = [];
            $params[':key'] = $key;
            $params[':projectId'] = ($projectId instanceof UUID)
                ? UUID::toBinary($projectId)
                : $projectId;

            $query = "
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
                LEFT JOIN
                    `userJobTitle` AS ujt
                ON 
                    u.id = ujt.userId
                WHERE
                    MATCH(u.firstName, u.middleName, u.lastName, u.bio, u.email) 
                    AGAINST (:key IN NATURAL LANGUAGE MODE)
                    AND pw.projectId = :projectId
                GROUP BY u.id
                LIMIT " . (int)$options['limit'] ?? 10 . "
                OFFSET " . (int)$options['offset'] ?? 0 . "
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (empty($result)) {
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
    public static function findProjectWorkersByProjectId(
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

        $instance = new self();
        try {
            $query = "
                SELECT 
                    u.id,
                    u.publicId,
                    u.firstName,
                    u.middleName,
                    u.lastName,
                    u.gender,
                    u.email,
                    u.contactNumber,
                    u.profileLink,
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
                    `projectWorker` AS pw ON u.id = pw.workerId
                INNER JOIN
                    `project` AS p ON pw.projectId = p.id
                LEFT JOIN
                    `userJobTitle` AS ujt ON u.id = ujt.userId
                WHERE 
                    " . (is_int($projectId) ? "p.id" : "p.publicId ") . " = :id
                GROUP BY u.id
                LIMIT " . (int)$options['limit'] ?? 10 . "
                OFFSET " . (int)$options['offset'] ?? 0 . "
            ";
            $statement = $instance->connection->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();

            if (empty($result)) {
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









	public static function all(int $offset = 0, int $limit = 10): mixed
	{
		// TODO: Implement method logic
		return [];
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

	protected static function find(string $whereClause = '', array $params = [], array $options = []): mixed
	{
		// TODO: Implement method logic
		return null;
	}

	public static function save(array $data): bool
	{
		// TODO: Implement method logic
		return false;
	}
}
