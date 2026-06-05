<?php

declare(strict_types=1);

namespace Pdv\Auth;

use PDO;
use Pdv\Support\Env;

final class AuthService
{
    /** @var array<string, mixed>|null */
    private ?array $cachedUser = null;

    public function __construct(private readonly PDO $pdo, bool $startSession = true)
    {
        if ($startSession) {
            $this->startSession();
        }
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(Env::get('SESSION_NAME', 'pdv_session') ?? 'pdv_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => Env::bool('SESSION_SECURE', false),
            'httponly' => true,
            'samesite' => Env::get('SESSION_SAMESITE', 'Lax') ?? 'Lax',
        ]);
        session_start();
    }

    public function hasUsers(): bool
    {
        $count = $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        return (int) $count > 0;
    }

    public function createUser(string $name, string $email, string $password, string $role = 'admin'): int
    {
        $email = strtolower(trim($email));
        $role = trim($role) !== '' ? trim($role) : 'admin';
        $now = gmdate('c');

        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, active, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :role, 1, :created_at, :updated_at)'
        );
        $statement->execute([
            'name' => trim($name),
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function attempt(string $email, string $password, ?string $ipAddress = null): bool
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);

        $user = $statement->fetch();

        if (! is_array($user) || ! password_verify($password, (string) $user['password_hash'])) {
            $this->audit(null, 'auth.login_failed', 'user', null, ['email' => strtolower(trim($email))], $ipAddress);
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $this->cachedUser = $user;

        $this->pdo->prepare('UPDATE users SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'last_login_at' => gmdate('c'),
                'updated_at' => gmdate('c'),
                'id' => (int) $user['id'],
            ]);

        $this->audit((int) $user['id'], 'auth.login_success', 'user', (string) $user['id'], null, $ipAddress);

        return true;
    }

    public function logout(): void
    {
        $user = $this->user();

        if ($user !== null) {
            $this->audit((int) $user['id'], 'auth.logout', 'user', (string) $user['id']);
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->cachedUser = null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (! is_int($userId) && ! ctype_digit((string) $userId)) {
            return null;
        }

        $statement = $this->pdo->prepare('SELECT id, name, email, role, active, last_login_at, created_at, updated_at FROM users WHERE id = :id AND active = 1 LIMIT 1');
        $statement->execute(['id' => (int) $userId]);

        $user = $statement->fetch();

        if (! is_array($user)) {
            unset($_SESSION['user_id']);
            return null;
        }

        $this->cachedUser = $user;

        return $user;
    }

    /** @param array<string, mixed>|null $metadata */
    private function audit(?int $actorUserId, string $action, string $entityType, ?string $entityId, ?array $metadata = null, ?string $ipAddress = null): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, metadata_json, ip_address, created_at)
             VALUES (:actor_user_id, :action, :entity_type, :entity_id, :metadata_json, :ip_address, :created_at)'
        );
        $statement->execute([
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata_json' => $metadata === null ? null : json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'ip_address' => $ipAddress,
            'created_at' => gmdate('c'),
        ]);
    }
}
