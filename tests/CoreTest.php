<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Tests;

use PHPUnit\Framework\TestCase;
use Thuraaung\NrcKit\Nrc;
use Thuraaung\NrcKit\NrcData;

final class CoreTest extends TestCase
{
    public function test_snapshot_integrity(): void
    {
        $data = NrcData::snapshot();
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->assertSame('009e65cfa1a01d8a5f4ad710acf32e766b8fca1f913917203db8338d5eaf11bc', hash('sha256', $encoded));

        $this->assertSame(array_map('strval', range(1, 14)), array_column($data, 'state'));

        $count = 0;

        foreach ($data as $group) {
            $count += count($group['townships']);

            foreach (['en', 'mm'] as $language) {
                $codes = array_map('strtolower', array_column($group['townships'], $language));

                $this->assertCount(count($codes), array_unique($codes));
                $this->assertNotContains('', $codes);
            }
        }

        $this->assertSame(429, $count);
    }

    public function test_shared_fixtures(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/nrc-cases.json');
        $this->assertNotFalse($json);
        $cases = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        foreach ($cases as $case) {
            $nrc = Nrc::from($case['input']);

            $this->assertSame(! isset($case['errors']), $nrc->isValid());
            $this->assertSame(isset($case['errors']), $nrc->isInvalid());
            $this->assertSame($case['errors'] ?? [], array_column($nrc->validate()['errors'], 'code'));

            if (isset($case['errors'])) {
                $this->assertNull($nrc->parse());

                continue;
            }

            $this->assertSame($case['en'], $nrc->toEnglish());
            $this->assertSame($case['mm'], $nrc->toMyanmar());

            $parsed = $nrc->parse();
            $this->assertNotNull($parsed);
            $this->assertSame($case['language'], $parsed['language']);
            $this->assertSame($nrc->parse($case['language']), $nrc->parse());
            $this->assertSame($nrc->parse('en'), $nrc->parse('eng'));

            $english = $nrc->parse('en');
            $this->assertNotNull($english);
            $this->assertSame($case['mm'], Nrc::fromParts($english)->toMyanmar());
        }
    }

    public function test_every_township_round_trips(): void
    {
        foreach (NrcData::snapshot() as $group) {
            foreach ($group['townships'] as $township) {
                foreach (
                    [
                        'N' => 'N',
                        'Naing' => 'N',
                        'P' => 'P',
                        'Pyu' => 'P',
                        'E' => 'E',
                        'AE' => 'E',
                        'M' => 'M',
                    ] as $alias => $type
                ) {
                    $input = $group['state'] . '/' . strtolower($township['en']) . '(' . $alias . ')001234';
                    $expected = $group['state'] . '/' . $township['en'] . '(' . $type . ')001234';

                    $this->assertSame($expected, Nrc::from(Nrc::from($input)->toMyanmar())->toEnglish());

                    $mixed = NrcData::numbers($group['state'], 'mm') . '/' . $township['mm'] . '(' . $alias . ')၀0၁234';

                    $this->assertSame($expected, Nrc::from($mixed)->toEnglish());

                    $parsed = Nrc::from($mixed)->parse();
                    $this->assertNotNull($parsed);
                    $this->assertSame('mm', $parsed['language']);
                }
            }
        }
    }

    public function test_fresh_localized_options(): void
    {
        $this->assertContains([
            'stateNo' => 12,
            'name' => 'ရန်ကုန်တိုင်း',
        ], Nrc::states('mm'));
        $this->assertContains('လမန', Nrc::townships('၁၂', 'mm'));
        $this->assertSame([], Nrc::townships(99));
        $this->assertSame('နိုင်', Nrc::types('mm')[0]['mm']);

        $options = Nrc::townships(12);
        $this->assertIsArray($options[0]);
        $options[0]['mm'] = 'broken';
        $fresh = Nrc::townships(12);
        $this->assertIsArray($fresh[0]);

        $this->assertNotSame('broken', $fresh[0]['mm']);
    }

    public function test_invalid_language_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Nrc::from('bad')->parse('xx');
    }

    public function test_integer_serial_throws(): void
    {
        $this->expectException(\TypeError::class);

        Nrc::fromParts([
            'state' => '12',
            'township' => 'LaMaNa',
            'type' => 'N',
            'number' => 123456,
        ]);
    }

    public function test_invalid_conversion_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Nrc::from('bad')->toEnglish();
    }

    public function test_wrong_input_conversion_throws(): void
    {
        $this->expectException(\TypeError::class);

        Nrc::from(123)->toEnglish();
    }
}
