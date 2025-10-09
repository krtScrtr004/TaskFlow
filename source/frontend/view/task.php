<?php
if (!$project)
    throw new Error('Project data is required.');
$projectId = $project->getPublicId();

if (!isset($tasks))
    throw new Error('Tasks data is required.');

$searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
$searchFilter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'task.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="task main-page flex-col">

        <!-- Search Bar -->
        <section>
            <form class="search-bar" action="" method="POST">
                <div>
                    <input type="text" name="search_task_input" id="search_task_input" placeholder="Search by Name or ID" min="1"
                        max="255" value="<?= $searchKey ?>" autocomplete="on" required>
                    <button id="search_task_button" type="button" class="transparent-bg">
                        <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search Task" title="Search Task" height="20">
                    </button>
                </div>

                <select class="" name="search_task_filter" id="search_task_filter">

                    <!-- Default Option -->
                    <option value="all" selected>All Tasks</option>

                    <!-- Filter By Status -->
                    <optgroup class="filter-group" label="Filter by Status">
                        <option value="allStatus" <?= $searchFilter === 'allStatus' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $searchFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="onGoing" <?= $searchFilter === 'onGoing' ? 'selected' : '' ?>>On Going</option>
                        <option value="completed" <?= $searchFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="delayed" <?= $searchFilter === 'delayed' ? 'selected' : '' ?>>Delayed</option>
                        <option value="cancelled" <?= $searchFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </optgroup>

                    <!-- Filter By Priority -->
                    <optgroup class="filter-group" label="Filter by Priority">
                        <option value="allPriority" <?= $searchFilter === 'allPriority' ? 'selected' : '' ?>>All Priorities</option>
                        <option value="high" <?= $searchFilter === 'high' ? 'selected' : '' ?>>High Priority</option>
                        <option value="medium" <?= $searchFilter === 'medium' ? 'selected' : '' ?>>Medium Priority</option>
                        <option value="low" <?= $searchFilter === 'low' ? 'selected' : '' ?>>Low Priority</option>
                    </optgroup>

                </select>

            </form>
        </section>


        <!-- Task Grid -->
        <section class="task-grid-container" data-projectid="<?= $projectId ?>">

            <section class="task-grid grid">
                <?php if (Role::isProjectManager(Me::getInstance())): ?>
                    <a href="<?= REDIRECT_PATH . "add-task/$projectId" ?>"
                        class="add-task-button task-grid-card flex-col flex-child-center-h flex-child-center-v">
                        <img src="<?= ICON_PATH . 'add_w.svg' ?>" alt="Add New Task" title="Add New Task" height="90">
                        <h3>Add New Task</h3>
                    </a>
                <?php endif; ?>

                <?php foreach ($tasks as $task) {
                    echo taskGridCard($task, $projectId);
                } ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'break-text-fallback.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'task' . DS . 'search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'task' . DS . 'infinite-scroll.js' ?>" defer></script>

</body>

</html>