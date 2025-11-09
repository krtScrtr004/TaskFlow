<?php
use App\Middleware\Csrf;

function hiddenCsrfInput(): string
{
    $token = Csrf::get();
    return <<<HTML
    <input type="hidden" name="csrf_token" id="csrf_token" value="$token">
    HTML;
}
