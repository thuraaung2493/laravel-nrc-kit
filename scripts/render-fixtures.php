<?php

use Illuminate\View\ComponentAttributeBag;
use Orchestra\Testbench\TestCase;
use Thuraaung\NrcKit\NrcServiceProvider;
use Thuraaung\NrcKit\View\Components\NrcSelector;

require __DIR__ . '/../vendor/autoload.php';

class FixtureApp extends TestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app)
    {
        return [NrcServiceProvider::class];
    }

    /** @return array<int, string> */
    public function fixtures(bool $configured = false): array
    {
        // This CLI process needs an application, not a PHPUnit runner lifecycle.
        $this->app = $this->createApplication();
        $html = [];

        $json = file_get_contents(__DIR__ . '/../tests/fixtures/nrc-cases.json');

        if ($json === false) {
            throw new RuntimeException('Unable to read selector fixtures.');
        }

        $cases = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if ($configured) {
            // Synthetic records for the configured-selector browser checks only.
            $groups = [];

            foreach (['12', '99'] as $state) {
                $groups[] = [
                    'state' => $state,
                    'townships' => [
                        [
                            'en' => 'TestCode',
                            'mm' => 'ကခဂ',
                        ],
                    ],
                ];
            }

            $this->app['config']->set('nrc-kit.data', $groups);
            $cases = [['input' => '12/TestCode(P)001234']];
        }

        foreach ($cases as $index => $case) {
            $component = new NrcSelector('nrc', $case['input'], 'auto', true, false, 'fixture-' . $index);
            $html[] = $component->render()->with($component->data())->with('attributes', new ComponentAttributeBag)->render();
        }

        $this->app->terminate();

        return $html;
    }
}

/** @var array<int, string> $argv */
echo json_encode((new FixtureApp('fixtures'))->fixtures(in_array('--configured', $argv, true)), JSON_UNESCAPED_UNICODE);
