<?php

namespace App\Enumeration;

enum Role: string {
    case PROJECT_MANAGER = 'projectManager';

    // Worker roles
    case WORKER = 'worker';
    case TASK_WORKER = 'taskWorker';

    /**
     * Returns a human-readable display name for the current Role enum case.
     *
     * This method converts the enum case's camelCase value into sentence case
     * using camelToSentenceCase() and returns it for UI/display purposes.
     *
     * Mapped cases:
     *  - self::PROJECT_MANAGER => camelToSentenceCase(self::PROJECT_MANAGER->value)
     *  - self::WORKER => camelToSentenceCase(self::WORKER->value)
     *  - self::TASK_WORKER => camelToSentenceCase(self::TASK_WORKER->value)
     *
     * Note: The implementation uses a match expression. If a new enum case is added
     * and not included in the match, a \UnhandledMatchError will be thrown.
     *
     * @return string Human-readable role name suitable for display (e.g. "Project manager", "Worker", "Task worker")
     * @throws \UnhandledMatchError If an enum case is not handled by the match expression
     */
    public function getDisplayName(): string {
        return match($this) {
            self::PROJECT_MANAGER   => camelToSentenceCase(self::PROJECT_MANAGER->value),
            self::WORKER            => camelToSentenceCase(self::WORKER->value),   
            self::TASK_WORKER       => camelToSentenceCase(self::TASK_WORKER->value)
        };
    }

    /**
     * Returns a human-readable description for this Role enum value.
     *
     * Maps enum cases to concise capability descriptions:
     * - self::PROJECT_MANAGER => "Can manage workers, projects, and tasks"
     * - self::WORKER => "Can work on assigned tasks and projects"
     * - self::TASK_WORKER => "Can work on assigned tasks and projects"
     *
     * @return string Short, human-readable description of the current role.
     */
    public function getDescription(): string {
        return match($this) {
            self::PROJECT_MANAGER   => 'Can manage workers, projects, and tasks',
            self::WORKER            => 'Can work on assigned tasks and projects',
            self::TASK_WORKER       => 'Can work on assigned tasks and projects'
        };
    }

    /**
     * Determines whether the provided user has the Project Manager role.
     *
     * This method checks the role value returned by the given User instance
     * against the PROJECT_MANAGER constant of this enumeration and returns
     * a boolean indicating the match.
     *
     * @param Role $role Role enum value to be evaluated.
     *
     * @return bool True if the user's role equals self::PROJECT_MANAGER, false otherwise.
     */
    public static function isProjectManager(self $role): bool {
        return $role === self::PROJECT_MANAGER;
    }

    /**
     * Determines whether the given User has the WORKER role.
     *
     * This method retrieves the role from the provided User instance via getRole()
     * and performs a strict comparison against this enumeration's WORKER constant.
     *
     * @param Role $role Role enum value to be evaluated.
     *
     * @return bool True if the user's role is equal to self::WORKER, false otherwise.
     */
    public static function isWorker(self $role): bool {
        return $role === self::WORKER;
    }

    /**
     * Determines whether the given User has the TASK_WORKER role.
     *
     * This method retrieves the role from the provided User instance via getRole()
     * and performs a strict comparison against this enumeration's TASK_WORKER constant.
     *
     * @param Role $role Role enum value to be evaluated.
     *
     * @return bool True if the user's role is equal to self::TASK_WORKER, false otherwise.
     */    
    public static function isTaskWorker(self $role): bool {
        return $role === self::TASK_WORKER;
    }   
}