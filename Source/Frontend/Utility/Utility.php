<?php

/**
 * Converts an integer into its English words representation.
 *
 * This function supports:
 * - Zero and positive integers: maps 0-19 to words, 20-99 using tens with optional hyphenation for units,
 *   and larger values via recursive decomposition using scale words (hundred, thousand, million, billion).
 * - Negative integers: prefixed with "Negative " followed by the words for the absolute value.
 * - Recursive composition: numbers are split by the largest applicable scale and each part is converted
 *   recursively, joining scale words with spaces.
 *
 * Notes:
 * - Units (0-19) and tens (20,30,...,90) are provided by internal lookup tables.
 * - Ties between tens and units use a hyphen (e.g. "twenty-one").
 * - Scales recognized: 100 => "hundred", 1_000 => "thousand", 1_000_000 => "million", 1_000_000_000 => "billion".
 * - The function may return null if conversion cannot be determined (fallback case).
 *
 * @param int $number Integer to convert. May be negative.
 *
 * @return string|null English words representation of the number, or null on failure.
 */
function numberToWords(int $number): ?string
{
    $units = [
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen'
    ];
    
    $tens = [
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
    ];
    
    $scales = [
        1000000000 => 'billion',
        1000000 => 'million',
        1000 => 'thousand',
        100 => 'hundred'
    ];
    
    if ($number < 0) {
        return 'Negative ' . numberToWords(abs($number));
    }
    
    if ($number < 20) {
        return $units[$number];
    }
    
    if ($number < 100) {
        $ten = floor($number / 10);
        $remainder = $number % 10;
        return $remainder ? $tens[$ten] . '-' . $units[$remainder] : $tens[$ten];
    }
    
    foreach ($scales as $scale => $word) {
        if ($number >= $scale) {
            $quotient = floor($number / $scale);
            $remainder = $number % $scale;
            $text = numberToWords($quotient) . ' ' . $word;
            return $remainder ? $text . ' ' . numberToWords($remainder) : $text;
        }
    }
    
    return null;
}

/**
 * Convert a DateTime into a human-readable date string.
 *
 * Produces a string containing the day, full month name, and year.
 * The day is returned as an integer by default (e.g. "1 January 2020").
 * The month is the full English month name (DateTime::format('F')) and
 * the year is the full numeric year (DateTime::format('Y')).
 *
 * Note: The implementation contains a placeholder for converting the day
 * to words; currently the day is returned as a numeric value. Timezone
 * information from the provided DateTime is preserved for formatting.
 *
 * @param DateTime $date DateTime instance to format
 *
 * @return string Formatted date in the form "<day> <Month> <year>", e.g. "21 March 2023"
 */
function dateToWords(DateTime $date): string {
    $day = (int)$date->format('j');
    $month = $date->format('F');
    $year = (int)$date->format('Y');

    // Convert day to words if needed

    return $day . ' ' . $month . ' ' . $year;
}

/**
 * Masks a portion of a string by replacing characters with asterisks ('*').
 *
 * The function splits the input string into characters using str_split and replaces
 * each character in the specified range [offset, offset + limit) with '*'.
 * Behavior details:
 * - If the input string is empty, an empty string is returned.
 * - A negative $offset is interpreted as an offset from the end of the string:
 *     $offset = max(0, $length + $offset).
 * - A negative $limit is treated as 0 (no characters will be masked).
 * - The masking range is capped to the string length:
 *     $end = min($length, $offset + max(0, $limit)).
 * - Note: this implementation uses str_split and is not multi-byte safe. For
 *   UTF-8/multi-byte strings consider using mb_* functions.
 *
 * @param string $string The input string to mask.
 * @param int    $offset The start position (0-based). If negative, counted from the end.
 * @param int    $limit  Number of characters to mask. Negative values are treated as 0.
 *
 * @return string The resulting string with characters in the specified range replaced by '*'.
 */
function maskString(string $string, int $offset, int $limit): string
{
    $return = str_split($string);

    $length = count($return);
    if ($length === 0) {
        return '';
    }

    if ($offset < 0) {
        $offset = max(0, $length + $offset);
    }

    $end = min($length, $offset + max(0, $limit));

    foreach ($return as $i => &$char) {
        if ($i >= $offset && $i < $end) {
            $char = '*';
        }
    }
    unset($char);

    return implode('', $return);
}

/**
 * Simplifies a DateTime instance to a short, human-readable string.
 *
 * This function compares the calendar date of the provided DateTime with the
 * current system date:
 * - If the dates differ, it returns the date portion in 'Y-m-d' format.
 * - If the dates are the same (today), it returns the time portion formatted
 *   as 'h:i A' (12-hour clock with leading zero and AM/PM).
 *
 * Notes:
 * - Comparison is done using the 'Y-m-d' representation. If the provided
 *   DateTime has a different timezone than the system default, the result
 *   may be affected by timezone differences.
 *
 * @param DateTime $date DateTime instance to simplify
 *
 * @return string Either the date in 'Y-m-d' when not today, or the time in 'h:i A' when it is today
 */
