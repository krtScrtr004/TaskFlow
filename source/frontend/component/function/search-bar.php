<?php
/**
 *
 * @param array|null $filterOptions Filter options in format [ optionName => [optionGroup], optionName => [optionGroup], ...] or null to disable filter
 * @param string $placeholder Placeholder text for search input
 * @return void Outputs the search bar HTML
 */
function searchBar(
    ?array $filterOptions = null,
    string $placeholder = 'Search by Name or ID'
): string {
    if (!$filterOptions && !isAssociativeArray($filterOptions)) 
        throw new InvalidArgumentException('Filter options must be an associative array.');

    $searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
    $searchFilter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';

    ob_start();
    ?>
    <form class="search-bar" action="" method="POST">
        <div>
            <input class="search-input" type="text" name="search_bar_input" id="search_bar_input"
                placeholder="<?= $placeholder ?>" min="1" max="255" value="<?= $searchKey ?>" autocomplete="on" required>
            <button class="search-button" id="search_bar_button" type="button">
                <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search" title="Search" height="20">
            </button>
        </div>

        <?php if ($filterOptions): ?>
            <select class="search-filter" name="search_bar_filter" id="search_bar_filter">
                <!-- Default Option -->
                <option value="all" <?= $searchFilter === 'all' ? 'selected' : '' ?>>All Projects</option>

                <?php foreach ($filterOptions as $group => $groups) { ?>
                    <optgroup label="<?= ucwords(camelToSentenceCase($group)) ?>">
                        <?php foreach ($groups as $value) { ?>
                            <option value="<?= $value ?>" <?= $searchFilter === $value ? 'selected' : '' ?>>
                                <?= ucwords(camelToSentenceCase($value)) ?>
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