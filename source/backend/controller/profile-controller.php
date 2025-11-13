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
                header('Location: ' . REDIRECT_PATH . 'login');
                exit();
            }

            require_once VIEW_PATH . 'profile.php';
        } catch (NotFoundException $e) {
            ErrorController::notFound();
        } catch (ForbiddenException $e) {
            ErrorController::forbidden();
        }
    }
}