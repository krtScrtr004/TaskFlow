<?php

enum ProjectTaskStatus: string
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

    public static function getStatusFromDates(DateTime $startDate, DateTime $completionDate): ProjectTaskStatus
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

    public function badge(): string
    {
        $statusName = $this->getDisplayName();
        $backgroundColor = match ($this) {
            self::PENDING => 'yellow-bg',
            self::ON_GOING => 'green-bg',
            self::COMPLETED => 'blue-bg',
            self::DELAYED => 'orange-bg',
            self::CANCELLED => 'red-bg'
        };
        $textColor = match ($this) {
            self::PENDING, self::DELAYED => 'black-text',
            self::ON_GOING, self::COMPLETED, self::CANCELLED => 'white-text'
        };

        ob_start();
?>
        <div class="status-badge <?= $backgroundColor . ' ' . $textColor ?>">
            <?= $statusName ?>
        </div>
<?php
        return ob_get_clean();
    }

    public static function fromString(string $value): ProjectTaskStatus
    {
        return self::from($value); // This throws ValueError if invalid
    }
}
