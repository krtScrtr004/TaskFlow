<?php

namespace App\Auth;

use App\Core\Me;
use App\Core\Session;
use App\Core\UUID;
use App\Entity\User;
use App\Middleware\Csrf;

class SessionAuth
{
    private function __construct() {}

    public static function hasAuthorizedSession(): bool
    {
        return (Me::getInstance() !== null) && Session::isSet() && Session::has('userData');
    }

    public static function setAuthorizedSession(User|array $user): void
    {
        // Always ensure session is started FIRST
        if (!Session::isSet()) {
            Session::create();
        }

        // Always re-instantiate Me with the new user data
        // This ensures the Me instance is always up-to-date
        Me::instantiate($user);

        // Store user data in session
        $currentUser = Me::getInstance();
        Session::set('userData', [
            'id' => $currentUser->getId(),
            'publicId' => UUID::toString($currentUser->getPublicId()),
            'firstName' => $currentUser->getFirstName(),
            'middleName' => $currentUser->getMiddleName(),
            'lastName' => $currentUser->getLastName(),
            'gender' => $currentUser->getGender()->value,
            'birthDate' => $currentUser->getBirthDate()?->format('Y-m-d'),
            'role' => $currentUser->getRole()->value,
            'jobTitles' => implode(',', $currentUser->getJobTitles()->toArray()),
            'contactNumber' => $currentUser->getContactNumber(),
            'email' => $currentUser->getEmail(),
            'bio' => $currentUser->getBio(),
            'profileLink' => $currentUser->getProfileLink(),
            'createdAt' => $currentUser->getCreatedAt()->format('Y-m-d H:i:s'),
            'additionalInfo' => $currentUser->getAdditionalInfo()
        ]);
    }
}