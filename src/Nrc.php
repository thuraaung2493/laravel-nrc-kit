<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit;

/**
 * @phpstan-import-type ParsedNrc from NrcParser
 * @phpstan-import-type ValidationResult from NrcParser
 * @phpstan-import-type StateInfo from NrcData
 * @phpstan-import-type Township from NrcData
 * @phpstan-import-type NrcType from NrcData
 */
final class Nrc
{
    /** @var mixed */
    private $input;

    /** @param mixed $input */
    private function __construct($input)
    {
        $this->input = $input;
    }

    /** @param mixed $input */
    public static function from($input): self
    {
        return new self($input);
    }

    /** @param array<string, mixed> $parts */
    public static function fromParts(array $parts): self
    {
        foreach (['state', 'township', 'type', 'number'] as $key) {
            if (! isset($parts[$key]) || ! is_string($parts[$key])) {
                throw new \TypeError('NRC parts must contain string state, township, type, and number.');
            }

            if (preg_match('/[\/()]/', $parts[$key])) {
                throw new \InvalidArgumentException('Invalid NRC parts.');
            }
        }

        $nrc = new self($parts['state'] . '/' . $parts['township'] . '(' . $parts['type'] . ')' . $parts['number']);

        return new self($nrc->toEnglish());
    }

    /**
     * @param  mixed  $language
     * @return ParsedNrc|null
     */
    public function parse($language = 'auto'): ?array
    {
        $target = $language === 'auto' ? null : NrcData::language($language);
        $value = NrcParser::read($this->input)['parsed'];

        if ($value === null) {
            return null;
        }

        $target = $target ?? $value['language'];
        $township = NrcData::township($value['state'], $value['township']);
        assert($township !== null);

        $type = NrcData::type($value['type']);
        assert($type !== null);

        return [
            'state' => NrcData::numbers($value['state'], $target),
            'stateName' => NrcData::stateName($value['state'], $value['township'], $target),
            'township' => $township[$target],
            'type' => $type[$target],
            'typeName' => $target === 'mm' ? $type['labelMm'] : $type['label'],
            'number' => NrcData::numbers($value['number'], $target),
            'language' => $target,
        ];
    }

    /** @param mixed $language */
    public function convert($language = 'en'): string
    {
        $target = NrcData::language($language);

        if (! is_string($this->input)) {
            throw new \TypeError('NRC must be a string.');
        }

        $value = $this->parse($target);

        if ($value === null) {
            throw new \InvalidArgumentException('Invalid NRC.');
        }

        return $value['state'] . '/' . $value['township'] . '(' . $value['type'] . ')' . $value['number'];
    }

    public function toEnglish(): string
    {
        return $this->convert('en');
    }

    public function toMyanmar(): string
    {
        return $this->convert('mm');
    }

    public function isValid(): bool
    {
        return NrcParser::read($this->input)['validation']['valid'];
    }

    public function isInvalid(): bool
    {
        return ! $this->isValid();
    }

    /** @return ValidationResult */
    public function validate(): array
    {
        return NrcParser::read($this->input)['validation'];
    }

    /**
     * @param  mixed  $language
     * @return array<int, StateInfo>
     */
    public static function states($language = 'en'): array
    {
        return NrcData::states(NrcData::language($language));
    }

    /**
     * @param  mixed  $state
     * @param  mixed  $language
     * @return array<int, Township>|array<int, string>
     */
    public static function townships($state, $language = null): array
    {
        $target = func_num_args() < 2 ? null : NrcData::language($language);
        $group = NrcData::group($state);
        $townships = $group['townships'] ?? [];

        return $target === null ? $townships : array_column($townships, $target);
    }

    /**
     * @param  mixed  $language
     * @return array<int, array{en: string, mm: string, label: string}>
     */
    public static function types($language = 'en'): array
    {
        $target = NrcData::language($language);

        return array_map(static function ($type) use ($target) {
            return [
                'en' => $type['en'],
                'mm' => $type['mm'],
                'label' => $target === 'mm' ? $type['labelMm'] : $type['label'],
            ];
        }, NrcData::types());
    }
}
