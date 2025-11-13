<?php
if (!isset($component)) {
    throw new Exception('No component context found.');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $component['title'] ?? 'ERROR' ?></title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'http-error.css' ?>">

</head>

<body>
    <main>
        <div id="sec-1" class="section">
            <div id="ctn">
                <div class="marquee">
                    <div class="marquee-text"></div>
                    <div class="marquee-text"></div>
                    <div class="marquee-text"></div>
                </div>

                <div class="text-ctn">ERROR</div>

                <div id="context"><?= $component['title'] ?? 'ERROR OCCURRED' ?></div>

                <div class="text-ctn">HTTP<br><?= $component['status'] ?? '400' ?></div>

                <div class="marquee">
                    <div class="marquee-text"></div>
                    <div class="marquee-text"></div>
                    <div class="marquee-text"></div>
                </div>
            </div>
        </div>
    </main>

</body>

</html>