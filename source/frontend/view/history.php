<?php
if (!isset($projects))
    throw new ErrorException('Projects data are required to render this view');
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
            <?= searchBar([
                'Status' => [
                    'pending',
                'onGoing',
                'completed',
                'delayed',
                'cancelled'
                ]
            ]) ?>
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
    <script type="module" src="<?= EVENT_PATH . 'history' . DS . 'search.js' ?>" defer></script>
</body>

</html>