<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;

/**
 * @phpstan-type Township array{en: string, mm: string}
 * @phpstan-type StateGroup array{state: string, townships: array<int, Township>}
 * @phpstan-type Option array{value: string, label: string}
 * @phpstan-type NrcType array{en: string, mm: string, label: string, labelMm: string}
 * @phpstan-type Region array{stateNo: int, en: string, mm: string, townships?: array<int, string>}
 * @phpstan-type StateInfo array{stateNo: int, name: string}
 */
final class NrcData
{
    /** @var array{data: array<int, StateGroup>, states: array<int, Region>, types: array<int, NrcType>}|null */
    private static ?array $defaults = null;

    /** @var array<mixed>|null */
    private static ?array $configured = null;

    /** @var array<int, StateGroup>|null */
    private static ?array $reference = null;

    /** @return array{data: array<int, StateGroup>, states: array<int, Region>, types: array<int, NrcType>} */
    private static function defaults(): array
    {
        if (self::$defaults === null) {
            self::$defaults = require __DIR__ . '/../config/nrc-kit.php';
        }

        return self::$defaults;
    }

    /** @return mixed */
    private static function configuration(string $key)
    {
        $container = Container::getInstance();

        if ($container->bound('config')) {
            /** @var Repository $config */
            $config = $container->make('config');

            if ($config->has('nrc-kit.' . $key)) {
                return $config->get('nrc-kit.' . $key);
            }
        }

        return self::defaults()[$key];
    }

    /** @return array<int, StateGroup> */
    public static function snapshot(): array
    {
        return self::defaults()['data'];
    }

