<?php

namespace App\Enumeration;

enum WorkerStatus: string {
    case UNASSIGNED = 'unassigned'; // This is only for workers who have been created but not yet assigned to any project
    case ASSIGNED = 'assigned';
    case TERMINATED = 'terminated';

    /**
     * Returns a human-friendly display name for the worker status enum.
     *
     * This method converts the enum's underlying value (expected to be in camelCase)
     * into a readable sentence and then formats it into title case:
     * - Uses camelToSentenceCase() to convert camelCase to a sentence (words separated by spaces)
     * - Applies ucwords() to convert the sentence to Title Case
     *
     * The method currently maps the following enum cases:
     * - ASSIGNED
     * - UNASSIGNED
     * - TERMINATED
     *
     * @return string Human-readable display name for the current enum case (Title Case)
     *
     * @throws \UnhandledMatchError If the enum gains new cases that are not handled by the match expression
     */
    public function getDisplayName(): string {
        return match($this) {
            self::ASSIGNED => ucwords(camelToSentenceCase(self::ASSIGNED->value)),
            self::UNASSIGNED => ucwords(camelToSentenceCase(self::UNASSIGNED->value)),
            self::TERMINATED => ucwords(camelToSentenceCase(self::TERMINATED->value))
        };
    }

    /**
     * Returns an HTML badge for the given worker status.
     *
     * Generates a small HTML <span> element used as a status badge. The method
     * maps enum values to presentation strings and CSS classes:
     * - self::ASSIGNED   => '<span class="worker-badge badge blue-bg white-text">Assigned</span>'
     * - self::UNASSIGNED => '<span class="worker-badge badge yellow-bg black-text">Unassigned</span>'
     * - self::TERMINATED => '<span class="worker-badge badge red-bg white-text">Terminated</span>'
     *
     * The produced markup includes base classes ("worker-badge", "badge") and
     * color-specific classes for background and text (e.g. "blue-bg", "white-text").
     * Consumers should ensure the corresponding CSS is available to style the badges.
     *
     * @param self $status Worker status enum value (one of ASSIGNED, UNASSIGNED, TERMINATED)
     * @return string HTML <span> element representing the status badge
     */
    public static function badge(self $status): string {
        return match($status) {
            self::ASSIGNED => '<span class="worker-badge badge blue-bg white-text">Assigned</span>',
            self::UNASSIGNED => '<span class="worker-badge badge yellow-bg black-text">Unassigned</span>',
            self::TERMINATED => '<span class="worker-badge badge red-bg white-text">Terminated</span>'
        };
    }
}