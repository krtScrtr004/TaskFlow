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

    /**
     * Creates a new JobTitleContainer instance from an array of data.
     *
     * This static factory method provides a convenient way to create a JobTitleContainer
     * object from an associative array of data, typically used when deserializing
     * from JSON or database results.
     *
     * @param array $data Array of strings containing job title data
     * 
     * @return mixed A new JobTitleContainer instance created from the provided data
     */
    public static function fromArray(array $data): mixed
    {
        return new JobTitleContainer($data);
    }
}
