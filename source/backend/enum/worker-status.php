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
}