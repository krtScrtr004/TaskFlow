<?php

use App\Enumeration\Role;
use App\Enumeration\WorkerStatus;
use App\Middleware\Csrf;

if (!isset($users))
    throw new Error('Users data is required.');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

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

            <section class="user-grid grid">
                <?php foreach ($users as $user) {
                    echo userGridCard($user);
                } ?>
            </section>

            <!-- Sentinel -->
            <div class="sentinel"></div>

            <div
                class="no-users-wall no-content-wall <?= count($users) > 0 ? 'no-display' : 'flex-col' ?>">
                <img src="<?= ICON_PATH . 'empty_w.svg' ?>" alt="No users available" title="No users available"
                    height="70">
                <h3 class="center-text">No users found.</h3>
            </div>
        </section>
    </main>

    <script type="module" src="<?= EVENT_PATH . 'toggle-menu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'break-text-fallback.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'search.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'infinite-scroll.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'users' . DS . 'create-user-card.js' ?>" defer></script>

</body>

</html>