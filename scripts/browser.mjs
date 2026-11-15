import { chromium } from '@playwright/test';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import assert from 'node:assert/strict';

const html = JSON.parse(
  execFileSync(
    process.env.PHP_BINARY || 'php',
    ['scripts/render-fixtures.php'],
    { encoding: 'utf8' },
  ),
);
const browser = await chromium.launch({
  channel: process.env.PLAYWRIGHT_CHANNEL || undefined,
  headless: true,
});

try {
  const page = await browser.newPage();
  const failures = [];
  page.on('pageerror', (e) => failures.push(e.message));
  await page.setContent(
    '<form>' + html[0] + html[1] + '<button>Submit</button></form>',
  );
  await page.addScriptTag({
    content: readFileSync('resources/dist/nrc-selector.js', 'utf8'),
  });

  const first = page.locator('[data-nrc-selector]').first();
  const second = page.locator('[data-nrc-selector]').nth(1);
  const formData = () =>
    page.locator('form').evaluate((form) => new FormData(form).getAll('nrc'));

  assert.deepEqual(await formData(), [
    '12/LaMaNa(N)001234',
    '၁၂/လမန(နိုင်)၀၀၁၂၃၄',
  ]);

  await first.evaluate((root) => window.NrcKit.setLanguage(root, 'mm'));

  assert.equal(await first.getByLabel('ဂဏန်းခြောက်လုံး').inputValue(), '၀၀၁၂၃၄');

  await first.getByLabel('ပြည်နယ်/တိုင်း').focus();
  await page.keyboard.press('Tab');

  assert.equal(
    await first
      .getByLabel('မြို့နယ်')
      .evaluate((el) => el === document.activeElement),
    true,
  );

  await first.getByLabel('ပြည်နယ်/တိုင်း').selectOption('1');

  assert.equal(await first.getByLabel('မြို့နယ်').inputValue(), '');
  assert.equal(
    await page.locator('form').evaluate((form) => form.checkValidity()),
    false,
  );
  assert.deepEqual(await formData(), ['', '၁၂/လမန(နိုင်)၀၀၁၂၃၄']);

  await first.getByLabel('မြို့နယ်').selectOption('MaKaNa');
  await first.getByLabel('ဂဏန်းခြောက်လုံး').fill('၀0၁၂၃၄');

  assert.equal(await first.getByLabel('ဂဏန်းခြောက်လုံး').inputValue(), '၀၀၁၂၃၄');
  assert.equal(
    await page.locator('form').evaluate((form) => form.checkValidity()),
    true,
  );

  await first.getByLabel('နိုင်ငံသားအမျိုးအစား').selectOption('E');
  assert.deepEqual(await formData(), ['၁/မကန(ဧည့်)၀၀၁၂၃၄', '၁၂/လမန(နိုင်)၀၀၁၂၃၄']);
  await first.getByLabel('နိုင်ငံသားအမျိုးအစား').selectOption('M');
  assert.deepEqual(await formData(), ['၁/မကန(သ)၀၀၁၂၃၄', '၁၂/လမန(နိုင်)၀၀၁၂၃၄']);
  await first.getByLabel('ပြည်နယ်/တိုင်း').selectOption('9');
  assert.equal(
    await first.locator('[data-field="state"] option[value="9"]').count(),
    1,
  );
  assert.equal(
    await first.locator('[data-field="state"] option[value="9"]').textContent(),
    'နေပြည်တော် / မန္တလေးတိုင်း',
  );
  await first.getByLabel('မြို့နယ်').selectOption('UTaTha');
  assert.deepEqual(await formData(), ['၉/ဥတသ(သ)၀၀၁၂၃၄', '၁၂/လမန(နိုင်)၀၀၁၂၃၄']);
  await first.getByLabel('ပြည်နယ်/တိုင်း').selectOption('1');
  await first.getByLabel('မြို့နယ်').selectOption('MaKaNa');
  await first.getByLabel('နိုင်ငံသားအမျိုးအစား').selectOption('N');

  await first.evaluate((root) => window.NrcKit.setLanguage(root, 'en'));

  assert.deepEqual(await formData(), ['1/MaKaNa(N)001234', '၁၂/လမန(နိုင်)၀၀၁၂၃၄']);

  await second
    .locator('input[type=hidden]')
    .evaluate((el) => (el.disabled = true));

  assert.deepEqual(await formData(), ['1/MaKaNa(N)001234']);
  const [configured] = JSON.parse(
    execFileSync(
      process.env.PHP_BINARY || 'php',
      ['scripts/render-fixtures.php', '--configured'],
      { encoding: 'utf8' },
    ),
  );
  await page.setContent('<form>' + configured + '</form>');
  const custom = page.locator('[data-nrc-selector]');
  await custom.evaluate((root) => window.NrcKit.init(root));

  assert.deepEqual(await formData(), ['12/TestCode(P)001234']);

  await custom.evaluate((root) => window.NrcKit.setLanguage(root, 'mm'));
  await custom.getByLabel('ပြည်နယ်/တိုင်း').selectOption('99');

  assert.equal(await custom.getByLabel('မြို့နယ်').inputValue(), 'TestCode');
  assert.deepEqual(await formData(), ['၉၉/ကခဂ(ပြု)၀၀၁၂၃၄']);
  assert.equal(
    await page.locator('form').evaluate((form) => form.checkValidity()),
    true,
  );

  assert.deepEqual(failures, []);

  console.log(
    `Chromium ${browser.version()}: Blade asset submission, localization, keyboard, independent instances required/disabled behavior and application extensions passed`,
  );
} finally {
  await browser.close();
}
