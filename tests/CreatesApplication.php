<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (!$app->environment('testing')) {
            throw new \RuntimeException('Tests must run with APP_ENV=testing.');
        }

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                "Unsafe test database [{$connection}:{$database}]. Tests require sqlite :memory:."
            );
        }

        return $app;
    }
}
