<?php

use App\Auth\SessionAuth;
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
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'about-us.css' ?>">
</head>

<body>
    <?php if (SessionAuth::hasAuthorizedSession()) {
        require_once COMPONENT_PATH . 'sidenav.php';
    } ?>

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
            <h1 class="heading center-text">Our Team</h1>

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
        <section id="contact" class="contact flex-col">
            <h1 class="heading center-text">Contact Us</h1>

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
                        <div class="contact-reference text-w-icon">
                            <img class="circle white-bg" src="<?= ICON_PATH . 'email_b.svg' ?>" alt="" height="32">

                            <div class="flex-col">
                                <h3 class="contact-link blue-text bold-text">Email:</h3>
                                <a class="contact-link"
                                    href="https://mail.google.com/mail/u/0/#inbox?compose=CllgCJlHnRnmffXbCrHkkkPqmBbdHxktLkWxmbgvfKDrlBlzCzXKlPkfWfTkdxXNZLddTVPsNHg"><em>taskflow.reset@gmail.com</em></a>
                            </div>
                        </div>

                        <div class="contact-reference text-w-icon">
                            <img class="circle white-bg" src="<?= ICON_PATH . 'contact_b.svg' ?>" alt="" height="32">

                            <div class="flex-col">
                                <h3 class="contact-link blue-text bold-text start-text">Contact :</h3>
                                <p class="contact-link"><em>+63 912 345 6789</em></p>
                            </div>
                        </div>

                        <div class=" contact-reference text-w-icon">
                            <img class="circle white-bg" src="<?= ICON_PATH . 'home_b.svg' ?>" alt="" height="32">

                            <div class="flex-col">
                                <h3 class="contact-link blue-text bold-text start-text">Address:</h3>
                                <a class="contact-link"
                                    href="https://www.google.com/maps/place/Lt.+Gen.+Alfonso+Arellano,+Taguig,+Kalakhang+Maynila/@14.5301671,121.0434605,17z/data=!3m1!4b1!4m6!3m5!1s0x3397c8c53373305f:0x91d78e54d31b54e4!8m2!3d14.5301619!4d121.0460354!16s%2Fg%2F1tk656h1?entry=ttu&g_ep=EgoyMDI1MTEzMC4wIKXMDSoASAFQAw%3D%3D"><em>Lt.
                                        Gen. Alfonso Arellano, Fort Bonifacio, Taguig City, Metro Manila</em></a>
                            </div>
                        </div>

                    </section>

                </section>
            </section>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'toggle-menu.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'about-us' . DS . 'carousel-tracker.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'about-us' . DS . 'send-concern.js' ?>" defer></script>
</body>

</html>