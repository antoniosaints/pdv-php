<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\View\View;

final class HomeController
{
    public function __construct(private readonly View $view)
    {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        return $this->view->render('home', [
            'title' => 'Painel inicial',
            'currentRoute' => '/',
        ]);
    }
}
