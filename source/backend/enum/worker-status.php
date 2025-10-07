<?php

enum WorkerStatus: string {
    case ACTIVE = 'active';
    case ON_LEAVE = 'onLeave';
    case TERMINATED = 'terminated';

    public function getDisplayName(): string {
        return match($this) {
            self::ACTIVE => ucwords(camelToSentenceCase(self::ACTIVE->value)),
            self::ON_LEAVE => ucwords(camelToSentenceCase(self::ON_LEAVE->value)),
            self::TERMINATED => ucwords(camelToSentenceCase(self::TERMINATED->value))
        };
    }

    public static function badge(self $status): string {
        return match($status) {
            self::ACTIVE => '<span class="worker-badge badge red-bg white-text">Active</span>',
            self::ON_LEAVE => '<span class="worker-badge badge orange-bg white-text">On Leave</span>',
            self::TERMINATED => '<span class="worker-badge badge red-bg white-text">Terminated</span>'
        };
    }
}