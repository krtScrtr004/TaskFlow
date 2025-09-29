<?php

enum ProjectTaskStatus: string {
    case ON_GOING = 'on_going';
    case COMPLETED = 'completed';
    case DELAYED = 'delayed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string {
        return match($this) {
            self::ON_GOING => ucwords(kebabToSentenceCase(self::ON_GOING->value)),
            self::COMPLETED => ucwords(kebabToSentenceCase(self::COMPLETED->value)),
            self::DELAYED => ucwords(kebabToSentenceCase(self::DELAYED->value)),
            self::CANCELLED => ucwords(kebabToSentenceCase(self::CANCELLED->value))
        };
    }

    public static function fromString(string $value): ProjectTaskStatus {
        return self::from($value); // This throws ValueError if invalid
    }
}