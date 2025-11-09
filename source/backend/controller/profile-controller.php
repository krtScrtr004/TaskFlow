<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;

class ProfileController implements Controller {
    public static function index(): void 
    {
        try {   
            if (!SessionAuth::hasAuthorizedSession()) {
                throw new ForbiddenException('User session is not authorized to perform this action.');
            }

            require_once VIEW_PATH . 'profile.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}