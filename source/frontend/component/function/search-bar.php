<?php

/**
 * Renders a searchable HTML form (search bar) and returns it as a string.
 *
 * The generated form contains:
 * - A text input for the search query (required) with placeholder and value populated
 *   from the current request (escaped via htmlspecialchars).
 * - A submit button containing a search icon (ICON_PATH constant is used).
 * - An optional <select> filter grouped by provided filter options. Each group is
 *   rendered as an <optgroup> and each option value is converted to a camel-case
 *   value attribute using sentenceToCamelCase while the visible label remains the
 *   original value. The current selected filter is taken from $_GET['filter'].
 *
 * Notes and behavior:
 * - Validates that $filterOptions is an associative array using isAssociativeArray();
 *   otherwise an InvalidArgumentException is thrown.
 * - Reads initial values from the GET superglobal:
 *     - 'key' populates the search input value (escaped).
 *     - 'filter' populates the selected filter (escaped).
 * - Escapes user-supplied values with htmlspecialchars before output.
 * - Uses output buffering to capture and return the generated HTML as a string.
 * - The form posts via POST to the current URL.
 * - Relies on the constants NAME_MIN, NAME_MAX (used for input min/max attributes)
 *   and ICON_PATH (used to locate the search icon).
 * - Relies on helper functions: isAssociativeArray(), camelToSentenceCase(),
 *   sentenceToCamelCase().
 *
 * @param array|null $filterOptions Associative array of filter groups to option lists, e.g.:
 *      [
 *          'groupName' => ['Option One', 'Option Two'],
 *          'anotherGroup' => ['One', 'Two']
 *      ]
 *      If null or an empty array is provided, no filter <select> is rendered.
 * @param string $placeholder Placeholder text for the search input (default: 'Search...')
 *
 * @throws InvalidArgumentException If $filterOptions is not an associative array.
 *
 * @return string HTML markup of the search form
 */
function searchBar(
    ?array $filterOptions = null,
    string $placeholder = 'Search...'
): string {
    if (!isAssociativeArray($filterOptions)) 
        throw new InvalidArgumentException('Filter options must be an associative array.');

    $searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
    $searchFilter = htmlspecialchars($_GET['filter'] ?? 'all');

    ob_start();
    ?>
    <form class="search-bar" action="" method="POST">
        <div class="search-bar-container">
            <input class="search-input" type="text" name="search_bar_input" id="search_bar_input"
                placeholder="<?= $placeholder ?>" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" value="<?= $searchKey ?>" autocomplete="on" required>
            <button class="search-button" id="search_bar_button" type="submit">
                <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search" title="Search" height="20">
            </button>
        </div>

        <?php if ($filterOptions || ($filterOptions && count($filterOptions) > 0)): ?>
            <select class="search-filter" name="search_bar_filter" id="search_bar_filter">
                <!-- Default Option -->
                <option value="all" <?= $searchFilter === 'all' ? 'selected' : '' ?>>All</option>

                <?php foreach ($filterOptions as $group => $groups) { ?>
                    <optgroup label="<?= ucwords(camelToSentenceCase($group)) ?>">
                        <?php foreach ($groups as $value) { ?>
                            <option value="<?= sentenceToCamelCase($value) ?>" <?= $searchFilter === sentenceToCamelCase($value) ? 'selected' : '' ?>>
                                <?= $value ?>
                            </option>
                        <?php } ?>
                    </optgroup>
                <?php } ?>
            </select>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}