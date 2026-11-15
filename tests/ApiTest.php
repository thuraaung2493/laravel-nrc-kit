<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Tests;

use PHPUnit\Framework\TestCase;
use Thuraaung\NrcKit\Nrc;

final class ApiTest extends TestCase
{
    public function test_types_include_descriptions_and_unambiguous_codes(): void
    {
        $this->assertSame([
            [
                'en' => 'N',
                'mm' => 'နိုင်',
                'label' => 'Full Citizen (Naing-ngan-tha)',
            ],
            [
                'en' => 'E',
                'mm' => 'ဧည့်',
                'label' => 'Associate Citizen (Aye-naing-ngan-tha)',
            ],
            [
                'en' => 'P',
                'mm' => 'ပြု',
                'label' => 'Naturalized Citizen (Pyu-su-khwint-ya-thu)',
            ],
            [
                'en' => 'M',
                'mm' => 'သ',
                'label' => 'Monk (Tharthana-win)',
            ],
        ], Nrc::types('en'));
        $copy = Nrc::types();
        $copy[0]['label'] = 'Changed';

        $this->assertSame('Full Citizen (Naing-ngan-tha)', Nrc::types()[0]['label']);
    }

    public function test_state_numbers_have_localized_names_and_shared_number_nine(): void
    {
        $states = Nrc::states('en');
        $this->assertCount(15, $states);
        $this->assertSame([
            'stateNo' => 1,
            'name' => 'Kachin',
        ], $states[0]);
        $this->assertSame([
            [
                'stateNo' => 9,
                'name' => 'Naypyidaw',
            ],
            [
                'stateNo' => 9,
                'name' => 'Mandalay',
            ],
        ], array_values(array_filter($states, static function ($state) {
            return $state['stateNo'] === 9;
        })));
        $this->assertContains([
            'stateNo' => 12,
            'name' => 'ရန်ကုန်တိုင်း',
        ], Nrc::states('mm'));
    }

    public function test_townships_are_pairs_or_localized_strings(): void
    {
        $this->assertSame([
            [
                'en' => 'MaLaMa',
                'mm' => 'မလမ',
            ],
            [
                'en' => 'KaMaRa',
                'mm' => 'ကမရ',
            ],
        ], array_slice(Nrc::townships(10), 0, 2));
        $this->assertSame(['ThaGaKa', 'RaKaNa', 'UKaTa', 'UKaMa'], array_slice(Nrc::townships(12, 'en'), 0, 4));
        $this->assertContains('လမန', Nrc::townships('၁၂', 'mm'));
        $this->assertSame([], Nrc::townships(99));
        $this->assertNotContains('townshipOptions', get_class_methods(Nrc::class));
    }

    public function test_parse_includes_region_and_type_names(): void
    {
        $this->assertSame([
            'state' => '၁၂',
            'stateName' => 'ရန်ကုန်တိုင်း',
            'township' => 'လမန',
            'type' => 'နိုင်',
            'typeName' => 'နိုင်ငံသား',
            'number' => '၀၀၁၂၃၄',
            'language' => 'mm',
        ], Nrc::from('12/LaMaNa(N)001234')->parse('mm'));
        $this->assertSame([
            'state' => '12',
            'stateName' => 'Yangon',
            'township' => 'LaMaNa',
            'type' => 'N',
            'typeName' => 'Full Citizen (Naing-ngan-tha)',
            'number' => '001234',
            'language' => 'en',
        ], Nrc::from('၁၂/လမန(နိုင်)၀၀၁၂၃၄')->parse('en'));

        foreach (['UTaTha', 'ZaYaTha', 'PaBaTha', 'TaKaNa', 'DaKhaTha', 'ZaBaTha', 'PaMaNa', 'LaWaNa'] as $code) {
            $parsed = Nrc::from('9/' . $code . '(N)001234')->parse('en');
            $this->assertNotNull($parsed);
            $this->assertSame('Naypyidaw', $parsed['stateName']);
        }

        $mandalay = Nrc::from('9/MaNaMa(M)001234')->parse('en');
        $this->assertNotNull($mandalay);
        $this->assertSame('Mandalay', $mandalay['stateName']);
    }

    public function test_new_types_convert_without_accepting_nun_or_invalid_serials(): void
    {
        foreach (Nrc::types() as $type) {
            $english = '12/LaMaNa(' . $type['en'] . ')001234';
            $myanmar = '၁၂/လမန(' . $type['mm'] . ')၀၀၁၂၃၄';

            $this->assertSame($myanmar, Nrc::from($english)->toMyanmar());
            $this->assertSame($english, Nrc::from($myanmar)->toEnglish());
        }

        $this->assertSame('12/LaMaNa(E)001234', Nrc::from('12/LaMaNa(ae)001234')->toEnglish());
        $this->assertSame('12/LaMaNa(E)001234', Nrc::from('12/LaMaNa(ဧည့်)001234')->toEnglish());
        $this->assertFalse(Nrc::from('12/LaMaNa(သီ)001234')->isValid());
        $this->assertFalse(Nrc::from('12/LaMaNa(Nun)001234')->isValid());
        $this->assertFalse(Nrc::from('1/LaMaNa(E)001234')->isValid());
        $this->assertFalse(Nrc::from('12/LaMaNa(M)12345')->isValid());
    }
}
