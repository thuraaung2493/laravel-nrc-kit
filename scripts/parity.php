<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Thuraaung\NrcKit\Nrc;
use Thuraaung\NrcKit\NrcData;

/** @var array<int, string> $argv */
if (in_array('--data', $argv, true)) {
    echo json_encode(NrcData::snapshot(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    exit;
}

if (in_array('--metadata', $argv, true)) {
    echo json_encode([
        'states' => NrcData::stateNames(),
        'types' => NrcData::types(),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    exit;
}

$json = stream_get_contents(STDIN);

if ($json === false) {
    throw new RuntimeException('Unable to read parity inputs.');
}

$inputs = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$output = [];

foreach ($inputs as $input) {
    $value = Nrc::from($input);

    $output[] = [
        'validation' => $value->validate(),
        'auto' => $value->parse(),
        'en' => $value->parse('en'),
        'mm' => $value->parse('mm'),
        'english' => $value->isValid() ? $value->toEnglish() : null,
        'myanmar' => $value->isValid() ? $value->toMyanmar() : null,
    ];
}

$options = [];

foreach (range(1, 15) as $state) {
    $options[] = [
        'en' => Nrc::townships($state),
        'mm' => Nrc::townships($state, 'mm'),
        'pairs' => Nrc::townships($state),
    ];
}

echo json_encode([
    'cases' => $output,
    'options' => $options,
    'states' => [
        'en' => Nrc::states('en'),
        'mm' => Nrc::states('mm'),
    ],
    'types' => Nrc::types('mm'),
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
