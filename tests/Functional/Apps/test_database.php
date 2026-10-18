<?php

/*
 * The test apps use SQLite by default. Set EASYADMIN_TEST_DATABASE_URL to run them on
 * another database server (see the tests-db target of the Makefile). The URL is read when
 * compiling the container, which the test bootstrap does on every run.
 */
return static function (string $appName): array {
    $databaseUrl = $_SERVER['EASYADMIN_TEST_DATABASE_URL'] ?? getenv('EASYADMIN_TEST_DATABASE_URL');

    if (false === $databaseUrl || '' === $databaseUrl) {
        return [
            'driver' => 'pdo_sqlite',
            // ParaTest workers share the compiled container but each one needs its own database
            'path' => '%kernel.cache_dir%/test_database%env(TEST_TOKEN)%.sqlite',
        ];
    }

    return [
        'url' => $databaseUrl,
        // each test app, and each ParaTest worker, needs its own database
        'dbname_suffix' => sprintf('_%s%%env(TEST_TOKEN)%%', $appName),
    ];
};
