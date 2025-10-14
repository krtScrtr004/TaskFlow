<?php
if (!isset($users))
    throw new Error('Users data is required.');

// $searchKey = isset($_GET['key']) ? htmlspecialchars($_GET['key']) : '';
// $searchFilter = isset($_GET['filter']) ? htmlspecialchars($_GET['filter']) : 'all';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'users.css' ?>">
</head>

<body>
    <?php 
    require_once COMPONENT_PATH . 'sidenav.php';
    require_once COMPONENT_PATH . 'template' . DS . 'user-info-card.php';
    ?>

    <main class="users main-page flex-col">

        <!-- Search Bar -->
        <section>
            <?= searchBar([
                'Role' => [
                    'All Roles',
                    Role::PROJECT_MANAGER->getDisplayName(),
                    Role::WORKER->getDisplayName()
                ],
                'Status' => [
                    'All Statuses',
                    WorkerStatus::ACTIVE->getDisplayName(),
                    WorkerStatus::UNASSIGNED->getDisplayName(),
                    WorkerStatus::ON_LEAVE->getDisplayName(),
                    WorkerStatus::TERMINATED->getDisplayName()
                ]
            ]) ?>
        </section>

        <!-- User Grid -->
        <section class="user-grid-container" data-projectid="<?= $projectId ?>">

            <section class="user-grid grid">
                <?php foreach ($users as $user) {
                    echo userGridCard($user);
                } ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'break-text-fallback.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'infinite-scroll.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'create-user-card.js' ?>" defer></script>

</body>

</html>