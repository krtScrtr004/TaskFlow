<?php

use App\Container\JobTitleContainer;

/**
 * Generates an HTML preview of job titles with a limit on the number of titles displayed.
 *
 * This function takes a JobTitleContainer object and iterates through its job titles,
 * generating HTML markup for each title up to the specified limit. If there are more
 * job titles than the limit, a summary chip is added to indicate the remaining count.
 *
 * Behavior and side effects:
 * - Iterates through the job titles in the JobTitleContainer up to the specified limit.
 * - Generates HTML markup for each job title as a styled chip.
 * - Escapes job title strings using htmlspecialchars() to prevent XSS attacks.
 * - Optionally limits the maximum character width of each job title chip if $charLimit is provided.
 * - If the total number of job titles exceeds the limit, appends an additional chip
 *   indicating the number of remaining titles.
 * - Stops processing if a null value is encountered in the job title list.
 *
 * @param JobTitleContainer $jobTitle The container holding the job titles to preview.
 * @param int $limit The maximum number of job titles to display (default is 3).
 * @param int|null $charLimit Optional maximum character width for each job title chip.
 *
 * @return string The generated HTML string containing the job title preview or empty string if no job titles are present.
 * @throws InvalidArgumentException If $limit is not a positive integer or if $charLimit is provided and is not a positive integer.
 */
function jobTitlePreview(JobTitleContainer $jobTitle, int $limit = 3, int|null $charLimit = null): string
{
    if ($limit <= 0) throw new InvalidArgumentException('Limit must be a positive integer');
    if ($charLimit !== null && $charLimit <= 0)
        throw new InvalidArgumentException('Character limit must be a positive integer or null');

    if ($jobTitle->count() === 0) return '';

    $html = '';
    $total = $jobTitle->count();

    $counter = 0;
    while ($counter < $total && $counter < $limit) {
        $current = htmlspecialchars($jobTitle->get($counter));
        if ($charLimit !== null && strlen($current) > $charLimit)
            $current = substr($current, 0, $charLimit) . '…';

        $html .= '
            <span class="job-title-chip" title="' . $current . '">
                <p class="dark-white-text light-text">
                    ' . $current . '
                </p>
            </span>';
        $counter++;
    }

    $remaining = $total - $limit;
    $remainingTitles = array_slice($jobTitle->toArray(), $limit);
    if ($remaining > 0) {
        $html .= '
            <span class="job-title-chip" title="' . htmlspecialchars(implode(', ', $remainingTitles)) . '   ">
                <p class="dark-white-text light-text">
                    +' . $remaining . '
                </p>
            </span>';
    }

    return $html;
}
