<?php

namespace App\Enumeration;

enum Gender: string {
    case MALE = 'male';
    case FEMALE = 'female';

    /**
     * Get a human-readable display name for the current Gender enum value.
     *
     * This method converts the enum's underlying camelCase string value into
     * a sentence-like label and capitalizes each word. It currently handles:
     * - Gender::MALE  => "Male"
     * - Gender::FEMALE => "Female"
     *
     * Conversion steps:
     * - camelToSentenceCase(self::VALUE->value) — turns camelCase into words separated by spaces
     * - ucwords(...) — capitalizes the first letter of each resulting word
     *
     * @return string Human-readable gender label
     */
    public function getDisplayName(): string {
        return match($this) {
            Gender::MALE => ucwords(camelToSentenceCase(self::MALE->value)),
            Gender::FEMALE => ucwords(camelToSentenceCase(self::FEMALE->value)),
        };
    }
}