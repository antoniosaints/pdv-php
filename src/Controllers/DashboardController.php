<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Security\Csrf;
use Pdv\View\View;

final class DashboardController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        $user = $this->auth->user();

        return $this->view->render('dashboard/index', [
            'title' => 'Dashboard',
            'currentRoute' => '/dashboard',
            'authUser' => $user,
            'csrfToken' => $this->csrf->token(),
            'cards' => [
                ['label' => 'Status', 'value' => 'Base pronta', 'hint' => 'Instalação, sessão e banco conectados.'],
                ['label' => 'Próximo módulo', 'value' => 'Catálogo', 'hint' => 'Produtos, variantes, serviços e códigos de barras.'],
                ['label' => 'Banco', 'value' => 'SQLite', 'hint' => 'PDO com migrations e caminho para MySQL.'],
            ],
        ]);
    }
}
