<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Project</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">

</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="">
        <!-- No content -->
        <section class="no-content full-body-content no-display flex-child-center-h flex-child-center-v">
            <img 
                src="<?= ICON_PATH . 'empty_b.svg' ?>" 
                alt="No active project found"
                title="No active project found"
                height="150">
            <h3>No active project found</h3>
        </section>

        <!-- Main Content -->
        <section>
            
        </section>
    </main>
</body>

</html>