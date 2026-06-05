<?php

declare(strict_types=1);

namespace Pdv\Http\Middleware;

use Pdv\Auth\AuthService;
use Pdv\Http\Request;
use Pdv\Http\Response;

final class AuthMiddleware
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /**
     * @param callable(): Response $next
     * @param list<string> $roles
     */
    public function handle(Request $request, callable $next, array $roles = []): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            return Response::redirect('/login?redirect=' . rawurlencode($request->path()));
        }

        if ($roles !== [] && ! in_array((string) $user['role'], $roles, true)) {
            return Response::html(
                '<main class="shell shell--narrow"><section class="panel form-panel"><p class="eyebrow">403</p><h1>Acesso negado</h1><p>Seu usuário não tem permissão para acessar esta área.</p><a class="button" href="/dashboard">Voltar ao painel</a></section></main>',
                403
            );
        }

        return $next();
    }
}
