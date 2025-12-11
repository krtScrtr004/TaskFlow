<?php

namespace App\Container;

use App\Abstract\Container;
use InvalidArgumentException;

class JobTitleContainer extends Container
{
    /**
     * Initializes the JobTitleContainer with an array of job title strings.
     *
     * The constructor iterates over the provided array and adds each job title to the container:
     * - Ensures every element in $jobTitles is a string
     * - Throws InvalidArgumentException when a non-string element is encountered
     * - Calls add() for each validated job title
     *
     * @param string[] $jobTitles Array of job title strings to populate the container
     *
     * @throws InvalidArgumentException If any element of $jobTitles is not a string
     */
    public function __construct(array $jobTitles = [])
    {
        foreach ($jobTitles as $jobTitle) {
            if (!is_string($jobTitle)) {
                throw new InvalidArgumentException('All elements of jobTitles array must be instances of String.');
            }
            $this->add($jobTitle);
        }
    }

    /**
     * Adds a job title to the container.
     *
     * Only string values are accepted. If a non-string value is provided an
     * InvalidArgumentException will be thrown. The valid job title string is
     * appended to the container's internal items collection.
     *
     * @param string $jobTitle The job title to add.
     *
     * @throws InvalidArgumentException If the provided $jobTitle is not a string.
     *
     * @return void
     */
    public function add($jobTitle): void
    {
        if (!is_string($jobTitle)) {
            throw new InvalidArgumentException('Only String instances can be added to JobTitlesContainer.');
        }

        $this->items[] = $jobTitle;
    }

    /**
     * Removes a job title from the container.
     *
     * This method validates the input and removes the first matching entry from the internal items array:
     * - Ensures the provided $jobTitle is a string; otherwise an exception is thrown
     * - Performs a strict (type- and value-sensitive) search for the job title
     * - Removes only the first matching occurrence
     * - Re-indexes the internal items array after removal
     * - If the job title is not found, the method performs no action
     *
     * @param string $jobTitle The exact job title string to remove (case- and type-sensitive)
     *
     * @throws \InvalidArgumentException If $jobTitle is not a string
     *
     * @return void Mutates the container by removing the specified job title when found
     */
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
     * Determines whether the container contains the specified job title.
     *
     * This method accepts a single job title string and checks for its presence
     * among the container's items using a strict comparison (in_array(..., true)),
     * which enforces both type and value equality. The check is case-sensitive
     * and will only match exact string entries previously stored in the container.
     * Providing a non-string value will result in an exception.
     *
     * @param string $item Job title to check for in the container.
     *
     * @return bool True if the job title exists in the container, false otherwise.
     *
     * @throws InvalidArgumentException If the provided $item is not a string.
     */
    public function contains($item): bool
    {
        if (!is_string($item)) {
            throw new InvalidArgumentException('Only String instances can be checked in JobTitleContainer.');
        }
        return in_array($item, $this->items, true);
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
