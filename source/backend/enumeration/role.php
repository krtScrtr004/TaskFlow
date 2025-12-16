<?php

namespace App\Enumeration;

use App\Entity\User;

enum Role: string {
    case PROJECT_MANAGER = 'projectManager';
    case WORKER = 'worker';

    /**
     * Returns a human-readable display name for the current Role enum case.
     *
     * This method converts the enum case's camelCase value into sentence case
     * using camelToSentenceCase() and returns it for UI/display purposes.
     *
     * Mapped cases:
     *  - self::PROJECT_MANAGER => camelToSentenceCase(self::PROJECT_MANAGER->value)
     *  - self::WORKER => camelToSentenceCase(self::WORKER->value)
     *
     * Note: The implementation uses a match expression. If a new enum case is added
     * and not included in the match, a \UnhandledMatchError will be thrown.
     *
     * @return string Human-readable role name suitable for display (e.g. "Project manager", "Worker")
     * @throws \UnhandledMatchError If an enum case is not handled by the match expression
     */
    public function getDisplayName(): string {
        return match($this) {
            self::PROJECT_MANAGER => camelToSentenceCase(self::PROJECT_MANAGER->value),
            self::WORKER => camelToSentenceCase(self::WORKER->value)
        };
    }

    /**
     * Returns a human-readable description for this Role enum value.
     *
     * Maps enum cases to concise capability descriptions:
     * - self::PROJECT_MANAGER => "Can manage workers, projects, and tasks"
     * - self::WORKER => "Can work on assigned tasks and projects"
     *
     * @return string Short, human-readable description of the current role.
     */
    public function getDescription(): string {
        return match($this) {
            self::PROJECT_MANAGER => 'Can manage workers, projects, and tasks',
            self::WORKER => 'Can work on assigned tasks and projects'
        };
    }

    /**
     * Get the numeric level for this role.
     *
     * This method returns an integer representing the role's level which can be
     * used for permission checks, ordering by seniority, or feature gating.
     *
     * Role to level mapping:
     * - self::WORKER => 1
     * - self::PROJECT_MANAGER => 2
     *
     * @return int Numeric level associated with the role (higher value indicates greater privileges)
     */
    public function getLevel(): int {
        return match($this) {
            self::WORKER => 1,
            self::PROJECT_MANAGER => 2
        };
    }

    /**
     * Determines whether the provided user has the Project Manager role.
     *
     * This method checks the role value returned by the given User instance
     * against the PROJECT_MANAGER constant of this enumeration and returns
     * a boolean indicating the match.
     *
     * @param User $user User instance whose role will be evaluated. The role returned by getRole() may be a string or Role enum.
     *
     * @return bool True if the user's role equals self::PROJECT_MANAGER, false otherwise.
     */
    public static function isProjectManager(User $user): bool {
        return $user->getRole() === self::PROJECT_MANAGER;
    }

    /**
     * Determines whether the given User has the WORKER role.
     *
     * This method retrieves the role from the provided User instance via getRole()
     * and performs a strict comparison against this enumeration's WORKER constant.
     *
     * @param User $user The user to check.
     *
     * @return bool True if the user's role is equal to self::WORKER, false otherwise.
     */
    public static function isWorker(User $user): bool {
        return $user->getRole() === self::WORKER;
    }
}