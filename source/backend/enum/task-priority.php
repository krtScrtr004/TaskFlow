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

    public static function badge(TaskPriority $priority): string {
        $priorityName = $priority->getDisplayName();
        $backgroundColor = match ($priority) {
            self::LOW => 'green-bg',
            self::MEDIUM => 'yellow-bg',
            self::HIGH => 'red-bg',
        };
        $textColor = match ($priority) {
            self::MEDIUM => 'black-text',
            self::LOW, self::HIGH => 'white-text'
        };

        return <<<HTML
        <div class="priority-badge badge center-child $backgroundColor">
            <p class="center-text $textColor">$priorityName</p>
        </div>
        HTML;
    }
}