<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Thuraaung\NrcKit\Nrc;
use Thuraaung\NrcKit\NrcData;

/**
 * @phpstan-import-type ParsedNrc from \Thuraaung\NrcKit\NrcParser
 * @phpstan-import-type StateGroup from NrcData
 * @phpstan-import-type NrcType from NrcData
 * @phpstan-import-type Region from NrcData
 * @phpstan-import-type Option from NrcData
 *
 * @phpstan-type Labels array{state: string, township: string, type: string, number: string, choose: string, placeholder: string, invalid: string}
 */
final class NrcSelector extends Component
{
    /** @var string */
    public $name;

    /** @var string */
    public $id;

    /** @var 'en'|'mm' */
    public $language;

    /** @var bool */
    public $required;

    /** @var bool */
    public $disabled;

    /** @var array{state: string, township: string, type: string, number: string, language?: string} */
    public $parts;

    /** @var string */
    public $formatted;

    /** @var string */
    public $error;

    /** @var Labels */
    public $text;

    /** @var array<string, array<int, Option>> */
    public $options;

    /** @var array{groups: array<int, StateGroup>, states: array<int, Region>, types: array<int, NrcType>, labels: array<string, Labels>} */
    public $reference;

    /**
     * @param  string  $name
     * @param  mixed  $value
     * @param  string  $language
     * @param  bool  $required
     * @param  bool  $disabled
     * @param  string|null  $id
     * @param  string|null  $error
     */
    public function __construct(
        $name = 'nrc',
        $value = null,
        $language = 'auto',
        $required = false,
        $disabled = false,
        $id = null,
        $error = null
    ) {
        if (! in_array($language, ['auto', 'en', 'mm'], true)) {
            throw new \InvalidArgumentException('Selector language must be auto, en, or mm.');
        }

        $this->name = $name;
        $this->id = $id ?? uniqid('nrc-', false);
        $this->required = (bool) $required;
        $this->disabled = (bool) $disabled;

        $value = old($name, $value);
        $nrc = Nrc::from($value);
        $parsed = $nrc->parse('en');

        $detected = $nrc->parse();
        $this->language = $language === 'auto' ? ($detected['language'] ?? 'en') : $language;
        $this->parts = $parsed ?? [
            'state' => '',
            'township' => '',
            'type' => '',
            'number' => '',
        ];
        $this->formatted = $parsed ? $nrc->convert($this->language) : '';

        $labels = self::labels();
        $this->text = $labels[$this->language];

        $errors = session('errors');
        $this->error = $error ?? ($errors ? $errors->first($name) : '');

        if (! $this->error && $value !== null && $value !== '' && ! $parsed) {
            $this->error = $this->text['invalid'];
        }

        $this->options = $this->makeOptions();
        $this->reference = [
            'groups' => NrcData::all(),
            'states' => NrcData::stateNames(),
            'types' => NrcData::types(),
            'labels' => $labels,
        ];
    }

    /** @return array<string, array<int, Option>> */
    private function makeOptions(): array
    {
        $language = $this->language;
        $states = [];

        foreach (Nrc::states($language) as $state) {
            $value = (string) $state['stateNo'];

            if (isset($states[$value])) {
                $states[$value]['label'] .= ' / ' . $state['name'];
            } else {
                $states[$value] = [
                    'value' => $value,
                    'label' => $state['name'],
                ];
            }
        }

        $group = NrcData::group($this->parts['state']);

        return [
            'state' => array_values($states),
            'township' => array_map(static function ($township) use ($language) {
                return [
                    'value' => $township['en'],
                    'label' => $township[$language],
                ];
            }, $group['townships'] ?? []),
            'type' => array_map(static function ($type) use ($language) {
                return [
                    'value' => $type['en'],
                    'label' => $type[$language],
                ];
            }, NrcData::types()),
        ];
    }

    /** @return array<string, Labels> */
    private static function labels(): array
    {
        return [
            'en' => [
                'state' => 'State/Region',
                'township' => 'Township',
                'type' => 'NRC Type',
                'number' => 'Six-digit Number',
                'choose' => 'Select',
                'placeholder' => '001234',
                'invalid' => 'Enter a complete valid NRC.',
            ],
            'mm' => [
                'state' => 'ပြည်နယ်/တိုင်း',
                'township' => 'မြို့နယ်',
                'type' => 'နိုင်ငံသားအမျိုးအစား',
                'number' => 'ဂဏန်းခြောက်လုံး',
                'choose' => 'ရွေးချယ်ပါ',
                'placeholder' => '၀၀၁၂၃၄',
                'invalid' => 'မှန်ကန်သော နိုင်ငံသားစိစစ်ရေးကတ်အမှတ်ကို အပြည့်အစုံဖြည့်ပါ။',
            ],
        ];
    }

    public function render(): View
    {
        return view('nrc-kit::components.nrc-selector');
    }
}
