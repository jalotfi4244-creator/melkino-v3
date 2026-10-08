<?php
declare(strict_types=1);
namespace Melkino\Http\Controllers;
/** The public V2 router renders the SAME login document as every messenger entry. */
final class AuthController
{
    public function render(): string
    {
        ob_start();
        require MELKINO_ROOT . '/messenger-login-page.php';
        return (string)ob_get_clean();
    }
}
