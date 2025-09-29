<?php

enum TaskPriority: string {
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public function getDisplayName(): string {
        return match ($this) {
            self::LOW => ucwords(camelToSentenceCase(SELF::LOW->value)),
            self::MEDIUM => ucwords(camelToSentenceCase(SELF::MEDIUM->value)),
            self::HIGH => ucwords(camelToSentenceCase(SELF::HIGH->value)),
        };
    }
}