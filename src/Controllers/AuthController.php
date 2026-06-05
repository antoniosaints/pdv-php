<?php

declare(strict_types=1);

namespace Pdv\Controllers;

use Pdv\Auth\AuthService;
use Pdv\Http\Request;
use Pdv\Http\Response;
use Pdv\Security\Csrf;
use Pdv\View\View;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthService $auth,
        private readonly Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function showLogin(Request $request, array $vars = []): Response
    {
        if (! $this->auth->hasUsers()) {
            return Response::redirect('/setup/admin');
        }

        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return $this->view->render('auth/login', [
            'title' => 'Entrar',
            'currentRoute' => '/login',
            'csrfToken' => $this->csrf->token(),
            'redirect' => $request->query('redirect', '/dashboard') ?: '/dashboard',
            'email' => '',
            'error' => null,
        ]);
    }

    /** @param array<string, string> $vars */
    public function login(Request $request, array $vars = []): Response
    {
        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->loginFailed($request, 'Sessão expirada. Recarregue a página e tente novamente.');
        }

        $email = $request->post('email', '') ?? '';
        $password = $request->post('password', '') ?? '';
        $redirect = $this->safeRedirect($request->post('redirect', '/dashboard') ?? '/dashboard');

        if ($this->auth->attempt($email, $password, $request->clientIp())) {
            return Response::redirect($redirect);
        }

        return $this->loginFailed($request, 'E-mail ou senha inválidos.');
    }

    /** @param array<string, string> $vars */
    public function logout(Request $request, array $vars = []): Response
    {
        if ($this->csrf->validate($request->post('_token'))) {
            $this->auth->logout();
        }

        return Response::redirect('/');
    }

    /** @param array<string, string> $vars */
    public function showSetup(Request $request, array $vars = []): Response
    {
        if ($this->auth->hasUsers()) {
            return Response::redirect('/login');
        }

        return $this->view->render('auth/setup', [
            'title' => 'Criar administrador',
            'currentRoute' => '/setup/admin',
            'csrfToken' => $this->csrf->token(),
            'error' => null,
            'name' => '',
            'email' => '',
        ]);
    }

    /** @param array<string, string> $vars */
    public function storeSetup(Request $request, array $vars = []): Response
    {
        if ($this->auth->hasUsers()) {
            return Response::redirect('/login');
        }

        if (! $this->csrf->validate($request->post('_token'))) {
            return $this->setupFailed('Sessão expirada. Recarregue a página e tente novamente.', $request);
        }

        $name = $request->post('name', '') ?? '';
        $email = $request->post('email', '') ?? '';
        $password = $request->post('password', '') ?? '';
        $confirmation = $request->post('password_confirmation', '') ?? '';

        $error = $this->validateSetup($name, $email, $password, $confirmation);

        if ($error !== null) {
            return $this->setupFailed($error, $request);
        }

        try {
            $this->auth->createUser($name, $email, $password, 'admin');
        } catch (Throwable) {
            return $this->setupFailed('Não foi possível criar o administrador. Verifique se o e-mail já existe.', $request);
        }

        $this->auth->attempt($email, $password, $request->clientIp());

        return Response::redirect('/dashboard');
    }

    private function loginFailed(Request $request, string $message): Response
    {
        return $this->view->render('auth/login', [
            'title' => 'Entrar',
            'currentRoute' => '/login',
            'csrfToken' => $this->csrf->token(),
            'redirect' => $this->safeRedirect($request->post('redirect', $request->query('redirect', '/dashboard') ?? '/dashboard') ?? '/dashboard'),
            'email' => $request->post('email', '') ?? '',
            'error' => $message,
        ], 422);
    }

    private function setupFailed(string $message, Request $request): Response
    {
        return $this->view->render('auth/setup', [
            'title' => 'Criar administrador',
            'currentRoute' => '/setup/admin',
            'csrfToken' => $this->csrf->token(),
            'error' => $message,
            'name' => $request->post('name', '') ?? '',
            'email' => $request->post('email', '') ?? '',
        ], 422);
    }

    private function validateSetup(string $name, string $email, string $password, string $confirmation): ?string
    {
        if (strlen(trim($name)) < 2) {
            return 'Informe o nome do administrador.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'Informe um e-mail válido.';
        }

        if (strlen($password) < 8) {
            return 'Use uma senha com pelo menos 8 caracteres.';
        }

        if ($password !== $confirmation) {
            return 'A confirmação de senha não confere.';
        }

        return null;
    }

    private function safeRedirect(string $redirect): string
    {
        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return '/dashboard';
        }

        return $redirect;
    }
}
