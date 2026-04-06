<?php

namespace App\Controllers;

class NotFoundController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'Page Not Found';
        http_response_code(404);

        require_once __DIR__ . '/../../includes/header.php';
        require __DIR__ . '/../../views/errors/404.php';
        require_once __DIR__ . '/../../includes/footer.php';
    }
}
