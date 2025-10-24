<?php

namespace App\Enumeration;

use App\Entity\User;

enum Role: string {
    case PROJECT_MANAGER = 'projectManager';
    case WORKER = 'worker';

    public function getDisplayName(): string {
        return match($this) {
            self::PROJECT_MANAGER => camelToSentenceCase(self::PROJECT_MANAGER->value),
            self::WORKER => camelToSentenceCase(self::WORKER->value)
        };
    }

    public function getDescription(): string {
        return match($this) {
            self::PROJECT_MANAGER => 'Can manage workers, projects, and tasks',
            self::WORKER => 'Can work on assigned tasks and projects'
        };
    }

    public function getLevel(): int {
        return match($this) {
            self::WORKER => 1,
            self::PROJECT_MANAGER => 2
        };
    }

    /**
     * Check if this role has permission to perform actions requiring another role
     */
    public function hasPermission(Role $requiredRole): bool {
        return $this->getLevel() >= $requiredRole->getLevel();
    }

    public function hasHigherPermission(Role $compareRole): bool {
        return $this->getLevel() > $compareRole->getLevel();
    }

    public static function fromString(string $value): Role {
        return self::from($value); // This throws ValueError if invalid
    }

    public static function tryFromString(string $value): ?Role {
        return self::tryFrom($value);
    }

    public static function isValid(string $value): bool {
        return self::tryFrom($value) !== null;
    }

    public static function isProjectManager(User $user): bool {
        return $user->getRole() === self::PROJECT_MANAGER;
    }

    public static function isWorker(User $user): bool {
        return $user->getRole() === self::WORKER;
    }
}