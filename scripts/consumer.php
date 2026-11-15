<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temp = sys_get_temp_dir() . '/nrc-composer-consumer-' . uniqid();
mkdir($temp);

$composer = getenv('COMPOSER_BINARY');

if (! $composer) {
    foreach (explode(PATH_SEPARATOR, getenv('PATH') ?: '') as $entry) {
        if (is_file($entry . '/composer')) {
            $composer = $entry . '/composer';
            break;
        }
    }
}

if (! $composer) {
    throw new RuntimeException('Composer executable not found. Set COMPOSER_BINARY.');
}

/** @param array<int, string> $args */
function runConsumerCommand(array $args, string $directory): void
{
    $process = proc_open($args, [
        0 => ['pipe', 'r'],
        1 => STDOUT,
        2 => STDERR,
    ], $pipes, $directory);

    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start consumer command.');
    }

    fclose($pipes[0]);

    if (proc_close($process) !== 0) {
        throw new RuntimeException('Consumer command failed.');
    }
}

function removeConsumerDirectory(string $directory): void
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($directory);
}

try {
    runConsumerCommand([PHP_BINARY, $composer, 'archive', '--format=zip', '--dir=' . $temp, '--file=package', '--no-interaction'], $root);

    $zip = new ZipArchive;
    $zip->open($temp . '/package.zip');

    foreach (['config/nrc-kit.php', 'resources/dist/nrc-selector.js', 'resources/views/components/nrc-selector.blade.php', 'src/Nrc.php'] as $path) {
        if ($zip->locateName($path) === false) {
            throw new RuntimeException('Missing distribution file: ' . $path);
        }
    }

    if ($zip->locateName('data/nrc.json') !== false) {
        throw new RuntimeException('The distribution must use the PHP config without a JSON dataset.');
    }

    mkdir($temp . '/package');
    $zip->extractTo($temp . '/package');
    $zip->close();

    $consumer = [
        'name' => 'nrc/clean-consumer',
        'repositories' => [
            [
                'type' => 'path',
                'url' => $temp . '/package',
                'options' => [
                    'symlink' => false,
                    'versions' => ['thuraaung2493/laravel-nrc-kit' => '1.0.0'],
                ],
            ],
        ],
        'require' => ['thuraaung2493/laravel-nrc-kit' => '*'],
        'config' => ['audit' => ['block-insecure' => false]],
    ];

    $laravel = getenv('LARAVEL_VERSION');

    if ($laravel) {
        $consumer['require']['illuminate/support'] = $laravel;
    }

    mkdir($temp . '/app');
    file_put_contents($temp . '/app/composer.json', json_encode($consumer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    runConsumerCommand([PHP_BINARY, $composer, 'install', '--no-dev', '--prefer-dist', '--no-interaction'], $temp . '/app');

    file_put_contents(
        $temp . '/app/smoke.php',
        <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';

use Thuraaung\NrcKit\Nrc;

$parsed = Nrc::from('12/LaMaNa(N)001234')->parse('mm');

if ($parsed === null || $parsed['language'] !== 'mm' || $parsed['stateName'] !== 'ရန်ကုန်တိုင်း' || $parsed['typeName'] !== 'နိုင်ငံသား') {
    throw new RuntimeException('Localized parsed metadata did not match.');
}

if (Nrc::from('၁၂/လမန(ဧည့်)၀၀၁၂၃၄')->toEnglish() !== '12/LaMaNa(E)001234' || Nrc::from('12/LaMaNa(M)001234')->toMyanmar() !== '၁၂/လမန(သ)၀၀၁၂၃၄') {
    throw new RuntimeException('New type conversion did not match.');
}

if (Nrc::townships(10)[0] !== ['en' => 'MaLaMa', 'mm' => 'မလမ'] || Nrc::states('en')[0] !== ['stateNo' => 1, 'name' => 'Kachin']) {
    throw new RuntimeException('Lookup API shapes did not match.');
}

if (method_exists(Nrc::class, 'townshipOptions') || !file_exists(__DIR__ . '/vendor/thuraaung2493/laravel-nrc-kit/resources/dist/nrc-selector.js')) {
    throw new RuntimeException('Distribution API/assets did not match.');
}
PHP
    );
    runConsumerCommand([PHP_BINARY, 'smoke.php'], $temp . '/app');

    echo "Clean Composer archive consumer and bundled assets passed.\n";
} finally {
    removeConsumerDirectory($temp);
}
