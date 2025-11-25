<?php

namespace App\Enumeration;

use DateTime;
use Exception;

enum WorkStatus: string
{
    case PENDING = 'pending';
    case ON_GOING = 'onGoing';
    case COMPLETED = 'completed';
    case DELAYED = 'delayed';
    case CANCELLED = 'cancelled';

    /**
     * Returns a human-readable display name for the work status enum.
     *
     * This method converts the enum's underlying value (expected to be in
     * camelCase or a similar compact form) into a user-friendly label by:
     * - converting camelCase to a sentence-like string via camelToSentenceCase()
     * - capitalizing each word via ucwords()
     *
     * Typical output mapping for the enum cases:
     * - self::PENDING   => "Pending"
     * - self::ON_GOING  => "On Going"
     * - self::COMPLETED => "Completed"
     * - self::DELAYED   => "Delayed"
     * - self::CANCELLED => "Cancelled"
     *
     * @return string Human-readable display name for the current enum value.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::PENDING => ucwords(camelToSentenceCase(self::PENDING->value)),
            self::ON_GOING => ucwords(camelToSentenceCase(self::ON_GOING->value)),
            self::COMPLETED => ucwords(camelToSentenceCase(self::COMPLETED->value)),
            self::DELAYED => ucwords(camelToSentenceCase(self::DELAYED->value)),
            self::CANCELLED => ucwords(camelToSentenceCase(self::CANCELLED->value))
        };
    }

    /**
     * Determines the WorkStatus based on the provided start and completion dates.
     *
     * This method compares the current time (created via new DateTime()) against the given dates
     * and returns one of the WorkStatus enum values according to these rules:
     * - If now < $startDate: returns self::PENDING
     * - If now >= $startDate and now <= $completionDate: returns self::ON_GOING
     * - If now > $completionDate: returns self::COMPLETED
     *
     * Notes:
     * - Current time is obtained with new DateTime(), so comparisons use the system default timezone
     *   unless timezone-aware DateTime objects are provided. For consistent results, provide
     *   timezone-aware DateTime instances or normalize timezones before calling.
     * - An Exception is thrown if the status cannot be determined (this branch is intended as a guard
     *   and should normally be unreachable).
     *
     * @param DateTime $startDate      Start date/time of the work period
     * @param DateTime $completionDate Expected completion/end date/time of the work period
     *
     * @return WorkStatus Returns one of: self::PENDING, self::ON_GOING, self::COMPLETED
     *
     * @throws Exception If the status cannot be determined from the provided dates
     */
    public static function getStatusFromDates(DateTime $startDate, DateTime $completionDate): WorkStatus
    {
        $now = new DateTime();

        if ($now < $startDate) {
            return self::PENDING;
        } elseif ($now >= $startDate && $now <= $completionDate) {
            return self::ON_GOING;
        } elseif ($now > $completionDate) {
            return self::COMPLETED;
        } else {
            throw new Exception("Unable to determine status from given dates.");
        }
    }

    /**
     * Generates an HTML status badge for a given WorkStatus enum value.
     *
     * This method builds a small HTML fragment representing the work status:
     * - Retrieves the display name from the provided WorkStatus instance
     * - Maps the WorkStatus to a background CSS class:
     *      - self::PENDING => 'yellow-bg'
     *      - self::ON_GOING => 'green-bg'
     *      - self::COMPLETED => 'blue-bg'
     *      - self::DELAYED => 'orange-bg'
     *      - self::CANCELLED => 'red-bg'
     * - Determines the text color CSS class:
     *      - self::ON_GOING, self::PENDING => 'black-text'
     *      - self::COMPLETED, self::DELAYED, self::CANCELLED => 'white-text'
     * - Returns an HTML snippet containing a wrapper div and a paragraph with the status name,
     *   using the resolved background and text color classes.
     *
     * @param WorkStatus $status The WorkStatus enum instance to render as a badge
     *
     * @return string HTML fragment for the status badge
     */
    public static function badge(WorkStatus $status): string
    {
        $statusName = $status->getDisplayName();
        $backgroundColor = match ($status) {
            self::PENDING => 'yellow-bg',
            self::ON_GOING => 'green-bg',
            self::COMPLETED => 'blue-bg',
            self::DELAYED => 'orange-bg',
            self::CANCELLED => 'red-bg'
        };
        $textColor = match ($status) {
            self::ON_GOING, self::PENDING => 'black-text',
            self::COMPLETED, self::DELAYED, self::CANCELLED => 'white-text'
        };

        return <<<HTML
        <div class="status-badge badge center-child $backgroundColor">
            <p class="center-text $textColor">$statusName</p>
        </div>
        HTML;
    }

    /**
     * Creates a WorkStatus enum instance from a string value.
     *
     * This method converts a string representation of a work status into the corresponding
     * WorkStatus backed enum instance by delegating to the enum's native from() factory.
     * The provided value must match one of the enum's backed values exactly (case-sensitive).
     *
     * Example:
     * - 'active' => WorkStatus::from('active') returns the WorkStatus::active instance
     *
     * @param string $value String representation of the work status to convert.
     *
     * @return WorkStatus The corresponding WorkStatus enum instance.
     *
     * @throws ValueError If the provided value does not correspond to any WorkStatus backed value.
     *
     * @see WorkStatus::from()
     */
    public static function fromString(string $value): WorkStatus
    {
        return self::from($value); // This throws ValueError if invalid
    }
}
