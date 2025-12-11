<?php

namespace App\Enumeration;

enum TaskPriority: string {
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    /**
     * Returns a human-readable display name for the TaskPriority enum.
     *
     * This method converts the enum's underlying camelCase value into a polished label:
     * - Converts the enum value from camelCase to a sentence with spaces using camelToSentenceCase()
     * - Capitalizes each word in the resulting sentence using ucwords()
     * - Handles each defined enum case explicitly: LOW, MEDIUM, HIGH
     *
     * @return string Human-friendly display name for this TaskPriority value
     * @see camelToSentenceCase()
     * @see ucwords()
     */
    public function getDisplayName(): string {
        return match ($this) {
            self::LOW => ucwords(camelToSentenceCase(SELF::LOW->value)),
            self::MEDIUM => ucwords(camelToSentenceCase(SELF::MEDIUM->value)),
            self::HIGH => ucwords(camelToSentenceCase(SELF::HIGH->value)),
        };
    }

    /**
     * Renders an HTML priority badge for a given TaskPriority.
     *
     * This method:
     * - Obtains the human-readable priority label via $priority->getDisplayName()
     * - Maps the TaskPriority to a background CSS class:
     *     - self::LOW    => 'green-bg'
     *     - self::MEDIUM => 'yellow-bg'
     *     - self::HIGH   => 'red-bg'
     * - Chooses the text color CSS class:
     *     - self::LOW, self::MEDIUM => 'black-text'
     *     - self::HIGH               => 'white-text'
     * - Returns a small HTML fragment (a div containing a p) with appropriate classes applied.
     *
     * Note: The returned string is an HTML snippet intended for direct rendering. Ensure the display name
     * produced by getDisplayName() is trusted or properly escaped before output if it can contain user-provided content.
     *
     * @param TaskPriority $priority The priority enum instance to render (expected values: LOW, MEDIUM, HIGH)
     *
     * @return string HTML fragment for the priority badge:
     *      <div class="priority-badge badge center-child {backgroundClass}">
     *          <p class="center-text {textColorClass}">{Priority Display Name}</p>
     *      </div>
     */
    public static function badge(TaskPriority $priority): string {
        $priorityName = $priority->getDisplayName();
        $backgroundColor = match ($priority) {
            self::LOW => 'green-bg',
            self::MEDIUM => 'yellow-bg',
            self::HIGH => 'red-bg',
        };
        $textColor = match ($priority) {
            self::LOW, self::MEDIUM => 'black-text',
            self::HIGH => 'white-text'
        };

        return <<<HTML
        <div class="priority-badge badge center-child $backgroundColor">
            <p class="center-text $textColor">$priorityName</p>
        </div>
        HTML;
    }
}