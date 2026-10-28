const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadCreate({categories = false, config = {}, required = [], data = {}} = {}) {
    let definition;
    let configRequests = 0;
    let selected = [];
    let created;
    let opened = false;
    const events = [];
    const actions = [];
    const Message = {
        hidden: true,
        textContent: '',
        focus: () => actions.push('focus'),
        scrollIntoView: () => actions.push('scroll')
    };
    const Container = {appendChild: node => actions.push(node)};
    const CategoryField = {closest: () => Container};
    const options = [];
    const ProductTypes = {getElements: () => options};
    const Form = {
        querySelectorAll: () => required,
        querySelector: selector => ({value: selector.includes('main-category') ? '0' : ''})
    };
    const Elm = {
        querySelector: selector => ({
            '[data-name="validation-message"]': Message,
            '[data-name="categories"]': CategoryField,
            '[data-name="create-form"]': Form
        })[selector],
        getElement: selector => selector.includes('product-types') ? ProductTypes : {}
    };
    class Products {
        getTypes() { return Promise.resolve([]); }
        isAutoGenerateNextArticleNo() { return Promise.resolve(false); }
        createChild(...params) {
            created = params;
            return Promise.resolve({id: 123});
        }
    }
    class Fields {
        getSystemFields() { return Promise.resolve([]); }
        getStandardFields() { return Promise.resolve([]); }
    }
    class Element {
        constructor(tag, attributes) { this.attributes = attributes; }
        inject() { options.push(this); return this; }
        get(name) { return name === 'text' ? this.attributes.html : ''; }
    }
    const filename = resolve(__dirname, '../../bin/controls/products/Create.js');
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) { return value; },
        Element,
        define(name, modules, factory) {
            definition = factory(...modules.map(module => ({
                'qui/QUI': {
                    parse: () => Promise.resolve(),
                    Controls: {getById: () => ({open: () => opened = true})}
                },
                'qui/utils/Form': {getFormData: () => data},
                Locale: {get: (group, key, params) => params?.fieldtitle ?? key},
                Packages: {getConfig: () => {configRequests++; return Promise.resolve(config);}},
                'package/quiqqer/products/bin/classes/Products': Products,
                'package/quiqqer/products/bin/classes/Fields': Fields
            })[module]));
        }
    }, {filename});
    const control = Object.assign(Object.create(definition), {
        getElm: () => Elm,
        getAttribute: name => name === 'categories' ? categories : false,
        fireEvent: name => events.push([name, [...selected]]),
        $Categories: {
            addCategory: id => selected.push(id),
            getValue: () => selected.join(',')
        }
    });
    return {
        control, Message, Container, actions, events,
        clearCategories: () => selected = [],
        getSelected: () => selected,
        getCreated: () => created,
        getConfigRequests: () => configRequests,
        wasOpened: () => opened
    };
}

for (const [name, categories, config, expected] of [
    ['all products', false, {}, [0]],
    ['empty selection', [], {}, [0]],
    ['configured main category', [], {products: {mainCategory: '8'}}, [8]],
    ['existing selection', [4, 9], {products: {mainCategory: 8}}, [4, 9]],
    ['explicit all products', [0], {products: {mainCategory: 8}}, [0]],
    ['invalid configuration', false, {products: {mainCategory: -1}}, [0]]
]) {
    test(`initialization selects ${name} before reporting loaded`, async () => {
        const fixture = loadCreate({categories, config});
        await fixture.control.$onInject();
        assert.deepEqual(fixture.getSelected(), expected);
        assert.deepEqual(fixture.events, [['loaded', expected]]);
        assert.equal(fixture.getConfigRequests(), Array.isArray(categories) && categories.length ? 0 : 1);
    });
}

test('a missing category is shown and scrolled into view without creating a product', async () => {
    const fixture = loadCreate();
    await fixture.control.$onInject();
    fixture.clearCategories();
    await assert.rejects(fixture.control.submit(), error => error === 'No categories');
    assert.equal(fixture.Message.hidden, false);
    assert.equal(fixture.Message.textContent, 'message.product.create.missing.category');
    assert.deepEqual(fixture.actions, [fixture.Message, 'focus', 'scroll']);
    assert.equal(fixture.getCreated(), undefined);
});

for (const multiLang of [false, true]) {
    test(`an empty ${multiLang ? 'translated' : 'plain'} required field is shown next to the field`, async () => {
        const title = {textContent: 'Product title'};
        const Label = {querySelector: () => title};
        const Field = {
            value: multiLang ? JSON.stringify({de: '', en: 'Title'}) : '',
            dataset: {qui: multiLang ? 'controls/lang/InputMultiLang' : 'input', quiid: 'field-title'},
            closest: () => Label
        };
        const fixture = loadCreate({categories: [0], required: [Field]});
        Field.closest = selector => selector.includes('field-label') ? Label : fixture.Container;
        await fixture.control.$onInject();
        await assert.rejects(fixture.control.submit(), error => error === 'Please fill out all fields correctly');
        assert.equal(fixture.Message.hidden, false);
        assert.equal(fixture.Message.textContent, 'Product title');
        assert.deepEqual(fixture.actions, [fixture.Message, 'focus', 'scroll']);
        assert.equal(fixture.wasOpened(), multiLang);
        assert.equal(fixture.getCreated(), undefined);
    });
}

test('a valid submission clears the previous error and accepts category zero and zero prices', async () => {
    const fixture = loadCreate({categories: [0], data: {'field-1': '0', 'field-4': 'Title', unrelated: 'ignored'}});
    await fixture.control.$onInject();
    fixture.Message.hidden = false;
    fixture.Message.textContent = 'Previous error';
    const result = await fixture.control.submit();
    assert.equal(result.id, 123);
    assert.equal(fixture.Message.hidden, true);
    assert.equal(fixture.Message.textContent, '');
    const [category, categories, fields, type] = fixture.getCreated();
    assert.equal(category, '0');
    assert.deepEqual(Array.from(categories), ['0']);
    assert.deepEqual({...fields}, {'field-1': '0', 'field-4': 'Title'});
    assert.equal(type, '');
});
