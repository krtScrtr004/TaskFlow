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

    public static function fromString(string $value): WorkStatus
    {
        return self::from($value); // This throws ValueError if invalid
    }
}
