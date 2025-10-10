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

        <!-- Projects Grid -->
        <section class="project-grid grid">
            <?php foreach ($projects as $project) {
                echo projectGridCard($project);
            } ?>
        </section>

    </main>
</body>

</html>