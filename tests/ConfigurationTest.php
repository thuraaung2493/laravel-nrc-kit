<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\ComponentAttributeBag;
use Orchestra\Testbench\TestCase;
use Thuraaung\NrcKit\Nrc;
use Thuraaung\NrcKit\NrcData;
use Thuraaung\NrcKit\NrcServiceProvider;
use Thuraaung\NrcKit\Rules\ValidNrc;
use Thuraaung\NrcKit\View\Components\NrcSelector;

final class ConfigurationTest extends TestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app)
    {
        return [NrcServiceProvider::class];
    }

    /** @return array<int, array{state: string, townships: array<int, array{en: string, mm: string}>}> */
    private static function customData(string $state = '12'): array
    {
        // Synthetic records used only to test application configuration.
        return [
            [
                'state' => $state,
                'townships' => [
                    [
                        'en' => 'TestCode',
                        'mm' => 'ကခဂ',
                    ],
                ],
            ],
        ];
    }

    public function test_full_default_data_is_loaded_and_publishable_without_json(): void
    {
        $config = require __DIR__ . '/../config/nrc-kit.php';
        $this->assertSame($config['data'], $this->app['config']->get('nrc-kit.data'));
        $this->assertSame($config['data'], NrcData::snapshot());
        $this->assertSame($config['data'], NrcData::all());
        $this->assertCount(14, $config['data']);
        $this->assertFileDoesNotExist(__DIR__ . '/../data/nrc.json');

        $paths = ServiceProvider::pathsToPublish(NrcServiceProvider::class, 'nrc-kit-config');

        $this->assertSame([config_path('nrc-kit.php')], array_values($paths));
        $this->assertSame([realpath(__DIR__ . '/../config/nrc-kit.php')], array_map('realpath', array_keys($paths)));
    }

    public function test_configured_data_drives_core_apis_rule_and_blade(): void
    {
        $this->app['config']->set('nrc-kit.data', self::customData());
        $nrc = Nrc::from('12/testcode(Naing)001234');

        $this->assertTrue($nrc->isValid());
        $this->assertSame('၁၂/ကခဂ(နိုင်)၀၀၁၂၃၄', $nrc->toMyanmar());
        $this->assertSame('12/TestCode(N)001234', Nrc::from($nrc->toMyanmar())->toEnglish());
        $this->assertSame([
            'state' => '၁၂',
            'stateName' => 'ရန်ကုန်တိုင်း',
            'township' => 'ကခဂ',
            'type' => 'နိုင်',
            'typeName' => 'နိုင်ငံသား',
            'number' => '၀၀၁၂၃၄',
            'language' => 'mm',
        ], Nrc::from($nrc->toMyanmar())->parse());
        $this->assertSame('12/TestCode(P)001234', Nrc::fromParts([
            'state' => '12',
            'township' => 'TestCode',
            'type' => 'P',
            'number' => '001234',
        ])->toEnglish());
        $this->assertTrue((new ValidNrc)->passes('nrc', $nrc->toEnglish()));
        $this->assertFalse(Nrc::from('1/TestCode(N)001234')->isValid());
        $this->assertFalse(Nrc::from('12/TestCode(N)12345')->isValid());
        $this->assertFalse(Nrc::from('12/TestCode(X)001234')->isValid());
        $this->assertFalse(Nrc::from('12/LaMaNa(N)001234')->isValid());
        $this->assertSame([], Nrc::townships(1));
        $this->assertSame(['ကခဂ'], Nrc::townships(12, 'mm'));
        $this->assertSame([
            [
                'en' => 'TestCode',
                'mm' => 'ကခဂ',
            ],
        ], Nrc::townships(12));

        $component = new NrcSelector('nrc', $nrc->toMyanmar(), 'auto');
        $html = $component->render()->with($component->data())->with('attributes', new ComponentAttributeBag)->render();

        $this->assertSame(self::customData(), $component->reference['groups']);
        $this->assertMatchesRegularExpression('/value="TestCode"\s+selected\s*>ကခဂ<\/option>/', $html);
        $this->assertStringContainsString('name="nrc" value="၁၂/ကခဂ(နိုင်)၀၀၁၂၃၄"', $html);
        $this->assertCount(14, NrcData::snapshot());
    }

    public function test_application_configuration_replaces_defaults_during_registration(): void
    {
        $originalContainer = Container::getInstance();

        foreach ([self::customData(), []] as $data) {
            $fresh = new Application(__DIR__);
            $fresh->instance('config', new Repository(['nrc-kit' => ['data' => $data]]));

            try {
                (new NrcServiceProvider($fresh))->register();

                $this->assertSame($data, $fresh['config']->get('nrc-kit.data'));
                $this->assertSame($data, NrcData::all());
            } finally {
                Container::setInstance($originalContainer);
            }
        }
    }

    public function test_users_can_edit_and_remove_default_mappings(): void
    {
        $data = NrcData::snapshot();

        foreach ($data[11]['townships'] as &$township) {
            if ($township['en'] === 'LaMaNa') {
                $township['mm'] = 'ကခဂ';
            }
        }

        unset($township);
        $this->app['config']->set('nrc-kit.data', $data);

        $this->assertSame('၁၂/ကခဂ(နိုင်)၀၀၁၂၃၄', Nrc::from('12/LaMaNa(N)001234')->toMyanmar());
        $this->assertFalse(Nrc::from('၁၂/လမန(နိုင်)၀၀၁၂၃၄')->isValid());

        $data[11]['townships'] = array_values(array_filter($data[11]['townships'], static function ($township) {
            return $township['en'] !== 'LaMaNa';
        }));
        $this->app['config']->set('nrc-kit.data', $data);

        $this->assertFalse(Nrc::from('12/LaMaNa(N)001234')->isValid());

        unset($data[0]);
        $this->app['config']->set('nrc-kit.data', $data);

        $this->assertSame([], Nrc::townships(1));
        $this->assertCount(14, Nrc::states());
    }

    public function test_configured_order_and_new_states_are_preserved(): void
    {
        $this->app['config']->set('nrc-kit.data', array_merge(self::customData('99'), self::customData('20')));

        $this->assertSame([99, 20], array_column(Nrc::states(), 'stateNo'));
        $this->assertSame('၉၉/ကခဂ(ပြု)၀၀၁၂၃၄', Nrc::from('99/TestCode(P)001234')->toMyanmar());
        $this->assertFalse(Nrc::from('100/TestCode(N)001234')->isValid());
    }

    public function test_empty_data_removes_all_mappings_and_missing_config_uses_defaults(): void
    {
        $this->app['config']->set('nrc-kit.data', []);

        $this->assertSame([], Nrc::states());
        $this->assertSame([], Nrc::townships(12));
        $this->assertFalse(Nrc::from('12/LaMaNa(N)001234')->isValid());

        $this->app['config']->set('nrc-kit', []);

        $this->assertSame(NrcData::snapshot(), NrcData::all());
    }

    public function test_runtime_changes_and_fresh_applications_do_not_share_data(): void
    {
        $this->app['config']->set('nrc-kit.data', self::customData());
        $copy = NrcData::all();
        $copy[0]['townships'][0]['en'] = 'Changed';

        $this->assertSame('TestCode', NrcData::all()[0]['townships'][0]['en']);

        $this->app['config']->set('nrc-kit.data', self::customData('99'));

        $this->assertFalse(Nrc::from('12/TestCode(N)001234')->isValid());
        $this->assertTrue(Nrc::from('99/TestCode(N)001234')->isValid());

        $originalContainer = Container::getInstance();
        $fresh = new Container;
        $fresh->instance('config', new Repository);
        Container::setInstance($fresh);

        try {
            $this->assertSame(NrcData::snapshot(), NrcData::all());
            $this->assertFalse(Nrc::from('99/TestCode(N)001234')->isValid());
        } finally {
            Container::setInstance($originalContainer);
        }
    }

    public function test_laravel_loads_the_full_dataset_from_cached_configuration(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'nrc-config-');
        $this->assertNotFalse($file);
        $configuration = $this->app['config']->all();
        $configuration['nrc-kit'] = ['data' => self::customData()];
        file_put_contents($file, '<?php return ' . var_export($configuration, true) . ';');
        $previousPath = getenv('APP_CONFIG_CACHE');
        $originalContainer = Container::getInstance();
        putenv('APP_CONFIG_CACHE=' . $file);

        try {
            $fresh = new Application(__DIR__);
            (new LoadConfiguration)->bootstrap($fresh);
            (new NrcServiceProvider($fresh))->register();

            $this->assertTrue($fresh->configurationIsCached());
            $this->assertSame(self::customData(), $fresh['config']->get('nrc-kit.data'));
            $this->assertTrue(Nrc::from('12/TestCode(N)001234')->isValid());
            $this->assertFalse(Nrc::from('12/LaMaNa(N)001234')->isValid());
        } finally {
            putenv($previousPath === false ? 'APP_CONFIG_CACHE' : 'APP_CONFIG_CACHE=' . $previousPath);
            Container::setInstance($originalContainer);
            unlink($file);
        }
    }

    public function test_invalid_or_ambiguous_data_is_rejected(): void
    {
        $invalid = [false, null, ['state' => '12']];

        foreach (['012', 12, '9007199254740992'] as $state) {
            $data = self::customData();
            $data[0]['state'] = $state;
            $invalid[] = $data;
        }

        foreach (
            [
                [],
                [['en' => 'TestCode']],
                [
                    [
                        'en' => '<script>',
                        'mm' => 'ကခဂ',
                    ],
                ],
                [
                    [
                        'en' => 'TestCode',
                        'mm' => 'Code',
                    ],
                ],
                [
                    [
                        'en' => str_repeat('A', 256),
                        'mm' => 'ကခဂ',
                    ],
                ],
            ] as $townships
        ) {
            $invalid[] = [
                [
                    'state' => '12',
                    'townships' => $townships,
                ],
            ];
        }

        $invalid[] = array_merge(self::customData(), self::customData());

        foreach (
            [
                [
                    'en' => 'testcode',
                    'mm' => 'ကခဃ',
                ],
                [
                    'en' => 'OtherCode',
                    'mm' => 'ကခဂ',
                ],
                [
                    'en' => 'TestCode',
                    'mm' => 'ကခဂ',
                ],
            ] as $duplicate
        ) {
            $data = self::customData();
            $data[0]['townships'][] = $duplicate;
            $invalid[] = $data;
        }

        foreach ($invalid as $index => $data) {
            $this->app['config']->set('nrc-kit.data', $data);

            try {
                NrcData::all();
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('nrc-kit.data', $exception->getMessage(), 'Invalid data case ' . $index);

                continue;
            }

            $this->fail('Expected invalid data case ' . $index . ' to be rejected.');
        }
    }

    public function test_configured_names_and_type_descriptions_are_used(): void
    {
        $this->app['config']->set('nrc-kit.states', [
            [
                'stateNo' => 12,
                'en' => 'Custom Yangon',
                'mm' => 'စိတ်ကြိုက်ရန်ကုန်',
            ],
        ]);
        $types = NrcData::types();
        $types[0]['label'] = 'Custom Citizen';
        $types[0]['labelMm'] = 'စိတ်ကြိုက်နိုင်ငံသား';
        $this->app['config']->set('nrc-kit.types', $types);

        $english = Nrc::from('12/LaMaNa(N)001234')->parse('en');
        $myanmar = Nrc::from('12/LaMaNa(N)001234')->parse('mm');

        $this->assertNotNull($english);
        $this->assertNotNull($myanmar);
        $this->assertSame('Custom Yangon', $english['stateName']);
        $this->assertSame('Custom Citizen', $english['typeName']);
        $this->assertSame('စိတ်ကြိုက်ရန်ကုန်', $myanmar['stateName']);
        $this->assertSame('စိတ်ကြိုက်နိုင်ငံသား', Nrc::types('mm')[0]['label']);
    }

    public function test_invalid_metadata_fails_clearly(): void
    {
        $cases = [
            ['types', [['en' => 'N']]],
            ['types', array_merge(NrcData::types(), NrcData::types())],
            [
                'states',
                [
                    [
                        'stateNo' => '12',
                        'en' => 'Yangon',
                        'mm' => 'ရန်ကုန်တိုင်း',
                    ],
                ],
            ],
            [
                'states',
                [
                    [
                        'stateNo' => 12,
                        'en' => 'Yangon',
                        'mm' => 'ရန်ကုန်တိုင်း',
                        'townships' => null,
                    ],
                ],
            ],
        ];

        foreach ($cases as [$key, $data]) {
            $this->app['config']->set('nrc-kit.' . $key, $data);

            try {
                if ($key === 'types') {
                    Nrc::types();
                } else {
                    Nrc::states();
                }
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('nrc-kit.' . $key, $exception->getMessage());

                continue;
            }

            $this->fail('Expected invalid ' . $key . ' metadata to be rejected.');
        }
    }
}
