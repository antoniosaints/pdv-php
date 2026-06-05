<?php

declare(strict_types=1);

namespace Pdv\View;

use Pdv\Http\Response;
use RuntimeException;

final class View
{
    public function __construct(private readonly string $basePath)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], int $status = 200): Response
    {
        $templateFile = $this->basePath . DIRECTORY_SEPARATOR . $template . '.php';
        $layoutFile = $this->basePath . DIRECTORY_SEPARATOR . 'layout.php';

        if (! is_file($templateFile)) {
            throw new RuntimeException("Template not found: {$template}");
        }

        if (! is_file($layoutFile)) {
            throw new RuntimeException('Layout template not found.');
        }

        $title = $data['title'] ?? 'PDV Estoque';
        $currentRoute = $data['currentRoute'] ?? '';
        $authUser = $data['authUser'] ?? null;
        $appName = $_ENV['APP_NAME'] ?? 'PDV Estoque';

        extract($data, EXTR_SKIP);

        ob_start();
        require $templateFile;
        $content = (string) ob_get_clean();

        ob_start();
        require $layoutFile;

        return Response::html((string) ob_get_clean(), $status);
    }
}
