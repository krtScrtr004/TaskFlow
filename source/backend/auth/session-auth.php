<?php

namespace App\Auth;

use App\Core\Me;
use App\Core\Session;
use App\Core\UUID;
use App\Entity\User;

class SessionAuth
{
    private function __construct() {}

    public static function hasAuthorizedSession(): bool
    {
        return (Me::getInstance() !== null) && Session::isSet() && Session::has('userId');
    }

    public static function setAuthorizedSession(User|array $user): void
    {
        // Always ensure session is started FIRST
        if (!Session::isSet()) {
            Session::create();
        }

        // Instantiate Me if not already done
        if (Me::getInstance() === null) {
            Me::instantiate($user);
        }

        // Store user data in session
        $currentUser = Me::getInstance();
        Session::set('userId', $currentUser->getId());
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

    public static function restoreSession(): void
    {
        // Ensure session is started
        if (!Session::isSet()) {
            Session::create();
        }

        // Restore Me instance from session data if it exists
        if (Session::has('userData') && Me::getInstance() === null) {
            $userData = Session::get('userData');
            Me::instantiate($userData);
        }
    }

    public static function destroySession(): void
    {
        Session::destroy();
    }
}