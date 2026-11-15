<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Orchestra\Testbench\TestCase;
use Thuraaung\NrcKit\NrcServiceProvider;
use Thuraaung\NrcKit\Rules\ValidNrc;

final class IntegrationTest extends TestCase
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app)
    {
        return [NrcServiceProvider::class];
    }

    /** @param array<string, mixed> $data */
    public function renderSelector(string $template, array $data = []): string
    {
        $compiled = Blade::compileString($template);

        return view()->file($this->writeView($compiled), $data)->render();
    }

    private function writeView(string $compiled): string
    {
        $file = tempnam(sys_get_temp_dir(), 'nrc-view-');

        if ($file === false) {
            throw new \RuntimeException('Unable to create the temporary Blade view.');
        }

        rename($file, $file . '.php');
        $file .= '.php';
        file_put_contents($file, $compiled);

        $this->beforeApplicationDestroyed(function () use ($file) {
            unlink($file);
        });

        return $file;
    }

    public function test_laravel_rule(): void
    {
        $validator = $this->app['validator'];

        $this->assertTrue($validator->make(['nrc' => '12/LaMaNa(N)001234'], ['nrc' => ['required', new ValidNrc]])->passes());

        foreach (['1/LaMaNa(N)001234', '12/LaMaNa(N)12345', 123] as $value) {
            $result = $validator->make(['nrc' => $value], ['nrc' => ['required', new ValidNrc]]);

            $this->assertTrue($result->fails());
            $this->assertStringContainsString('nrc', $result->errors()->first());
        }
    }

    public function test_localized_markup_and_submission(): void
    {
        $html = $this->renderSelector('<x-nrc-selector name="identity" value="၁၂/လမန(နိုင်)၀၀၁၂၃၄" required />');

        $this->assertStringContainsString('ပြည်နယ်/တိုင်း', $html);
        $this->assertStringContainsString('မြို့နယ်', $html);
        $this->assertStringContainsString('ဂဏန်းခြောက်လုံး', $html);
        $this->assertMatchesRegularExpression('/value="12"\s+selected\s*>ရန်ကုန်တိုင်း<\/option>/', $html);
        $this->assertMatchesRegularExpression('/value="LaMaNa"\s+selected\s*>လမန<\/option>/', $html);
        $this->assertMatchesRegularExpression('/value="N"\s+selected\s*>နိုင်<\/option>/', $html);
        $this->assertStringContainsString('value="၀၀၁၂၃၄"', $html);
        $this->assertStringContainsString('name="identity" value="၁၂/လမန(နိုင်)၀၀၁၂၃၄"', $html);
        $this->assertSame(1, substr_count($html, 'name="'));
        $this->assertSame(4, substr_count($html, ' required'));
    }

    public function test_old_values_errors_and_escaping(): void
    {
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $this->app['session']->flashInput(['nrc' => '12/LaMaNa(P)000001']);
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['nrc' => '<script>error</script>']));
        $this->app['session']->put('errors', $errors);

        $html = $this->renderSelector('<x-nrc-selector value="bad" />');

        $this->assertStringContainsString('name="nrc" value="12/LaMaNa(P)000001"', $html);
        $this->assertStringContainsString('&lt;script&gt;error&lt;/script&gt;', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby=', $html);
    }

    public function test_invalid_preset_and_multiple_disabled_instances(): void
    {
        $html = $this->renderSelector('<x-nrc-selector name="first" value="bad" disabled /><x-nrc-selector name="second" language="mm" />');

        $this->assertMatchesRegularExpression('/name="first" value="" data-nrc-value\s+disabled/', $html);
        $this->assertStringContainsString('Enter a complete valid NRC.', $html);

        preg_match_all('/id="(nrc-[^"]+)"/', $html, $matches);

        $this->assertSame(count($matches[1]), count(array_unique($matches[1])));
        $this->assertSame(1, substr_count($html, 'src="http://localhost/vendor/nrc-kit/nrc-selector.js"'));
        $this->assertFileExists(__DIR__ . '/../resources/dist/nrc-selector.js');
    }

    public function test_asset_is_included_again_in_separate_renders(): void
    {
        foreach (range(1, 2) as $render) {
            $html = $this->renderSelector('<x-nrc-selector />');

            $this->assertSame(1, substr_count($html, 'src="http://localhost/vendor/nrc-kit/nrc-selector.js"'));
        }
    }
}
