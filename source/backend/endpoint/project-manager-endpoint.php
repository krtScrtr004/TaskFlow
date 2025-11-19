<?php

namespace App\Endpoint;

use App\Auth\HttpAuth;
use App\Auth\SessionAuth;
use App\Core\UUID;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Middleware\Response;
use App\Model\ProjectManagerModel;
use App\Model\ProjectModel;
use App\Model\ProjectWorkerModel;
use App\Utility\ProjectManagerPerformanceCalculator;
use App\Utility\ResponseExceptionHandler;
use Throwable;

class ProjectManagerEndpoint
{
    /**
     * Retrieves a project manager by ID, optionally scoped to a specific project.
     *
     * This method performs the following validations and operations:
     * - Ensures the request is a valid GET request
     * - Verifies the user has an authorized session
     * - Requires at least one of managerId or projectId to be provided
     * - Validates and converts projectId to a UUID if provided
     * - Fetches the project if projectId is given
     * - Validates and converts managerId to a UUID if provided
     * - Retrieves the manager: uses provided managerId or the project's manager if not specified
     * - Calculates the manager's performance based on project history
     * - Adds the performance score to the manager's additional info
     * - Returns a success response with the manager data
     *
     * @param array $args Associative array containing request arguments with following keys:
     *      - managerId: string|UUID (optional) The manager's unique identifier
     *      - projectId: string|UUID (optional) The project's unique identifier
     * 
     * @return void Sends a JSON response with the manager data or handles exceptions
     */
    public static function getById(array $args = []): void
    {
        try {
            if (!HttpAuth::isGETRequest()) {
                throw new ForbiddenException('Invalid HTTP request method.');
            }

            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException();
            }

            if (!isset($args['managerId']) && !$args['projectId']) {
                throw new ForbiddenException('Manager ID and Project ID cannot both be missing.');
            }

            $projectId = isset($args['projectId'])
                ? UUID::fromString($args['projectId'])
                : null;
            if (isset($args['projectId']) && !$projectId) {
                throw new ForbiddenException('Project ID is required.');
            }

            $project = $projectId
                ? ProjectModel::findById($projectId)
                : null;
            if (!isset($args['projectId']) && !$project) {
                throw new NotFoundException('Project not found.');
            }

            $managerId = isset($args['managerId'])
                ? UUID::fromString($args['managerId'])
                : null;
            if (isset($args['managerId']) && !$managerId) {
                throw new ForbiddenException('Manager ID is required.');
            }

            $manager = $managerId
                ? ProjectManagerModel::findById($managerId,  $project->getId() ?? $projectId, true)
                : ProjectManagerModel::findById($project->getManager()->getId(),  $project->getId() ?? $projectId, true);
            if (!$manager) {
                throw new NotFoundException('Manager not found.');
            } 

            $performance = ProjectManagerPerformanceCalculator::calculate($manager->getAdditionalInfo('projectHistory') ?? []);
            $manager->addAdditionalInfo('performance', $performance['overallScore'] ?? 0.0);
            Response::success([$manager], 'Manager fetched successfully.');
        } catch (Throwable $e) {
            ResponseExceptionHandler::handle('Manager Fetch Failed.', $e);
        }
    }
}