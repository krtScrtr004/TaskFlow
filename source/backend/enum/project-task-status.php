<?php

enum ProjectTaskStatus: string {
    case PENDING = 'pending';
    case ON_GOING = 'onGoing';
    case COMPLETED = 'completed';
    case DELAYED = 'delayed';
    case CANCELLED = 'cancelled';

    public function getDisplayName(): string {
        return match($this) {
            self::PENDING => ucwords(camelToSentenceCase(self::PENDING->value)),
            self::ON_GOING => ucwords(camelToSentenceCase(self::ON_GOING->value)),
            self::COMPLETED => ucwords(camelToSentenceCase(self::COMPLETED->value)),
            self::DELAYED => ucwords(camelToSentenceCase(self::DELAYED->value)),
            self::CANCELLED => ucwords(camelToSentenceCase(self::CANCELLED->value))
        };
    }

    public static function getStatusFromDates(DateTime $startDate, DateTime $endDate): ProjectTaskStatus {
        $now = new DateTime();

        if ($now < $startDate) {
            return self::PENDING;
        } elseif ($now >= $startDate && $now <= $endDate) {
            return self::ON_GOING;
        } elseif ($now > $endDate) {
            return self::COMPLETED;
        } else {
            throw new Exception("Unable to determine status from given dates.");
        }
    }

    public static function fromString(string $value): ProjectTaskStatus {
        return self::from($value); // This throws ValueError if invalid
    }
}