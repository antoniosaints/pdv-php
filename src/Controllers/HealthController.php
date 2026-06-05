<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Security\Csrf;
use Pdv\Support\HealthCheck;
use Pdv\View\View;

final class HealthController
{
    public function __construct(
        private readonly View $view,
        private readonly HealthCheck $health,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        return $this->view->render('health/index', [
            'title' => 'Health',
            'currentRoute' => '/health',
            'authUser' => $this->auth->user(),
            'csrfToken' => $this->csrf->token(),
            'checks' => $this->health->report(),
            'passes' => $this->health->passes(),
        ], $this->health->passes() ? 200 : 503);
    }
}
