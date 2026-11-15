<?php

declare(strict_types=1);

$runtime = getenv('PINT_PHP_BINARY') ?: (PHP_VERSION_ID >= 80300 ? PHP_BINARY : null);

if ($runtime === null) {
    fwrite(STDERR, "Pint requires PHP 8.3+. Set PINT_PHP_BINARY to that PHP executable. Package tests and Larastan run on PHP 7.4.\n");
    exit(1);
}

$pint = __DIR__ . '/../tools/pint/vendor/bin/pint';

if (! is_file($pint)) {
    fwrite(STDERR, "Install the isolated formatter with PHP 8.3+: composer install --working-dir=tools/pint\n");
    exit(1);
}

/** @var array<int, string> $argv */
$process = proc_open(array_merge([$runtime, $pint, '--config=' . __DIR__ . '/../pint.json'], array_slice($argv, 1)), [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));

if (! is_resource($process)) {
    throw new RuntimeException('Unable to start Pint.');
}

exit(proc_close($process));
