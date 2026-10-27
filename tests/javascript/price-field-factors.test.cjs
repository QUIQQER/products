const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function createEntry(id) {
    const inputs = {};
    const defaults = {
        active: '',
        multiplier: '',
        sourceFieldId: '1',
        update_on_save: '',
        rounding_vat: '0',
        rounding_type: 'none',
        decimal_custom_value: '',
        fixedSurchargeAmount: '',
        fixedSurchargePriority: 'beforeRounding'
    };

    for (const [name, value] of Object.entries(defaults)) {
        inputs[name] = {value, checked: name === 'update_on_save', addEvent() {}};
    }

    const entry = {
        get: () => String(id),
        getElement(selector) {
            const name = selector.match(/\[name="([^"]+)"\]/)?.[1];
            return name ? inputs[name] : {removeClass() {}};
        }
    };

    return {entry, inputs};
}

async function loadSettings(settings) {
    let definition;
    let ready;
    const entries = [createEntry(7), createEntry(8)];
    const input = {value: JSON.stringify(settings)};
    const root = {
        getElement: () => null,
        getElements: () => entries.map(({entry}) => entry),
        set() {}
    };
    const filename = resolve(__dirname, '../../bin/controls/settings/PriceFieldFactors.js');

    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) { return value; },
        Element: function () { return {inject: () => root}; },
        JSON: {decode: JSON.parse, encode: JSON.stringify},
        Promise: {
            all(values) {
                return {
                    then(callback) {
                        ready = Promise.all(values).then(callback);
                        return ready;
                    }
                };
            }
        },
        define(name, modules, factory) {
            definition = factory(...modules.map(module => ({
                'qui/controls/buttons/Button': function () { this.inject = () => {}; },
                Locale: {get: (group, key) => key},
                Mustache: {render: () => ''}
            })[module]));
        }
    }, {filename});

    const control = Object.assign(Object.create(definition), {
        getElm: () => input,
        getAttribute: () => null,
        Loader: {inject() {}, show() {}},
        $getPriceFields: () => Promise.resolve([]),
        $getVatEntries: () => Promise.resolve([])
    });

    control.$onImport();
    await ready;

    return {control, input, inputs: entries[0].inputs, inactiveInputs: entries[1].inputs};
}

for (const rounding of [undefined, null, {}, {vat: 0, custom: 0}]) {
    test(`loads incomplete rounding settings without losing the price factor: ${JSON.stringify(rounding)}`, async () => {
        const {control, input, inputs, inactiveInputs} = await loadSettings({
            7: {multiplier: 1.5, sourceFieldId: 1, updateOnSave: true, rounding}
        });

        assert.equal(inputs.active.checked, true);
        assert.equal(inputs.multiplier.value, 1.5);
        assert.equal(inputs.sourceFieldId.value, 1);
        assert.equal(inputs.update_on_save.checked, true);
        assert.equal(inputs.rounding_type.value, 'none');
        assert.equal(inputs.rounding_vat.value, 0);
        assert.equal(inputs.decimal_custom_value.value, rounding?.custom ?? '');
        assert.equal(inactiveInputs.active.checked, false);

        inputs.decimal_custom_value.value = String(inputs.decimal_custom_value.value);
        control.$updateValue();

        const saved = JSON.parse(input.value);
        assert.equal(saved[7].multiplier, 1.5);
        assert.equal(saved[7].sourceFieldId, 1);
        assert.equal(saved[7].updateOnSave, true);
        assert.equal(saved[7].rounding.type, 'none');
        assert.equal(saved[8], undefined);
    });
}

test('preserves explicitly configured rounding and surcharges', async () => {
    const {inputs} = await loadSettings({
        7: {
            multiplier: 2,
            sourceFieldId: 3,
            updateOnSave: false,
            fixedSurchargeAmount: 4,
            fixedSurchargePriority: 'afterRounding',
            rounding: {type: 'commercial_9', vat: 19, custom: '95'}
        }
    });

    assert.equal(inputs.rounding_type.value, 'commercial_9');
    assert.equal(inputs.rounding_vat.value, 19);
    assert.equal(inputs.decimal_custom_value.value, '95');
    assert.equal(inputs.update_on_save.checked, false);
    assert.equal(inputs.fixedSurchargeAmount.value, 4);
    assert.equal(inputs.fixedSurchargePriority.value, 'afterRounding');
});
