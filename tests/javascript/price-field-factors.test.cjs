const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function createEntry(id) {
    const defaults = {
        active: '',
        multiplier: '1',
        'source-field': '1',
        'update-on-save': '',
        'rounding-vat': '0',
        'rounding-type': 'none',
        'rounding-custom': '',
        'surcharge-amount': '0',
        'surcharge-priority': 'beforeRounding'
    };
    const inputs = {};

    for (const [name, initialValue] of Object.entries(defaults)) {
        let value = initialValue;
        inputs[name] = {
            get value() { return value; },
            set value(next) { value = String(next); },
            checked: name === 'update-on-save'
        };
    }

    Object.defineProperty(inputs['source-field'], 'selectedOptions', {
        get: () => [{textContent: inputs['source-field'].value === '3' ? 'Einkaufspreis' : 'Preis'}]
    });
    inputs.configuration = {hidden: true, disabled: true};
    inputs.summary = {textContent: ''};
    inputs['rounding-options'] = {hidden: true};
    inputs['rounding-toggle'] = {
        attributes: {'aria-expanded': 'false'},
        setAttribute(name, value) { this.attributes[name] = value; },
        getAttribute(name) { return this.attributes[name]; }
    };
    const entry = {
        dataset: {id: String(id)},
        classList: {toggle() {}},
        querySelector: selector => inputs[selector.match(/\[data-name="([^"]+)"\]/)[1]]
    };
    inputs['rounding-toggle'].closest = () => entry;

    return {entry, inputs};
}

async function loadSettings(settings, categoryId = null) {
    let definition;
    let rendered;
    let hidden = false;
    const entries = [createEntry(7), createEntry(8)];
    const input = {
        value: typeof settings === 'string' ? settings : JSON.stringify(settings),
        after() {},
        dispatchEvent() {}
    };
    const listeners = {};
    const priority = categoryId ? {value: '0'} : null;
    const root = {
        dataset: {},
        contains: () => true,
        appendChild() {},
        querySelector: selector => selector.includes('category-priority') ? priority : {},
        querySelectorAll: () => entries.map(({entry}) => entry),
        addEventListener: (name, handler) => listeners[name] = handler
    };
    const filename = resolve(__dirname, '../../bin/controls/settings/PriceFieldFactors.js');

    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) { return value; },
        document: {createElement: () => root},
        Promise,
        Event,
        define(name, modules, factory) {
            definition = factory(...modules.map(module => ({
                'qui/controls/buttons/Button': function () { this.inject = () => {}; },
                Locale: {
                    get: (group, key) => key,
                    getNumberFormatter: options => new Intl.NumberFormat('de', options)
                },
                Mustache: {render: (template, data) => { rendered = data; return ''; }}
            })[module]));
        }
    }, {filename});

    const control = Object.assign(Object.create(definition), {
        getElm: () => input,
        getId: () => 'price-factors-test',
        getAttribute: () => categoryId,
        Loader: {inject() {}, show() {}, hide() { hidden = true; }},
        $getPriceFields: () => Promise.resolve([{id: 7, title: 'UVP', edit: true}, {id: 8, title: 'Angebotspreis', edit: true}]),
        $getVatEntries: () => Promise.resolve([{vat: 19}])
    });
    control.$updateValue = control.$updateValue.bind(control);
    control.$onRoundingToggle = control.$onRoundingToggle.bind(control);
    await control.$onImport();
    assert.equal(hidden, true);

    return {control, input, inputs: entries[0].inputs, inactiveInputs: entries[1].inputs, listeners, priority, rendered};
}

for (const rounding of [undefined, null, {}, {vat: 0, custom: 0}]) {
    test(`loads incomplete rounding settings without losing the price factor: ${JSON.stringify(rounding)}`, async () => {
        const {control, input, inputs, inactiveInputs} = await loadSettings({
            7: {multiplier: 1.5, sourceFieldId: 1, updateOnSave: true, rounding}
        });

        assert.equal(inputs.active.checked, true);
        assert.equal(inputs.multiplier.value, '1.5');
        assert.equal(inputs['source-field'].value, '1');
        assert.equal(inputs['update-on-save'].checked, true);
        assert.equal(inputs['rounding-type'].value, 'none');
        assert.equal(inputs['rounding-vat'].value, '0');
        assert.equal(inputs['rounding-custom'].value, String(rounding?.custom ?? ''));
        assert.equal(inactiveInputs.active.checked, false);
        assert.equal(inputs.configuration.hidden, false);
        assert.equal(inputs.configuration.disabled, false);
        assert.equal(inactiveInputs.configuration.hidden, true);
        assert.equal(inactiveInputs.configuration.disabled, true);

        control.$updateValue();
        const saved = JSON.parse(input.value);
        assert.equal(saved[7].multiplier, 1.5);
        assert.equal(saved[7].sourceFieldId, 1);
        assert.equal(saved[7].updateOnSave, true);
        assert.equal(saved[7].rounding.type, 'none');
        assert.equal(saved[8], undefined);
    });
}

