<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit;

/**
 * @phpstan-type ParsedNrc array{state: string, stateName: string, township: string, type: string, typeName: string, number: string, language: 'en'|'mm'}
 * @phpstan-type ValidationResult array{valid: bool, errors: array<int, array{code: string, message: string}>}
 * @phpstan-type ReadResult array{parsed: ParsedNrc|null, validation: ValidationResult}
 */
final class NrcParser
{
    private const MESSAGES = [
        'INVALID_INPUT_TYPE' => 'NRC must be a string.',
        'INVALID_FORMAT' => 'Expected state/township(type) followed by digits.',
        'INVALID_STATE' => 'Unknown NRC state number.',
        'INVALID_TOWNSHIP' => 'Township does not belong to the state.',
        'INVALID_TYPE' => 'Expected N (နိုင်), E (ဧည့်), P (ပြု), or M (သ).',
        'INVALID_SERIAL_LENGTH' => 'NRC serial must contain exactly six digits.',
    ];

    /**
     * @param  mixed  $input
     * @return ReadResult
     */
    public static function read($input): array
    {
        $codes = [];

        if (! is_string($input)) {
            return self::result(null, ['INVALID_INPUT_TYPE']);
        }

        $space = '[\\x{0009}-\\x{000d} \\x{00a0}\\x{1680}\\x{2000}-\\x{200a}\\x{2028}\\x{2029}\\x{202f}\\x{205f}\\x{3000}]';
        $pattern = '/\A' . $space . '*([0-9၀-၉]+)'
            . $space . '*\/' . $space . '*([A-Za-z\x{1000}-\x{109f}]+)'
            . $space . '*\(' . $space . '*([A-Za-z\x{1000}-\x{109f}]+)'
            . $space . '*\)' . $space . '*([0-9၀-၉]+)' . $space . '*\z/u';

        if (mb_strlen($input) > 256 || ! preg_match($pattern, $input, $match)) {
            return self::result(null, ['INVALID_FORMAT']);
        }

        $state = NrcData::numbers($match[1], 'en');
        $township = NrcData::township($state, $match[2]);
        $type = NrcData::type($match[3]);
        $number = NrcData::numbers($match[4], 'en');

        if (NrcData::group($state) === null) {
            $codes[] = 'INVALID_STATE';
        } elseif ($township === null) {
            $codes[] = 'INVALID_TOWNSHIP';
        }

        if ($type === null) {
            $codes[] = 'INVALID_TYPE';
        }

        if (strlen($number) !== 6) {
            $codes[] = 'INVALID_SERIAL_LENGTH';
        }

        if ($codes) {
            return self::result(null, $codes);
        }

        assert($township !== null && $type !== null);

        return self::result([
            'state' => $state,
            'stateName' => NrcData::stateName($state, $township['en'], 'en'),
            'township' => $township['en'],
            'type' => $type['en'],
            'typeName' => $type['label'],
            'number' => $number,
            'language' => preg_match('/\A[A-Za-z]+\z/', $match[2]) ? 'en' : 'mm',
        ], []);
    }

    /**
     * @param  ParsedNrc|null  $parsed
     * @param  array<int, string>  $codes
     * @return ReadResult
     */
    private static function result(?array $parsed, array $codes): array
    {
        return [
            'parsed' => $parsed,
            'validation' => [
                'valid' => count($codes) === 0,
                'errors' => array_map(function ($code) {
                    return [
                        'code' => $code,
                        'message' => self::MESSAGES[$code],
                    ];
                }, $codes),
            ],
        ];
    }
}
