<?php

namespace App\Container;

use App\Abstract\Container;
use InvalidArgumentException;

class JobTitleContainer extends Container
{
    public function __construct(array $jobTitles = [])
    {
        foreach ($jobTitles as $jobTitle) {
            if (!is_string($jobTitle)) {
                throw new InvalidArgumentException('All elements of jobTitles array must be instances of String.');
            }
            $this->add($jobTitle);
        }
    }

    public function add($jobTitle): void
    {
        if (!is_string($jobTitle)) {
            throw new InvalidArgumentException('Only String instances can be added to JobTitlesContainer.');
        }
        $this->items[] = $jobTitle;
    }

    public function remove($jobTitle): void
    {
        if (!is_string($jobTitle)) {
            throw new InvalidArgumentException('Only String instances can be removed from JobTitlesContainer.');
        }

        $index = array_search($jobTitle, $this->items, true);
        if ($index !== false) {
            array_splice($this->items, $index, 1);
        }
    }

    public static function fromArray(array $data): mixed
    {
        // TODO: Implement proper mapping if necessary
        return new JobTitleContainer([
            // Map the array elements to strings if necessary
            // For example, if $data contains associative arrays or objects
            // you might need to extract the string representation
            // Here we assume $data is already an array of strings
            ...$data
        ]);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
