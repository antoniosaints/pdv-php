<?php

declare(strict_types=1);

namespace Pdv\Database;

use PDO;

interface Migration
{
    public function up(PDO $pdo, string $driver): void;
}