function simplifyDate(DateTime $date): string
{
    $dateTime = new DateTime(); 

    $paramDate = $date->format('Y-m-d');
    $currentDateTime = $dateTime->format('Y-m-d');
    if ($paramDate !== $currentDateTime) {
        return $paramDate;
    } else {
        return $date->format('h:i A');
    }
}

/**
 * Formats a DateTime instance into a string using the given format.
 *
 * This function delegates to DateTime::format and:
 * - Uses the provided format string (default 'Y-m-d H:i:s')
 * - Preserves the DateTime object's timezone
 * - Returns the formatted date/time as a string
 *
 * @param DateTime $dateTime DateTime instance to format
 * @param string $format Format string compatible with DateTime::format() (default 'Y-m-d H:i:s')
 *
 * @return string Formatted date/time string
 */
function formatDateTime(DateTime $dateTime, string $format = 'Y-m-d H:i:s'): string {
    return $dateTime->format($format);
}

/**
 * Format a number with thousands separators and optional decimal part.
 *
 * Converts an integer or float into a human-readable string:
 * - Inserts commas as thousands separators every three digits from the right.
 * - Preserves a leading negative sign.
 * - If the input contains a fractional part, the fractional part is computed by
 *   rounding (number - floor(number)) to 2 decimal places and appended (prefixed
 *   with the dot) only if the rounded fractional part is non-zero.
 * - The fractional part is not zero-padded: e.g. 1.5 becomes "1.5" (not "1.50").
 * - For very short string representations (string length < 4) the original string
 *   is returned unchanged (no separators applied).
 *
 * Notes / edge cases:
 * - Decimal extraction uses floor(), so fractional part for negative numbers is
 *   computed correctly (e.g. -1.23 -> fractional part 0.23).
 * - If the rounded fractional part equals 0.00 it will be omitted entirely.
 * - Commas are never inserted before a leading negative sign.
 *
 * @param int|float $number Number to format (integer or floating-point)
 *
 * @return string Formatted number string (with commas and optional fractional part)
 *
 * Examples:
 *  - formatNumber(1234)         => "1,234"
 *  - formatNumber(1234567.89)   => "1,234,567.89"
 *  - formatNumber(-1234.5)      => "-1,234.5"
 *  - formatNumber(123)          => "123"  (unchanged, length < 4)
 */
function formatNumber(int|float $number): string
{
    $stringNumber = (string) $number;
    if (strlen($stringNumber) < 4) {
        return $stringNumber;
    }

    // Search whether the param is float
    $decimalIndex = strpos($stringNumber, '.');
    $decimal = null;
    if (is_int($decimalIndex)) {
        // Extract the decimal part
        $decimal =  round($number - floor($number), 2); 
        // Remove the decimal part
        $stringNumber = substr($stringNumber, 0, $decimalIndex);
    }

    // Apply comma on string number
    $formatted = '';
    for ($i = strlen($stringNumber); $i > 0; ) {
        for ($j = 0; $j < 3; ++$j) {
            if ($i > 0) {
                $formatted = "{$stringNumber[--$i]}$formatted";
            }
        }

        // Check if there is / are more number upfront to apply comma
        // Second condition is to check if there is a negative sign
        if ($i > 0 && (is_numeric($stringNumber[$i - 1]))) {
            $formatted = ",$formatted";
        }
    }

    if ($decimal) {
        // Offset to 1 to remove the leading zero of the decimal part
        $formatted .= substr((string) $decimal, 1, 3);
    }
    return $formatted;
}

/**
 * Builds a displayable full name from given name parts.
 *
 * This function constructs a full name using the following rules:
 * - Starts with the first name followed by a single space
 * - If a non-empty middle name is provided, appends its first character as an initial followed by a dot and a space
 * - Appends the last name after the final space
 *
 * The middleName parameter may be null or an empty string; in those cases no middle initial or extra spacing is added.
 *
 * @param string $firstName Person's first name
 * @param string|null $middleName Person's middle name or null; if non-empty, only the first character is used as an initial
 * @param string $lastName Person's last name
 *
 * @return string Full name in the format "First M. Last" or "First Last" when no middle name/initial is present
 */
function createFullName(string $firstName, ?string $middleName, string $lastName): string
{
    $fullName = $firstName . ' ';

    if ($middleName && strlen($middleName) > 0) {
        $fullName .= $middleName[0] . '. ';
    }

    $fullName .= $lastName;
    return $fullName;
}
