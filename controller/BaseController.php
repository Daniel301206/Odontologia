<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

/** Herramientas comunes para controladores web. */
abstract class BaseController
{
    protected function requireAuthentication(): void
    {
        require_login();
    }

    protected function verifyCsrf(): void
    {
        check_csrf();
    }

    protected function redirect(string $location): never
    {
        header('Location: ' . $location);
        exit;
    }
}
