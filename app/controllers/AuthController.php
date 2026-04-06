<?php

namespace App\Controllers;

require_once __DIR__ . '/../services/AuthService.php';

class AuthController extends BaseController
{
    private $auth;

    public function __construct(?\AuthService $auth = null)
    {
        parent::__construct();
        $this->auth = $auth ?: new \AuthService();
    }

    public function login(): void
    {
        $error = null;

        if ($this->request->isPost()) {
            $username = trim($this->input('username', ''));
            $password = $this->input('password', '');

            if ($username !== '' && $password !== '') {
                // The Controller calls the Service
                if ($this->auth->login($username, $password)) {
                    $role = $_SESSION['user']['role'] ?? null;
                    $this->redirect(role_landing_page($role));
                }
                $error = 'Invalid credentials or inactive account.';
            } else {
                $error = 'Please enter your username and password.';
            }
        }

        require __DIR__ . '/../../views/auth/login.php';
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('login.php');
    }
}