test('preserves configured rounding and surcharges and opens their options', async () => {
    const fieldSettings = {
        multiplier: 2,
        sourceFieldId: 3,
        updateOnSave: false,
        fixedSurchargeAmount: 4,
        fixedSurchargePriority: 'afterRounding',
        rounding: {type: 'commercial_9', vat: '19', custom: '95'}
    };
    const {control, input, inputs} = await loadSettings({7: fieldSettings});

    assert.equal(inputs['rounding-options'].hidden, false);
    assert.equal(inputs['rounding-toggle'].getAttribute('aria-expanded'), 'true');
    control.$updateValue();
    assert.deepEqual(JSON.parse(input.value)[7], fieldSettings);
});

test('activation enables the card and deactivation removes only its saved rule', async () => {
    const {input, inputs, listeners} = await loadSettings({
        7: {multiplier: 1.5, sourceFieldId: 1, updateOnSave: true},
        8: {multiplier: 0.9, sourceFieldId: 1, updateOnSave: false}
    });
    inputs.active.checked = false;
    listeners.change();
    assert.equal(inputs.configuration.hidden, true);
    assert.equal(inputs.configuration.disabled, true);
    assert.equal(JSON.parse(input.value)[7], undefined);
    assert.equal(JSON.parse(input.value)[8].multiplier, 0.9);

    inputs.active.checked = true;
    listeners.change();
    assert.equal(inputs.configuration.hidden, false);
    assert.equal(inputs.configuration.disabled, false);
    assert.equal(JSON.parse(input.value)[7].multiplier, 1.5);
});

test('input changes update the localized summary and keep the selected source and surcharge', async () => {
    const {inputs, input, listeners} = await loadSettings({7: {multiplier: 1.5, sourceFieldId: 3}});
    assert.equal(inputs.summary.textContent, 'Einkaufspreis × 1,5');
    inputs.multiplier.value = '1.75';
    inputs['surcharge-amount'].value = '2.5';
    listeners.input();
    assert.equal(inputs.summary.textContent, 'Einkaufspreis × 1,75');
    assert.equal(JSON.parse(input.value)[7].multiplier, 1.75);
    assert.equal(JSON.parse(input.value)[7].fixedSurchargeAmount, 2.5);
    assert.equal(JSON.parse(input.value)[7].sourceFieldId, 3);
});

test('category priority is retained and zero VAT is offered without changing settings on load', async () => {
    const settings = {categoryPriority: 12, 7: {multiplier: 1.5, sourceFieldId: 1}};
    const {control, input, priority, rendered} = await loadSettings(settings, 42);
    assert.equal(input.value, JSON.stringify(settings));
    assert.equal(priority.value, 12);
    assert.equal(rendered.vatEntries[0].vat, 0);
    assert.equal(rendered.priceFields[0].sourceFields.length, 2);
    assert.notEqual(rendered.priceFields[0].configurationId, rendered.priceFields[1].configurationId);
    control.$updateValue();
    assert.equal(JSON.parse(input.value).categoryPriority, 12);
});

test('rounding button toggles its panel and ARIA state without changing saved values', async () => {
    const {input, inputs, listeners} = await loadSettings({7: {multiplier: 1.5, sourceFieldId: 1}});
    const original = input.value;
    const target = {closest: () => inputs['rounding-toggle']};

    listeners.click({target});
    assert.equal(inputs['rounding-options'].hidden, false);
    assert.equal(inputs['rounding-toggle'].getAttribute('aria-expanded'), 'true');

    listeners.click({target});
    assert.equal(inputs['rounding-options'].hidden, true);
    assert.equal(inputs['rounding-toggle'].getAttribute('aria-expanded'), 'false');
    assert.equal(input.value, original);
});

for (const settings of ['invalid-json', 'null', '[]', '']) {
    test(`invalid or empty configuration leaves cards disabled: ${settings}`, async () => {
        const {inputs, inactiveInputs} = await loadSettings(settings);
        assert.equal(inputs.active.checked, false);
        assert.equal(inputs.configuration.disabled, true);
        assert.equal(inactiveInputs.configuration.hidden, true);
    });
}
