<?php
use App\Enumeration\Role;
?>

<form id="register_form" class="index-form flex-col" action="" method="POST">
    <?= hiddenCsrfInput() ?>

    <div class="separated-input flex-row">
        <!-- First Name -->
        <input type="text" id="register_first_name" name="register_first_name" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" placeholder="First Name"
            autocomplete="on" required>

        <!-- Middle Name -->
        <input type="text" id="register_middle_name" name="register_middle_name" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>"
            placeholder="Middle Name" autocomplete="on" required>
    </div>

    <!-- Last Name -->
    <input type="text" id="register_last_name" name="register_last_name" min="<?= NAME_MIN ?>" max="<?= NAME_MAX ?>" placeholder="Last Name"
        autocomplete="on" required>

    <!-- Gender -->
    <div class="gender-radio flex-row">
        <div class="flex-row-reverse flex-child-center-h">
            <label for="register_gender_male">Male</label>
            <input type="radio" name="gender" id="register_gender_male" value="male" required>
        </div>

        <div class="flex-row-reverse flex-child-center-h">
            <label for="register_gender_female">Female</label>
            <input type="radio" name="gender" id="register_gender_female" value="female" required>
        </div>
    </div>

    <div class="separated-input flex-row">
    <!-- Contact Number -->
        <input type="tel" name="register_contact" id="register_contact" placeholder="Contact Number" pattern="[0-9]{11,20}"
            minlength="<?= CONTACT_NUMBER_MIN ?>" maxlength="<?= CONTACT_NUMBER_MAX ?>" required>

        <!-- Birth Date -->
        <input type="date" name="register_birth_date" id="register_birth_date"
            value="<?= formatDateTime(new DateTime(), 'Y-m-d') ?>" required>
    </div>


    <!-- Job Titles -->
    <input type="text" name="register_job_titles" id="register_job_titles" placeholder="Job Titles (comma separated)"
        min="1" max="255" autocomplete="on" required>

    <!-- Email -->
    <input type="email" name="register_email" id="register_email" placeholder="Email" min="<?= URI_MIN ?>" max="<?= URI_MAX ?>"
        autocomplete="on" required>

    <!-- Password -->
    <div class="password-toggle-wrapper">
        <img class="absolute" src="<?= ICON_PATH . 'show_w.svg'; ?>" alt="Show password" title="Show password"
            width="18" height="18" />

        <input type="password" name="register_password" id="register_password"
            placeholder="Please enter your password here" min="<?= PASSWORD_MIN ?>" max="<?= PASSWORD_MAX ?>" required />
    </div>

    <div class="role-selection flex-row">

        <div>
            <input class="no-display" type="radio" name="role" id="register_role_project_manager"
                value="<?= Role::PROJECT_MANAGER->value ?>">

            <!-- Project Manager Role Button -->
            <button id="project_manager_role_button" type="button" class="role-button unset-button">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'manager_w.svg' ?>" alt="Register as Project Manager"
                        title="Register as Project Manager" height="20">
                    <p>Project Manager</p>
                </div>
            </button>
        </div>

        <div>
            <input class="no-display" type="radio" name="role" id="register_role_worker" value="<?= Role::WORKER->value ?>">

            <!-- Worker Role Button -->
            <button id="worker_role_button" type="button" class="role-button unset-button">
                <div class="text-w-icon">
                    <img src="<?= ICON_PATH . 'worker_w.svg' ?>" alt="Register as Worker" title="Register as Worker"
                        height="20">
                    <p>Worker</p>
                </div>
            </button>
        </div>

    </div>

    <button id="register_button" type="submit" class="blue-bg white-text">
        <div class="text-w-icon">
            <img src="<?= ICON_PATH . 'login_w.svg' ?>" alt="Register An Account" title="Register An Account"
                height="20">
            <h3>Register</h3>
        </div>
    </button>
</form>