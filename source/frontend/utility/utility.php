<?php

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

function dateToWords(DateTime $date): string {
    $day = (int)$date->format('j');
    $month = $date->format('F');
    $year = (int)$date->format('Y');

    // Convert day to words if needed

    return $day . ' ' . $month . ' ' . $year;
}

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

function formatDateTime(DateTime $dateTime, string $format = 'Y-m-d H:i:s'): string {
    return $dateTime->format($format);
}

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
