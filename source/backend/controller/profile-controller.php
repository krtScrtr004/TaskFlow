<?php

namespace App\Controller;

use App\Auth\SessionAuth;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Interface\Controller;

class ProfileController implements Controller {
    /**
     * Displays the profile page for the currently authenticated user.
     *
     * This method handles authentication and view rendering with the following behavior:
     * - Checks for an authorized session via SessionAuth::hasAuthorizedSession()
     * - If the session is not authorized, sends a Location header to REDIRECT_PATH . 'login' and exits immediately
     * - Includes the profile view file located at VIEW_PATH . 'profile.php'
     * - Catches NotFoundException and delegates to ErrorController::notFound()
     * - Catches ForbiddenException and delegates to ErrorController::forbidden()
     *
     * Notes:
     * - This method produces side effects (sending HTTP headers, including a view, and possibly exiting).
     * - Exceptions mentioned above are handled internally and not re-thrown.
     *
     * @return void
     */
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