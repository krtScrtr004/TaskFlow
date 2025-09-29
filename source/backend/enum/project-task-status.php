<?php

enum ProjectTaskStatus: string {
    case ON_GOING = 'onGoing';
    case COMPLETED = 'completed';
    case DELAYED = 'delayed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string {
        return match($this) {
            self::ON_GOING => ucwords(camelToSentenceCase(self::ON_GOING->value)),
            self::COMPLETED => ucwords(camelToSentenceCase(self::COMPLETED->value)),
            self::DELAYED => ucwords(camelToSentenceCase(self::DELAYED->value)),
            self::CANCELLED => ucwords(camelToSentenceCase(self::CANCELLED->value))
        };
    }

    public static function fromString(string $value): ProjectTaskStatus {
        return self::from($value); // This throws ValueError if invalid
    }
}