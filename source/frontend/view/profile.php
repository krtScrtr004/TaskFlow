<?php

use App\Core\Me;
use App\Core\UUID;
use App\Enumeration\Gender;
use App\Enumeration\Role;
use App\Middleware\Csrf;

$me = Me::getInstance();
if (!$me)
    throw new Exception("User is required to view profile.");

$myData = [
    'id' => htmlspecialchars(UUID::toString($me->getPublicId())),
    'firstName' => htmlspecialchars($me->getFirstName()),
    'middleName' => htmlspecialchars($me->getMiddleName()),
    'lastName' => htmlspecialchars($me->getLastName()),
    'fullName' => htmlspecialchars($me->getFirstName()) . ($me->getMiddleName() ? ' ' . htmlspecialchars($me->getMiddleName()) . ' ' : ' ') . htmlspecialchars($me->getLastName()),
    'gender' => $me->getGender(),
    'birthDate' => $me->getBirthDate() ? htmlspecialchars(formatDateTime($me->getBirthDate(), 'Y-m-d')) : null,
    'bio' => $me->getBio() ? htmlspecialchars($me->getBio()) : null,
    'role' => $me->getRole(),
    'email' => htmlspecialchars($me->getEmail()),
    'contactNumber' => htmlspecialchars($me->getContactNumber()),
    'profileLink' => $me->getProfileLink() ? htmlspecialchars($me->getProfileLink()) : ICON_PATH . 'profile_w.svg',
    'jobTitles' => array_map('htmlspecialchars', $me->getJobTitles()->getItems()),
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Csrf::get() ?>">

    <title>Profile</title>

    <base href="<?= PUBLIC_PATH ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'root.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'utility.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'component.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'sidenav.css' ?>">
    <link rel="stylesheet" href="<?= STYLE_PATH . 'loader.css' ?>">

    <link rel="stylesheet" href="<?= STYLE_PATH . 'profile.css' ?>">

</head>

<body>
    <?php require_once COMPONENT_PATH . 'sidenav.php' ?>

    <main class="profile main-page flex-col" data-myid="<?= $myData['id'] ?>">

        <!-- Profile Overview -->
        <section class="profile-overview content-section-block flex-row">
            <!-- Profile Picture -->
            <section class="flex-col">
                <!-- Profile Picture Overview -->
                <img class="circle fit-cover" id="profile_picture_overview" src="<?= $myData['profileLink'] ?>"
                    alt="<?= $myData['fullName'] ?>" title="<?= $myData['fullName'] ?>" loading="lazy" height="100">

                <div>
                    <input class="no-display" type="file" name="profile_picker" id="profile_picker"
                        accept="image/.png, image/.jpg, image/.jpeg">

                    <!-- Change Profile Picture Button -->
                    <button id="pick_profile_picture_button" class="blue-bg" type="button">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'upload_w.svg' ?>" alt="Change Profile Picture"
                                title="Change Profile Picture" height="20">
                            <h3>Change</h3>
                        </div>
                    </button>
                </div>

                <!-- Role Chip -->
                <div class="role-chip flex-col white-bg">
                    <div class="text-w-icon">
                        <?php if (Role::isProjectManager($me)): ?>
                            <img src="<?= ICON_PATH . 'manager_b.svg' ?>" alt="Project Manager" title="Project Manager"
                                height="20">
                        <?php else: ?>
                            <img src="<?= ICON_PATH . 'worker_b.svg' ?>" alt="Worker" title="Worker" height="20">
                        <?php endif; ?>

                        <h3 class="role black-text"><?= htmlspecialchars($myData['role']->getDisplayName()) ?></h3>
                    </div>

                    <!-- Id -->
                    <p class="id black-text"><?= $myData['id'] ?></p>
                </div>
            </section>

            <section class="primary-info flex-col">
                <!-- Full Name -->
                <h1 class="full-name wrap-text"><?= $myData['fullName'] ?></h1>

                <!-- Job Titles -->
                <div class="job-titles flex-wrap">
                    <?php foreach ($myData['jobTitles'] as $jobTitle): ?>
                        <span class="job-title-chip"><?= htmlspecialchars($jobTitle) ?></span>
                    <?php endforeach; ?>
                </div>

                <!-- Bio -->
                <p class="bio wrap-text"><?= $myData['bio'] ?? 'No bio available...' ?></p>
            </section>

        </section>

        <!-- Editable Profile Details -->
        <form id="editable_profile_details_form" class="content-section-block flex-col" action="" method="POST">

            <!-- Heading-->
            <div class="text-w-icon">
                <img src="<?= ICON_PATH . 'edit_w.svg' ?>" alt="Edit Profile Details" title="Edit Profile Details"
                    height="30">
                <h3>Edit Profile Details</h3>
            </div>

            <div class="name-inputs three-input-layout flex-row">
                <!-- First Name -->
                <div class="input-label-container">
                    <label for="first_name">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="First Name" title="First Name" height="20">
                            <p>First Name</p>
                        </div>
                    </label>
                    <input type="text" name="first_name" id="first_name" placeholder="First Name"
                        value="<?= $myData['firstName'] ?>" required>
                </div>

                <!-- Middle Name -->
                <div class="input-label-container">
                    <label for="middle_name">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Middle Name" title="Middle Name"
                                height="20">
                            <p>Middle Name</p>
                        </div>
                    </label>
                    <input type="text" name="middle_name" id="middle_name" placeholder="Middle Name"
                        value="<?= $myData['middleName'] ?>" required>
                </div>

                <!-- Last Name -->
                <div class="input-label-container">
                    <label for="last_name">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'name_w.svg' ?>" alt="Last Name" title="Last Name" height="20">
                            <p>Last Name</p>
                        </div>
                    </label>
                    <input type="text" name="last_name" id="last_name" placeholder="Last Name"
                        value="<?= $myData['lastName'] ?>" required>
                </div>
            </div>

            <section class="grid">

                <!-- Email -->
                <div class="input-label-container">
                    <label for="email">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'email_w.svg' ?>" alt="Email" title="Email" height="20">
                            <p>Email</p>
                        </div>
                    </label>
                    <input type="email" name="email" id="email" placeholder="Email" value="<?= $myData['email'] ?>"
                        disabled>
                </div>

                <!--Contact Number -->
                <div class="input-label-container">
                    <label for="contact_number">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'contact_w.svg' ?>" alt="Contact Number" title="Contact Number"
                                height="20">
                            <p>Contact Number</p>
                        </div>
                    </label>

                    <input type="tel" name="contact_number" id="contact_number" placeholder="Contact Number"
                        pattern="\+?[\d\s\-\(\)]{11,20}" value="<?= $myData['contactNumber'] ?>" minlength="11"
                        maxlength="20" required>
                </div>

            </section>

            <div class="three-input-layout flex-row">
                <!-- Birth Date -->
                <div class="input-label-container">
                    <label for="birth_date">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'date_w.svg' ?>" alt="Birth Date" title="Birth Date" height="20">
                            <p>Birth Date</p>
                        </div>
                    </label>

                    <input type="date" name="birth_date" id="birth_date" value="<?= $myData['birthDate'] ?>" required>
                </div>

                <!-- Gender -->
                <div class="input-label-container">
                    <label for="gender">
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'gender_w.svg' ?>" alt="Gender" title="Gender" height="20">
                            <p>Gender</p>
                        </div>
                    </label>

                    <div class="gender-selections flex-row">
                        <div class="flex-row-reverse flex-child-center-h">
                            <label for="gender_male">Male</label>
                            <input type="radio" name="gender" id="gender_male" value="male"
                                <?= ($myData['gender'] === Gender::MALE) ? 'checked' : ''; ?> required>
                        </div>

                        <div class="flex-row-reverse flex-child-center-h">
                            <label for="gender_female">Female</label>
                            <input type="radio" name="gender" id="gender_female" value="female"
                                <?= ($myData['gender'] === Gender::FEMALE) ? 'checked' : ''; ?> required>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="input-label-container">
                    <div>
                        <div class="text-w-icon">
                            <img src="<?= ICON_PATH . 'password_w.svg' ?>" alt="Password" title="Password" height="20">
                            <p>Password</p>
                        </div>
                    </div>

                    <a href="<?= REDIRECT_PATH . 'reset-password' ?>" class="blue-text">Change Password</a>
                </div>
            </div>

            <!--Job Titles -->
            <div class="input-label-container">
                <label for="job_titles">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'job_w.svg' ?>" alt="Job Titles" title="Job Titles" height="18">
                        <p>Job Titles</p>
                    </div>
                </label>

                <?php $jobTitlesString = implode(', ', $myData['jobTitles']) ?>
                <input type="text" name="job_titles" id="job_titles" placeholder="Job Titles (Comma separated)"
                    value="<?= $jobTitlesString ?>" required>
            </div>

            <!-- Bio -->
            <div class="input-label-container">
                <label for="bio">
                    <div class="text-w-icon">
                        <img src="<?= ICON_PATH . 'description_w.svg' ?>" alt="Bio" title="Bio" height="20">
                        <p>Bio</p>
                    </div>
                </label>
                <textarea name="bio" id="bio" rows="4" cols="10" placeholder="Type your bio here..." minlength="1"
                    maxlength="300"><?= $myData['bio'] ?></textarea>
            </div>

            <!-- Save Changes Button -->
            <button id="save_changes_button" type="button" class="blue-bg" disabled>
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'save_w.svg' ?>" alt="Save Changes" title="Save Changes" height="20">
                    <h3>Save Changes</h3>
                </div>
            </button>

        </form>

        <!-- Actions -->
        <section class="actions flex-col content-section-block">
            <div class="heading-title text-w-icon">
                <img src="<?= ICON_PATH . 'action_w.svg' ?>" alt="Project Actions" title="Project Actions" height="20">

                <h3>Actions</h3>
            </div>

            <hr>

            <!-- Delete -->
            <button id="delete_my_account_button" type="button" class="unset-button">
                <p class="red-text">Delete My Account</p>
            </button>
        </section>

    </main>

    <script type="module" src="<?= EVENT_PATH . 'logout.js' ?>" defer></script>

    <script type="module" src="<?= EVENT_PATH . 'profile' . DS . 'change-profile.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'profile' . DS . 'submit.js' ?>" defer></script>
    <script type="module" src="<?= EVENT_PATH . 'profile' . DS . 'delete.js' ?>" defer></script>
</body>

</html>