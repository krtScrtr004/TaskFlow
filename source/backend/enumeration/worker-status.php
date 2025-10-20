<?php

namespace App\Enumeration;

enum WorkerStatus: string {
    case ASSIGNED = 'assigned';
    case UNASSIGNED = 'unassigned';
    case ON_LEAVE = 'onLeave';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

    public function getDisplayName(): string {
        return match($this) {
            self::ASSIGNED => ucwords(camelToSentenceCase(self::ASSIGNED->value)),
            self::UNASSIGNED => ucwords(camelToSentenceCase(self::UNASSIGNED->value)),
            self::ON_LEAVE => ucwords(camelToSentenceCase(self::ON_LEAVE->value)),
            self::SUSPENDED => ucwords(camelToSentenceCase(self::SUSPENDED->value)),
            self::TERMINATED => ucwords(camelToSentenceCase(self::TERMINATED->value))
        };
    }

    public static function badge(self $status): string {
        return match($status) {
            self::ASSIGNED => '<span class="worker-badge badge blue-bg white-text">Assigned</span>',
            self::UNASSIGNED => '<span class="worker-badge badge yellow-bg black-text">Unassigned</span>',
            self::ON_LEAVE => '<span class="worker-badge badge orange-bg white-text">On Leave</span>',
            self::SUSPENDED => '<span class="worker-badge badge red-bg white-text">Suspended</span>',
            self::TERMINATED => '<span class="worker-badge badge red-bg white-text">Terminated</span>'
        };
    }
}