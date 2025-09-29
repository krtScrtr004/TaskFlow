<?php

/**
 * Role enum for user role management
 */
enum Role: string {
    case PROJECT_MANAGER = 'projectManager';
    case WORKER = 'worker';

    /**
     * Get the display name for the role
     */
    public function getDisplayName(): string {
        return match($this) {
            self::PROJECT_MANAGER => camelToSentenceCase(self::PROJECT_MANAGER->value),
            self::WORKER => camelToSentenceCase(self::WORKER->value)
        };
    }

    /**
     * Get the description for the role
     */
    public function getDescription(): string {
        return match($this) {
            self::PROJECT_MANAGER => 'Can manage workers, projects, and tasks',
            self::WORKER => 'Can work on assigned tasks and projects'
        };
    }

    /**
     * Get the hierarchy level (higher number = more permissions)
     */
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

    /**
     * Check if this role has higher permissions than another role
     */
    public function hasHigherPermission(Role $compareRole): bool {
        return $this->getLevel() > $compareRole->getLevel();
    }

    /**
     * Create role from string value with validation
     */
    public static function fromString(string $value): Role {
        return self::from($value); // This throws ValueError if invalid
    }

    /**
     * Try to create role from string, return null if invalid
     */
    public static function tryFromString(string $value): ?Role {
        return self::tryFrom($value);
    }

    /**
     * Check if a string represents a valid role
     */
    public static function isValid(string $value): bool {
        return self::tryFrom($value) !== null;
    }

    /**
     * Convert role to array format (useful for JSON serialization)
     */
    public function toArray(): array {
        return [
            'value' => $this->value,
            'displayName' => $this->getDisplayName(),
            'description' => $this->getDescription(),
            'level' => $this->getLevel()
        ];
    }
}