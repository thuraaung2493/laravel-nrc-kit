(function () {
  'use strict';

  if (window.NrcKit) return;

  var instances = new WeakMap();
  var mm = '၀၁၂၃၄၅၆၇၈၉';

  function digits(value, language) {
    var en = value.replace(/[၀-၉]/g, function (d) {
      return String(mm.indexOf(d));
    });

    return language === 'en'
      ? en
      : en.replace(/[0-9]/g, function (d) {
          return mm[Number(d)];
        });
  }

  function init(root) {
    if (instances.has(root)) return instances.get(root);

    var data = JSON.parse(root.querySelector('[data-nrc-data]').textContent);
    var fields = {};
    ['state', 'township', 'type', 'number'].forEach(function (name) {
      fields[name] = root.querySelector('[data-field="' + name + '"]');
    });

    var hidden = root.querySelector('[data-nrc-value]');
    var language = root.dataset.language;
    var initial = {};
    Object.keys(fields).forEach(function (name) {
      initial[name] = fields[name].value;
    });

    function options(field, values) {
      var current = fields[field].value;
      fields[field].replaceChildren();
      [{ value: '', label: data.labels[language].choose }]
        .concat(values)
        .forEach(function (item) {
          fields[field].add(new Option(item.label, item.value));
        });
      fields[field].value = values.some(function (item) {
        return item.value === current;
      })
        ? current
        : '';
    }

    function render() {
      var group = data.groups.find(function (g) {
        return g.state === fields.state.value;
      });
      options(
        'state',
        data.groups.map(function (g) {
          var names = data.states.filter(function (state) {
            return String(state.stateNo) === g.state;
          }).map(function (state) { return state[language]; });

          return { value: g.state, label: names.length ? names.join(' / ') : digits(g.state, language) };
        }),
      );
      options(
        'township',
        (group ? group.townships : []).map(function (t) {
          return { value: t.en, label: t[language] };
        }),
      );
      options(
        'type',
        data.types.map(function (t) {
          return { value: t.en, label: t[language] };
        }),
      );
      fields.number.value = digits(fields.number.value, language);
      fields.number.placeholder = data.labels[language].placeholder;
      Object.keys(fields).forEach(function (name) {
        root.querySelector('[data-label="' + name + '"]').textContent =
          data.labels[language][name];
      });
      root.dataset.language = language;
    }

    function update(emit) {
      var group = data.groups.find(function (g) {
        return g.state === fields.state.value;
      });
      var township =
        group &&
        group.townships.find(function (t) {
          return t.en === fields.township.value;
        });
      var type = data.types.find(function (t) {
        return t.en === fields.type.value;
      });
      var serial = digits(fields.number.value, 'en');
      fields.number.value = digits(serial, language);

      var complete = township && type && /^[0-9]{6}$/.test(serial);
      hidden.value = complete
        ? digits(group.state, language) +
          '/' +
          township[language] +
          '(' +
          type[language] +
          ')' +
          digits(serial, language)
        : '';

      var partial = Object.keys(fields).some(function (name) {
        return fields[name].value !== '';
      });
      fields.number.setCustomValidity(
        partial && !complete ? data.labels[language].invalid : '',
      );

      if (emit) {
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }

    var controller = {
      setLanguage: function (next) {
        if (next !== 'en' && next !== 'mm' && next !== 'auto')
          throw new RangeError('Selector language must be auto, en, or mm.');

        var error = root.querySelector('[role="alert"]');
        var defaultError = error.textContent === data.labels[language].invalid;
        language =
          next === 'auto'
            ? /\/[A-Za-z]+\(/.test(hidden.value)
              ? 'en'
              : hidden.value
                ? 'mm'
                : 'en'
            : next;

        if (defaultError) error.textContent = data.labels[language].invalid;

        render();
        update(true);
      },
    };
    Object.keys(fields).forEach(function (name) {
      fields[name].addEventListener(
        name === 'number' ? 'input' : 'change',
        function () {
          if (name === 'state') render();

          update(true);
        },
      );
    });

    if (hidden.form)
      hidden.form.addEventListener('reset', function (event) {
        setTimeout(function () {
          if (event.defaultPrevented) return;

          fields.state.value = initial.state;
          render();
          fields.township.value = initial.township;
          fields.type.value = initial.type;
          fields.number.value = digits(initial.number, language);
          update(true);
        }, 0);
      });

    instances.set(root, controller);
    update(false);

    return controller;
  }

  window.NrcKit = {
    init: init,
    setLanguage: function (root, language) {
      init(root).setLanguage(language);
    },
  };

  function start() {
    document.querySelectorAll('[data-nrc-selector]').forEach(init);
  }

  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', start);
  else start();
})();
