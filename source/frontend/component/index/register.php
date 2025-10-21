<?php
use App\Enumeration\Role;
?>

<form id="register_form" class="index-form flex-col" action="" method="POST">

    <div class="separated-name-input flex-row">
        <!-- First Name -->
        <input type="text" id="register_first_name" name="register_first_name" min="1" max="50" placeholder="First Name"
            autocomplete="on" required>

        <!-- Middle Name -->
        <input type="text" id="register_middle_name" name="register_middle_name" min="1" max="50"
            placeholder="Middle Name" autocomplete="on" required>
    </div>

    <!-- Last Name -->
    <input type="text" id="register_last_name" name="register_last_name" min="1" max="50" placeholder="Last Name"
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

    <!-- Contact Number -->
    <input type="tel" name="register_contact" id="register_contact" placeholder="Contact Number" pattern="[0-9]{10,15}"
        minlength="11" maxlength="20" required>

    <!-- Birth Date -->
    <div class="birth-date flex-col">
        <label class="first-col" for="day_of_birth">Date of Birth</label>
        <div class="birth-date-inputs second-col flex-col">
            <div class="invalid-date-result-box">
                <p class="red-text"></p>
            </div>

            <div class="date-inputs flex-row">
                <?php
                $MAX_DAY_COUNT = 31;
                $CURRENT_YEAR = (int) date('Y');
                $OLDEST_YEAR = 1940;
                $months = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];
                ?>

                <!-- Day of Birth -->
                <select name="day_of_birth" id="day_of_birth">
                    <?php for ($i = 0; $i < $MAX_DAY_COUNT; ++$i): ?>
                        <option value="<?= $i + 1 ?>">
                            <?= $i + 1 ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <!-- Month of Birth -->
                <select name="month_of_birth" id="month_of_birth">
                    <?php foreach ($months as $month): ?>
                        <option value="<?= $month ?>">
                            <?= $month ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Year of Birth -->
                <select name="year_of_birth" id="year_of_birth">
                    <?php for ($i = $CURRENT_YEAR; $i >= $OLDEST_YEAR; --$i): ?>
                        <option value="<?= $i ?>">
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Job Titles -->
    <input type="text" name="register_job_titles" id="register_job_titles" placeholder="Job Titles (comma separated)"
        min="1" max="255" autocomplete="on" required>

    <!-- Email -->
    <input type="email" name="register_email" id="register_email" placeholder="Email" min="8" max="255"
        autocomplete="on" required>

    <!-- Password -->
    <div class="password-toggle-wrapper">
        <img class="absolute" src="<?= ICON_PATH . 'show_w.svg'; ?>" alt="Show password" title="Show password"
            width="18" height="18" />

        <input type="password" name="register_password" id="register_password"
            placeholder="Please enter your password here" min="8" max="255" required />
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

    <button id="register_button" type="button" class="blue-bg white-text">
        <div class="text-w-icon">
            <img src="<?= ICON_PATH . 'login_w.svg' ?>" alt="Register An Account" title="Register An Account"
                height="20">
            <h3>Register</h3>
        </div>
    </button>
</form>