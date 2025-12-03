<?php

use App\Middleware\Csrf;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title>About Us</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'about-us.css' ?>">
</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="about-us flex-col main-page">

        <!-- About -->
        <section class="about flex-row center-child">
            <h1 class="end-text">About Us</h1>

            <p class="start-text">
                TaskFlow is an enterprise-ready project management platform designed to streamline how organizations
                plan, coordinate, and evaluate complex initiatives. By organizing work into a structured hierarchy of
                projects, phases, and tasks, the system provides clear visibility into operational performance and
                resource utilization. Its priority-weighted calculation engines generate reliable progress measurements
                and objective performance assessments, enabling leaders to make informed decisions on resource
                distribution and capacity planning.
            </p>
        </section>

        <!-- Our Team -->
        <section class="our-team flex-col">
            <h1 class="heading center-text sticky">Our Team</h1>

            <section class="team-member-carousel carousel-wrapper relative">
                <section class="carousel flex-row">
                    <?php foreach ($memberData as $member): ?>
                        <div class="team-member-card relative">
                            <img class="fit-cover circle absolute" src="<?= IMAGE_PATH . 'team/' . $member['image'] ?>"
                                alt="<?= $member['name'] ?>" title="<?= $member['name'] ?>" height="100">

                            <!-- Info -->
                            <section class="info">
                                <h2 class="name center-text"><?= $member['name'] ?></h2>

                                <div class="roles center-child flex-wrap">
                                    <?php foreach ($member['roles'] as $role): ?>
                                        <span class="role badge white-bg black-text"><?= $role ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <p class="bio center-child"><?= $member['bio'] ?></p>
                            </section>
                        </div>

                    <?php endforeach; ?>
                </section>
                
                <div class="left-button tracker absolute">
                    <img src="<?= ICON_PATH . 'back.svg' ?>" alt="Left Button" height="32">
                </div>
                                        
                <div class="right-button tracker absolute">
                    <img src="<?= ICON_PATH . 'back.svg' ?>" alt="Right Button" height="32">
                </div>
            </section>
        </section>

        <!-- Contact -->
        <section class="contact flex-col">
            <h1 class="heading center-text sticky">Contact Us</h1>

            <section class="flex-row">
                <form id="concern_form" class="contact-form flex-col" action="" method="POST">
                    <div class="flex-row">
                        <input type="text" name="user_name" id="user_name" placeholder="Full Name" min="<?= NAME_MIN ?>"
                            max="<?= NAME_MAX ?>" required>
                        <input type="text" name="user_email" id="user_email" placeholder="Email Address"
                            min="<?= URI_MIN ?>" max="<?= URI_MAX ?>" required>
                    </div>

                    <textarea name="message" id="message" placeholder="Type your concern here..." cols="40" rows="10"
                        minlength="<?= LONG_TEXT_MIN ?>" maxlength="<?= LONG_TEXT_MAX ?>" required></textarea>

                    <button id="send_button" type="button" class="transparent-bg black-text center-child">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'send_w.svg' ?>" alt="" height="20">
                            <h3 class="white-text">Send Message</h3>
                        </div>
                    </button>
                </form>

                <section class="contact-reference-section">
                    <p class="start-text">
                        Feel free to reach out to us for any inquiries, feedback, or
                        collaboration opportunities. We would love to hear from you!
                    </p>

                    <section class="flex-col">
                        <div class="contact-reference">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'email_w.svg' ?>" alt="" height="20">
                                <h3>Email: </h3>
                            </div>

                            <p class="contact-link"><em>taskflow.reset@gmail.com</em></p>
                        </div>

                        <div class="contact-reference">
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'contact_w.svg' ?>" alt="" height="20">
                                <h3>Contact Number: </h3>
                            </div>

                            <p class="contact-link"><em>+63 912 345 6789</em></p>
                        </div>

                        <div>
                            <div class="text-w-icon">
                                <img src="<?= ICON_PATH . 'home_w.svg' ?>" alt="" height="20">
                                <h3>Address: </h3>
                            </div>

                            <p class="contact-link"><em>Lt. Gen. Alfonso Arellano, Fort Bonifacio, Taguig City, Metro
                                    Manila</em></p>
                        </div>
                    </section>

                </section>
            </section>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'about-us' . DS . 'carousel-tracker.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'about-us' . DS . 'send-concern.js' ?>" defer></script>
</body>

</html>