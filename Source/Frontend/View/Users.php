<?php

use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Middleware\Csrf;

if (!isset($users)) throw new Error('Users data is required');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title>Users</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="icon" type="image/x-icon" href="<?= IMAGE_PATH . 'logo-dark.ico' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'Users.css' ?>">
</head>

<body>
    <?php 
    require_once COMPONENT_PATH . 'Sidenav.php';
    require_once COMPONENT_PATH . 'Template' . DS . 'UserInfoCard.php';
    ?>

    <main class="users main-page flex-col">

        <!-- Search Bar -->
        <section class="search-bar-container">
            <?= searchBar([
                'Role' => [
                    Role::PROJECT_MANAGER->getDisplayName(),
                    Role::WORKER->getDisplayName()
                ],
                'Status' => [
                    WorkerStatus::ASSIGNED->getDisplayName(),
                    WorkerStatus::UNASSIGNED->getDisplayName(),
                ]
            ]) ?>
        </section>

        <!-- User Grid -->
        <section class="user-grid-container">

            <section class="user-grid grid-card-container grid">
                <?php foreach ($users as $user) {
                    echo userGridCard($user);
                } ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

            <div
                class="no-users-wall light-black-bg no-content-wall <?= count($users) > 0 ? 'no-display' : 'flex-col' ?>">
                <img src="<?= ICON_PATH . 'empty_dw.svg' ?>" alt="No users available" title="No users available"
                    height="80">
                <h3 class="center-text">No users found.</h3>
            </div>
        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'ToggleMenu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'BreakTextFallback.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'Users' . DS . 'Search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Users' . DS . 'InfiniteScroll.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'Users' . DS . 'CreateUserCard.js' ?>" defer></script>

</body>

</html>