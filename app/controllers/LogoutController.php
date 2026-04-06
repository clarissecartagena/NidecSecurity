<?php

namespace App\Controllers;

class LogoutController extends BaseController
{
    public function index(): void
    {
        auth_logout();
        $this->redirect(app_url('login.php'));
    }
}
