<?php
if (!isset($projects))
    throw new ErrorException('Projects data are required to render this view');

$searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
$searchFilter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>History</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'history.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="history main-page">

        <!-- Heading -->
        <section class="heading content-section-block">
            <h3>Project History</h3>
            <p>Track all projects you've handled</p>
        </section>

        <!-- Search Bar -->
        <section class="flex-row flex-child-end-v">
            <form class="search-bar" action="" method="POST">
                <div>
                    <input type="text" name="search_project_input" id="search_project_input"
                        placeholder="Search by Name or ID" min="1" max="255" value="<?= $searchKey ?>" autocomplete="on"
                        required>
                    <button id="search_project_button" type="button" class="transparent-bg">
                        <img src="<?= ICON_PATH . 'search_w.svg' ?>" alt="Search Project" title="Search Project"
                            height="20">
                    </button>
                </div>

                <select class="" name="search_project_filter" id="search_project_filter">

                    <!-- Default Option -->
                    <option value="all" selected>All Projects</option>

                    <option value="pending" <?= $searchFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="onGoing" <?= $searchFilter === 'onGoing' ? 'selected' : '' ?>>On Going</option>
                    <option value="completed" <?= $searchFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="delayed" <?= $searchFilter === 'delayed' ? 'selected' : '' ?>>Delayed</option>
                    <option value="cancelled" <?= $searchFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>

                </select>
            </form>
        </section>

        <section class="project-grid-container">
            <!-- Projects Grid -->
            <section class="project-grid grid">
                <?php foreach ($projects as $project) {
                    echo projectGridCard($project);
                } ?>
            </section>

            <div class="sentinel"></div>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'history' . DS . 'infinite-scroll.js' ?>" defer></script>
</body>

</html>