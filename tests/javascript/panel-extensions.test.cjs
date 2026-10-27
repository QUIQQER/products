const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadPanel() {
    let definition;
    let options;
    let resolveReady;
    let rejectReady;
    const actions = [];
    const ready = new Promise((resolve, reject) => {
        resolveReady = resolve;
        rejectReady = reject;
    });
    const Search = function (searchOptions) {
        options = searchOptions;
        this.inject = () => actions.push('inject');
        this.whenReady = () => ready;
        this.getCategoriesId = () => 'product-categories-test';
        this.show = () => {actions.push('showSearch'); return Promise.resolve();};
    };
    const filename = resolve(__dirname, '../../bin/controls/products/Panel.js');
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) {
            return value;
        },
        console: {error: () => actions.push('error')},
        define(name, modules, factory) {
            definition = factory(...modules.map(module => ({
                'qui/QUI': {
                    fireEvent(name, [panel, searchOptions]) {
                        actions.push(name);
                        if (name === 'quiqqerProductsPanelSearchCreate') {
                            searchOptions.searchParams = {extensions: {mode: 'inventory'}};
                        }
                    }
                },
                Locale: {get: (group, key) => key},
                'package/quiqqer/productsearch/bin/controls/products/search/Search': Search
            })[module]));
        }
    }, {filename});
    const content = {style: {visibility: 'hidden'}, setAttribute() {}};
    const toolbar = {style: {visibility: 'hidden'}};
    const panel = Object.assign(Object.create(definition), {
        $Search: null,
        $CategoriesButton: {getElm: () => ({setAttribute() {}})},
        $ButtonAdd: {name: 'add'},
        $Actions: {name: 'actions'},
        getContent: () => content,
        getButtonBar: () => ({getElm: () => toolbar}),
        refresh: () => actions.push('refresh'),
        Loader: {hide: () => actions.push('hideLoader')}
    });
    return {panel, actions, getOptions: () => options, resolveReady, rejectReady};
}

test('panel exposes the search and lets extensions configure it before injection', async () => {
    const {panel, actions, getOptions, resolveReady} = loadPanel();
    assert.equal(panel.getSearch(), null);
    panel.$onInject();
    assert.ok(panel.getSearch());
    assert.equal(getOptions().context, 'products.panel');
    assert.equal(getOptions().layout, 'panel');
    assert.equal(getOptions().freeTextSearch, false);
    assert.equal(getOptions().gridOptions.buttons[0], panel.$ButtonAdd);
    assert.equal(getOptions().gridOptions.buttons[1], panel.$Actions);
    assert.equal(getOptions().searchParams.extensions.mode, 'inventory');
    assert.equal(typeof getOptions().events.onDblClick, 'function');
    assert.deepEqual(actions, ['quiqqerProductsPanelSearchCreate', 'inject']);
    resolveReady();
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(actions, [
        'quiqqerProductsPanelSearchCreate',
        'inject',
        'quiqqerProductsPanelSearchReady',
        'refresh',
        'showSearch',
        'hideLoader'
    ]);
});

test('grid actions reflect zero, one and multiple selected products', () => {
    const {panel} = loadPanel();
    const states = {};
    panel.$Actions = {
        getChildren: () => ['edit', 'copy', 'toggleActive', 'delete'].map(name => ({
            getAttribute: () => name,
            setAttribute() {},
            enable: () => states[name] = true,
            disable: () => states[name] = false
        }))
    };
    panel.$selectionIsActive = () => false;
    panel.$setActionState(0);
    assert.ok(Object.values(states).every(value => value === false));
    panel.$setActionState(1);
    assert.ok(Object.values(states).every(value => value === true));
    panel.$setActionState(2);
    assert.equal(states.edit, false);
    assert.equal(states.copy, false);
    assert.equal(states.delete, true);
    assert.equal(states.toggleActive, true);
});

test('status action deactivates an all-active selection and activates mixed selections', () => {
    const {panel} = loadPanel();
    let rows = [{active: 1}, {active: '1'}];
    panel.$Search = {getResult: () => ({getGrid: () => ({getSelectedData: () => rows})})};
    let operation;
    panel.activateChildren = () => operation = 'activate';
    panel.deactivateChildren = () => operation = 'deactivate';
    panel.toggleActiveChildren();
    assert.equal(operation, 'deactivate');
    rows = [{active: 1}, {active: 0}];
    panel.toggleActiveChildren();
    assert.equal(operation, 'activate');
});

test('initial content remains hidden until the first search has completed', async () => {
    const {panel, resolveReady} = loadPanel();
    let resolveData;
    panel.refresh = () => new Promise(resolve => resolveData = resolve);
    panel.$onInject();
    resolveReady();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(panel.getContent().style.visibility, 'hidden');
    assert.equal(panel.getButtonBar().getElm().style.visibility, 'hidden');
    resolveData();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(panel.getContent().style.visibility, '');
    assert.equal(panel.getButtonBar().getElm().style.visibility, '');
});

test('failed readiness clears the loader without executing the initial search', async () => {
    const {panel, actions, rejectReady} = loadPanel();
    panel.$onInject();
    rejectReady(new Error('unavailable'));
    await new Promise(resolve => setImmediate(resolve));
    assert.ok(actions.includes('hideLoader'));
    assert.ok(actions.includes('error'));
    assert.ok(!actions.includes('refresh'));
    assert.ok(!actions.includes('quiqqerProductsPanelSearchReady'));
});

test('superseded grid resize animations do not keep the initial loader visible', async () => {
    const {panel, actions, resolveReady} = loadPanel();
    panel.$initialLoad = true;
    panel.$onInject();
    panel.getSearch().show = () => {
        actions.push('showSearch');
        // Core grid animations can be replaced by a resize without settling their original promise.
        return new Promise(() => {});
    };

    resolveReady();
    await new Promise(resolve => setImmediate(resolve));

    assert.ok(actions.includes('refresh'));
    assert.ok(actions.includes('showSearch'));
    assert.ok(actions.includes('hideLoader'));
    assert.equal(panel.$initialLoad, false);
    assert.equal(panel.getContent().style.visibility, '');
    assert.equal(panel.getButtonBar().getElm().style.visibility, '');
});
