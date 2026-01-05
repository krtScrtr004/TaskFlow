<?php

namespace App\Auth;

use App\Core\Me;
use App\Core\Session;
use App\Core\UUID;
use App\Dependent\ProjectManager;
use App\Dependent\Worker;

class SessionAuth
{
    private function __construct() {}

    public static function hasAuthorizedSession(): bool
    {
        return (Me::getInstance() !== null) && Session::isSet() && Session::has('userData');
    }

    public static function setAuthorizedSession(ProjectManager|Worker|array $user): void
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
            'id'                => $currentUser->getId(),
            'publicId'          => UUID::toString($currentUser->getPublicId()),
            'firstName'         => $currentUser->getFirstName(),
            'middleName'        => $currentUser->getMiddleName(),
            'lastName'          => $currentUser->getLastName(),
            'email'             => $currentUser->getEmail(),
            'gender'            => $currentUser->getGender()->value,
            'role'              => $currentUser->getRole()->value,
            'jobTitles'         => $currentUser->getJobTitles()->toArray(),
            'contactNumber'     => $currentUser->getContactNumber(),
            'profileLink'       => $currentUser->getProfileLink(),
            'bio'               => $currentUser->getBio(),
            'birthDate'         => $currentUser->getBirthDate()->format('Y-m-d'),
            'createdAt'         => $currentUser->getCreatedAt(),
            'confirmedAt'       => $currentUser->getConfirmedAt(),
            'deletedAt'         => $currentUser->getDeletedAt(),
            'additionalInfo'    => $currentUser->getAdditionalInfo(),
        ]);
    }
}