<?php

enum Gender: string {
    case MALE = 'male';
    case FEMALE = 'female';

    public function getDisplayName(): string {
        return match($this) {
            Gender::MALE => ucwords(camelToSentenceCase(self::MALE->value)),
            Gender::FEMALE => ucwords(camelToSentenceCase(self::FEMALE->value)),
        };
    }
}