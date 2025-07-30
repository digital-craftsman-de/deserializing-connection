<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

abstract class ConnectionTestCase extends TestCase
{
    protected Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * @psalm-suppress InvalidArgument
         */
        $this->connection = DriverManager::getConnection([
            'user' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'host' => getenv('DB_HOST'),
            'dbname' => getenv('DB_DATABASE'),
            'driver' => 'pdo_pgsql',
        ]);
    }
}
