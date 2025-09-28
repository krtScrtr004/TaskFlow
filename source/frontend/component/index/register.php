<form id="register_form" class="index-form flex-col" action="" method="POST">
    <div class="separated-name-input flex-row">
        <input
            type="text"
            id="first_name"
            name="first_name"
            min="1"
            max="50"
            placeholder="First Name"
            autocomplete="on"
            required>

        <input
            type="text"
            id="middle_name"
            name="middle_name"
            min="1"
            max="50"
            placeholder="Middle Name"
            autocomplete="on"
            required>
    </div>

    <input
        type="text"
        id="last_name"
        name="last_name"
        min="1"
        max="50"
        placeholder="Last Name"
        autocomplete="on"
        required>

    <div class="gender-radio flex-row">
        <div class="flex-row-reverse flex-child-center-h">
            <label for="gender_male">Male</label>
            <input
                type="radio"
                name="gender"
                id="gender_male"
                value="male"
                required>
        </div>

        <div class="flex-row-reverse flex-child-center-h">
            <label for="gender_female">Female</label>
            <input
                type="radio"
                name="gender"
                id="gender_female"
                value="female"
                required>
        </div>
    </div>

    <input
        type="tel"
        name="phone"
        id="phone"
        placeholder="Phone Number"
        pattern="[0-9]{10,15}"
        minlength="10"
        maxlength="15"
        required>

    <div class="birth-date flex-col">
        <label class="first-col black-text" for="day_of_birth">Date of Birth</label>
        <div class="birth-date-inputs second-col flex-col">
            <div class="invalid-date-result-box">
                <p class="red-text"></p>
            </div>

            <div class="date-inputs flex-row">
                <?php
                $MAX_DAY_COUNT  =   31;
                $CURRENT_YEAR   =   (int) date('Y');
                $OLDEST_YEAR    =   1940;
                $months         =   [
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

                <select name="day_of_birth" id="day_of_birth">
                    <?php for ($i = 0; $i < $MAX_DAY_COUNT; ++$i): ?>
                        <option class="black-text" value="<?= $i + 1 ?>">
                            <?= $i + 1 ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select name="month_of_birth" id="month_of_birth">
                    <?php foreach ($months as $month): ?>
                        <option class="black-text" value="<?= $month ?>">
                            <?= $month ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="year_of_birth" id="year_of_birth">
                    <?php for ($i = $CURRENT_YEAR; $i >= $OLDEST_YEAR; --$i): ?>
                        <option class="black-text" value="<?= $i ?>">
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="password-toggle-wrapper">
        <img
            class="absolute"
            src="<?= ICON_PATH . 'show_b.svg'; ?>"
            alt="Show password"
            title="Show password"
            width="18" height="18" />

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Please enter your password here"
            min="8"
            max="255"
            required />
    </div>

    <input
        type="email"
        name="email"
        id="email"
        placeholder="Email"
        min="8"
        max="255"
        autocomplete="on"
        required>

    <button id="register_button" type="button" class="blue-bg white-text">
        Register
    </button>
</form>