    /** @return array<int, StateGroup> */
    public static function all(): array
    {
        $data = self::configuration('data');

        if (! is_array($data)) {
            throw new \InvalidArgumentException('nrc-kit.data must be an array of state groups.');
        }

        if (self::$configured !== $data || self::$reference === null) {
            $reference = self::checkedData($data);
            self::$configured = $data;
            self::$reference = $reference;
        }

        return self::$reference;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, StateGroup>
     */
    private static function checkedData(array $data): array
    {
        $groups = [];
        $states = [];

        foreach ($data as $index => $entry) {
            $path = 'nrc-kit.data.' . $index;
            $group = self::checkedGroup($entry, $path);
            $state = $group['state'];

            if (isset($states[$state])) {
                throw new \InvalidArgumentException($path . ' repeats state ' . $state . '.');
            }

            $states[$state] = true;
            $english = [];
            $myanmar = [];

            foreach ($group['townships'] as $township) {
                $code = strtolower($township['en']);

                if (isset($english[$code]) || isset($myanmar[$township['mm']])) {
                    throw new \InvalidArgumentException($path . ' contains a duplicate township code in state ' . $state . '.');
                }

                $english[$code] = true;
                $myanmar[$township['mm']] = true;
            }

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @param  mixed  $entry
     * @return StateGroup
     */
    private static function checkedGroup($entry, string $path): array
    {
        if (
            ! is_array($entry) || count($entry) !== 2
            || ! isset($entry['state'], $entry['townships'])
            || ! is_string($entry['state'])
            || ! preg_match('/\A[1-9][0-9]*\z/', $entry['state'])
            || ! is_array($entry['townships']) || $entry['townships'] === []
        ) {
            throw new \InvalidArgumentException($path . ' must contain a canonical state string and a non-empty townships array.');
        }

        if (strlen($entry['state']) > 16 || (strlen($entry['state']) === 16 && strcmp($entry['state'], '9007199254740991') > 0)) {
            throw new \InvalidArgumentException($path . '.state must be a safe integer state number.');
        }

        $townships = [];

        foreach ($entry['townships'] as $index => $township) {
            $townships[] = self::checkedTownship($township, $entry['state'], $path . '.townships.' . $index);
        }

        return [
            'state' => $entry['state'],
            'townships' => $townships,
        ];
    }

    /**
     * @param  mixed  $entry
     * @return Township
     */
    private static function checkedTownship($entry, string $state, string $path): array
    {
        if (
            ! is_array($entry) || count($entry) !== 2
            || ! isset($entry['en'], $entry['mm'])
            || ! is_string($entry['en']) || ! is_string($entry['mm'])
            || ! preg_match('/\A[A-Za-z]+\z/', $entry['en'])
            || ! preg_match('/\A[\x{1000}-\x{109f}]+\z/u', $entry['mm'])
        ) {
            throw new \InvalidArgumentException($path . ' must contain English and Myanmar township code strings (en and mm).');
        }

        if (
            strlen($state . '/' . $entry['en'] . '(N)000000') > 256
            || mb_strlen($state . '/' . $entry['mm'] . '(နိုင်)၀၀၀၀၀၀') > 256
        ) {
            throw new \InvalidArgumentException($path . ' exceeds the supported NRC input length.');
        }

        return $entry;
    }

    /**
     * @param  mixed  $language
     * @return 'en'|'mm'
     */
    public static function language($language): string
    {
        if ($language === 'eng') {
            return 'en';
        }

        if ($language !== 'en' && $language !== 'mm') {
            throw new \InvalidArgumentException('Language must be en, eng, or mm.');
        }

        return $language;
    }

    public static function numbers(string $input, string $language): string
    {
        $target = self::language($language);
        $mm = ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'];
        $en = str_split('0123456789');

        $input = str_replace($mm, $en, $input);

        return $target === 'en' ? $input : str_replace($en, $mm, $input);
    }

    /**
     * @param  mixed  $state
     * @return StateGroup|null
     */
    public static function group($state): ?array
    {
        if (! is_string($state) && ! is_int($state)) {
            return null;
        }

        $state = self::numbers((string) $state, 'en');

        foreach (self::all() as $group) {
            if ($group['state'] === $state) {
                return $group;
            }
        }

        return null;
    }

    /** @return Township|null */
    public static function township(string $state, string $code): ?array
    {
        $group = self::group($state);

        foreach ($group['townships'] ?? [] as $township) {
            if (strtolower($township['en']) === strtolower($code) || $township['mm'] === $code) {
                return $township;
            }
        }

        return null;
    }

    /** @return array<int, NrcType> */
    public static function types(): array
    {
        $configured = self::configuration('types');

        if (! is_array($configured)) {
            throw new \InvalidArgumentException('nrc-kit.types must be an array.');
        }

        $types = [];
        $english = [];
        $myanmar = [];

        foreach ($configured as $index => $type) {
            if (
                ! is_array($type) || count($type) !== 4
                || ! isset($type['en'], $type['mm'], $type['label'], $type['labelMm'])
                || ! is_string($type['en']) || ! preg_match('/\A[A-Z]+\z/', $type['en'])
                || ! is_string($type['mm']) || ! preg_match('/\A[\x{1000}-\x{109f}]+\z/u', $type['mm'])
                || ! is_string($type['label']) || $type['label'] === ''
                || ! is_string($type['labelMm']) || $type['labelMm'] === ''
            ) {
                throw new \InvalidArgumentException('nrc-kit.types.' . $index . ' must contain en, mm, label and labelMm strings.');
            }

            if (isset($english[$type['en']]) || isset($myanmar[$type['mm']])) {
                throw new \InvalidArgumentException('nrc-kit.types.' . $index . ' duplicates an NRC type.');
            }

            $english[$type['en']] = true;
            $myanmar[$type['mm']] = true;
            $types[] = $type;
        }

        return $types;
    }

    /** @return NrcType|null */
    public static function type(string $code): ?array
    {
        $aliases = [
            'naing' => 'N',
            'pyu' => 'P',
            'ae' => 'E',
            'ဧည့်' => 'E',
        ];
        $code = $aliases[strtolower($code)] ?? $code;

        foreach (self::types() as $type) {
            if (strcasecmp($type['en'], $code) === 0 || $type['mm'] === $code) {
                return $type;
            }
        }

        return null;
    }

    /** @return array<int, Region> */
    public static function stateNames(): array
    {
        $configured = self::configuration('states');

        if (! is_array($configured)) {
            throw new \InvalidArgumentException('nrc-kit.states must be an array.');
        }

        $states = [];

        foreach ($configured as $index => $state) {
            if (
                ! is_array($state) || ! isset($state['stateNo'], $state['en'], $state['mm'])
                || ! is_int($state['stateNo']) || $state['stateNo'] < 1 || $state['stateNo'] > 9007199254740991
                || ! is_string($state['en']) || $state['en'] === ''
                || ! is_string($state['mm']) || $state['mm'] === ''
                || (array_key_exists('townships', $state) && ! is_array($state['townships']))
            ) {
                throw new \InvalidArgumentException('nrc-kit.states.' . $index . ' must contain a stateNo and bilingual names.');
            }

            foreach ($state['townships'] ?? [] as $code) {
                if (! is_string($code) || ! preg_match('/\A[A-Za-z]+\z/', $code)) {
                    throw new \InvalidArgumentException('nrc-kit.states.' . $index . '.townships must contain English township codes.');
                }
            }

            $states[] = $state;
        }

        return $states;
    }

    public static function stateName(string $state, string $township, string $language): string
    {
        $target = self::language($language);
        $names = [];

        foreach (self::stateNames() as $entry) {
            if ((string) $entry['stateNo'] !== $state) {
                continue;
            }

            foreach ($entry['townships'] ?? [] as $code) {
                if (strcasecmp($code, $township) === 0) {
                    return $entry[$target];
                }
            }

            $names[] = $entry[$target];
        }

        return $names ? implode(' / ', $names) : self::numbers($state, $target);
    }

    /** @return array<int, StateInfo> */
    public static function states(string $language): array
    {
        $target = self::language($language);
        $names = self::stateNames();
        $result = [];

        foreach (self::all() as $group) {
            $found = false;

            foreach ($names as $name) {
                if ((string) $name['stateNo'] === $group['state']) {
                    $result[] = [
                        'stateNo' => $name['stateNo'],
                        'name' => $name[$target],
                    ];
                    $found = true;
                }
            }

            if (! $found) {
                $result[] = [
                    'stateNo' => (int) $group['state'],
                    'name' => self::numbers($group['state'], $target),
                ];
            }
        }

        return $result;
    }
}
