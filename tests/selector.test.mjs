import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { JSDOM } from 'jsdom';

const cases = JSON.parse(
  readFileSync(new URL('./fixtures/nrc-cases.json', import.meta.url)),
);
const html = JSON.parse(
  execFileSync(
    process.env.PHP_BINARY || 'php',
    ['scripts/render-fixtures.php'],
    { encoding: 'utf8' },
  ),
);
const asset = readFileSync(
  new URL('../resources/dist/nrc-selector.js', import.meta.url),
  'utf8',
);

function setup(markup) {
  const dom = new JSDOM('<form>' + markup + '</form>', {
    runScripts: 'outside-only',
  });
  dom.window.eval(asset);

  const root = dom.window.document.querySelector('[data-nrc-selector]');
  dom.window.NrcKit.init(root);

  return {
    dom,
    root,
    fields: Object.fromEntries(
      [...root.querySelectorAll('[data-field]')].map((el) => [
        el.dataset.field,
        el,
      ]),
    ),
  };
}

for (const [index, fixture] of cases.entries())
  test('shared selector fixture ' + index, () => {
    const { dom, root, fields } = setup(html[index]);

    assert.equal(
      new dom.window.FormData(root.closest('form')).get('nrc'),
      fixture.errors ? '' : fixture.language === 'mm' ? fixture.mm : fixture.en,
    );

    if (!fixture.errors) {
      dom.window.NrcKit.setLanguage(root, 'mm');

      assert.equal(fields.number.value, fixture.mm.slice(-6));
      assert.equal(
        new dom.window.FormData(root.closest('form')).get('nrc'),
        fixture.mm,
      );
      assert.equal(fields.state.selectedOptions[0].text, 'ရန်ကုန်တိုင်း');
      assert.equal(fields.township.selectedOptions[0].text, 'လမန');

      dom.window.NrcKit.setLanguage(root, 'en');

      assert.equal(
        new dom.window.FormData(root.closest('form')).get('nrc'),
        fixture.en,
      );
    }

    dom.window.close();
  });

test('dependent fields, partial entries, accessibility, disabled and independent instances', () => {
  const { dom, root, fields } = setup(html[0] + html[1]);

  assert.equal(root.querySelectorAll('input[name]').length, 1);

  for (const el of Object.values(fields))
    assert.ok(root.querySelector('label[for="' + el.id + '"]'));

  fields.state.value = '1';
  fields.state.dispatchEvent(new dom.window.Event('change', { bubbles: true }));

  assert.equal(fields.township.value, '');
  assert.equal(fields.number.value, '001234');
  assert.equal(new dom.window.FormData(root.closest('form')).get('nrc'), '');
  assert.equal(fields.number.checkValidity(), false);

  fields.township.value = fields.township.options[1].value;
  fields.township.dispatchEvent(new dom.window.Event('change'));
  fields.number.value = '၀0၁၂၃၄';
  fields.number.dispatchEvent(new dom.window.Event('input'));

  assert.equal(fields.number.value, '001234');
  assert.equal(fields.number.checkValidity(), true);
  assert.equal(
    dom.window.document
      .querySelectorAll('[data-nrc-selector]')[1]
      .querySelector('[data-nrc-value]').value,
    cases[1].mm,
  );

  root.querySelector('[data-nrc-value]').disabled = true;

  assert.equal(
    new dom.window.FormData(root.closest('form')).getAll('nrc').length,
    1,
  );

  dom.window.close();
});

test('restores the original canonical preset on native form reset after a state and language change', async () => {
  const { dom, root, fields } = setup(html[0]);
  fields.state.value = '1';
  fields.state.dispatchEvent(new dom.window.Event('change'));
  fields.township.value = 'MaKaNa';
  fields.township.dispatchEvent(new dom.window.Event('change'));
  dom.window.NrcKit.setLanguage(root, 'mm');
  root.closest('form').reset();
  await new Promise((resolve) => setTimeout(resolve, 5));

  assert.equal(fields.state.value, '12');
  assert.equal(fields.township.value, 'LaMaNa');
  assert.equal(fields.number.value, '၀၀၁၂၃၄');
  assert.equal(
    new dom.window.FormData(root.closest('form')).get('nrc'),
    cases[0].mm,
  );

  dom.window.close();
});

test('configured data replaces browser choices and drives localized submission', () => {
  const [configured] = JSON.parse(
    execFileSync(
      process.env.PHP_BINARY || 'php',
      ['scripts/render-fixtures.php', '--configured'],
      { encoding: 'utf8' },
    ),
  );
  const { dom, root, fields } = setup(configured);

  assert.deepEqual(
    [...fields.state.options].map((option) => option.value),
    ['', '12', '99'],
  );
  assert.deepEqual(
    [...fields.township.options].map((option) => option.value),
    ['', 'TestCode'],
  );
  assert.equal(
    new dom.window.FormData(root.closest('form')).get('nrc'),
    '12/TestCode(P)001234',
  );

  dom.window.NrcKit.setLanguage(root, 'mm');

  assert.equal(fields.township.selectedOptions[0].text, 'ကခဂ');
  assert.equal(
    new dom.window.FormData(root.closest('form')).get('nrc'),
    '၁၂/ကခဂ(ပြု)၀၀၁၂၃၄',
  );

  fields.state.value = '99';
  fields.state.dispatchEvent(new dom.window.Event('change'));

  assert.equal(fields.township.value, 'TestCode');
  assert.equal(
    new dom.window.FormData(root.closest('form')).get('nrc'),
    '၉၉/ကခဂ(ပြု)၀၀၁၂၃၄',
  );
  assert.equal(fields.number.checkValidity(), true);

  dom.window.close();
});
