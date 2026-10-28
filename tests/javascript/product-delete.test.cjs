const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadModule(name, modules, globals = {}) {
    let definition;
    const filename = resolve(__dirname, `../../bin/controls/products/${name}.js`);
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) { return value; },
        console: {error() {}},
        ...globals,
        define(name, dependencies, factory) {
            definition = factory(...dependencies.map(dependency => modules[dependency]));
        }
    }, {filename});
    return definition;
}

function createElement(tag) {
    const attributes = new Map();
    return {
        style: {},
        children: [],
        classList: {add() {}, remove() {}},
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name),
        getAttribute: name => attributes.get(name),
        appendChild(child) { this.children.push(child); },
        textContent: '',
        get outerHTML() {
            const escaped = this.textContent.replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
            return `<${tag}>${escaped}${this.children.map(child => child.outerHTML).join('')}</${tag}>`;
        }
    };
}

function createWindow(deleteChildren = () => Promise.resolve()) {
    const errors = [];
    const calls = [];
    const events = [];
    const state = {closed: false, loading: false, disabled: false, success: false};
    let options;
    let handlers;
    const definition = loadModule('DeleteProductWindow', {
        'qui/QUI': {getMessageHandler: () => Promise.resolve({addSuccess: () => state.success = true})},
        Locale: {get: (group, key, params) => params?.products ?? key},
        'package/quiqqer/products/bin/Products': {deleteChildren(ids) {
            calls.push(Array.from(ids));
            return deleteChildren(ids);
        }}
    }, {document: {createElement}, console: {error: error => errors.push(error)}});
    const Submit = {
        getElm: () => createElement('button'),
        disable: () => state.disabled = true,
        enable: () => state.disabled = false
    };
    const window = Object.assign(Object.create(definition), {
        parent: value => options = value,
        addEvents: value => handlers = value,
        getButton: () => Submit,
        Loader: {show: () => state.loading = true, hide: () => state.loading = false},
        close: () => state.closed = true,
        fireEvent: (name, args) => events.push([name, Array.from(args[1])])
    });
    window.initialize({products: [{id: 21, title: '<img src=x onerror=alert(1)>'}, {id: 25, title: 'Other product'}]});
    return {window, state, events, calls, errors, getOptions: () => options, getHandlers: () => handlers};
}

test('shared dialog lists escaped product names and does not delete before confirmation', () => {
    const fixture = createWindow();
    const options = fixture.getOptions();
    assert.equal(options.autoclose, false);
    assert.match(options.information, /21: &lt;img src=x onerror=alert\(1\)&gt;/);
    assert.match(options.information, /25: Other product/);
    assert.ok(!options.information.includes('<img'));
    fixture.getHandlers().onOpen();
    assert.deepEqual(fixture.calls, []);
    assert.deepEqual(fixture.events, []);
});

test('confirming deletes once, closes the shared dialog and reports the deleted IDs', async () => {
    let complete;
    const fixture = createWindow(() => new Promise(resolve => complete = resolve));
    const request = fixture.window.$deleteProducts();
    await fixture.window.$deleteProducts();
    assert.deepEqual(fixture.calls, [[21, 25]]);
    assert.equal(fixture.state.loading, true);
    assert.equal(fixture.state.disabled, true);
    assert.equal(fixture.state.closed, false);
    complete();
    await request;
    assert.equal(fixture.state.closed, true);
    assert.equal(fixture.state.success, true);
    assert.deepEqual(fixture.events, [['deleted', [21, 25]]]);
});

test('a failed deletion keeps the dialog open and allows retrying', async () => {
    let fail = true;
    const fixture = createWindow(() => fail ? Promise.reject(new Error('permission denied')) : Promise.resolve());
    await fixture.window.$deleteProducts();
    assert.equal(fixture.state.closed, false);
    assert.equal(fixture.state.loading, false);
    assert.equal(fixture.state.disabled, false);
    assert.equal(fixture.errors.length, 1);
    assert.deepEqual(fixture.events, []);
    fail = false;
    await fixture.window.$deleteProducts();
    assert.equal(fixture.state.closed, true);
    assert.equal(fixture.calls.length, 2);
});

function createProductPanel(moduleName = 'Product') {
    const buttons = new Map();
    const dialogs = [];
    const requested = [];
    let closed = false;
    let refreshed = 0;
    class Button {
        constructor(options) { this.options = options; this.element = createElement('button'); }
        getElm() { return this.element; }
    }
    class DeleteWindow {
        constructor(options) { dialogs.push(options); }
        open() {}
    }
    const definition = loadModule(moduleName, {
        'qui/QUI': {Controls: {getByType: () => [{refresh: () => {refreshed++; return Promise.resolve();}}]}},
        'qui/controls/buttons/ButtonSwitch': Button,
        Locale: {get: (group, key) => key},
        Users: {getUserBySession: () => ({})},
        'package/quiqqer/products/bin/Products': {getChildren: ids => {
            requested.push(Array.from(ids));
            return Promise.resolve(Array.from(ids, id => ({id, title: 'Panel product'})));
        }},
        'package/quiqqer/products/bin/controls/products/DeleteProductWindow': DeleteWindow
    });
    const panel = Object.assign(Object.create(definition), {
        $Product: {getId: () => 21},
        $CurrentVariant: {getId: () => 99},
        Loader: {show() {}, setAttribute() {}},
        addButton(options) {
            const button = options instanceof Button ? options : new Button(options);
            buttons.set(button.options.name, button);
        },
        getButtons: name => buttons.get(name),
        destroy: () => closed = true,
        refresh: () => {refreshed++; return Promise.resolve();}
    });
    return {panel, buttons, dialogs, requested, isClosed: () => closed, getRefreshes: () => refreshed};
}

test('product toolbar places an accessible trash action to the right of the right-floated copy action', () => {
    const {panel, buttons} = createProductPanel();
    panel.$onCreate();
    const order = [...buttons.keys()];
    assert.ok(order.indexOf('delete') < order.indexOf('copy'));
    const Delete = buttons.get('delete');
    assert.equal(Delete.options.icon, 'fa fa-trash');
    assert.equal(Delete.options.styles.float, 'right');
    assert.equal(Delete.getElm().getAttribute('aria-label'), 'delete');
    assert.equal(Delete.getElm().type, 'button');
    assert.equal(Delete.getElm().getAttribute('tabindex'), undefined);
});

test('toolbar deletion always targets the panel product while a variant is being edited', async () => {
    const fixture = createProductPanel();
    await fixture.panel.deleteProduct();
    assert.deepEqual(fixture.requested, [[21]]);
    assert.equal(fixture.dialogs[0].products[0].id, 21);
    assert.equal(fixture.isClosed(), false);
    fixture.dialogs[0].events.onDeleted();
    assert.equal(fixture.isClosed(), true);
    assert.equal(fixture.getRefreshes(), 1);
});

test('product list uses the same deletion window for single and multiple products', async () => {
    const fixture = createProductPanel('Panel');
    await fixture.panel.deleteChild(21);
    await fixture.panel.deleteChild([21, 25]);
    assert.deepEqual(fixture.requested, [[21], [21, 25]]);
    assert.equal(fixture.dialogs.length, 2);
    assert.equal(fixture.getRefreshes(), 0);
    fixture.dialogs[1].events.onDeleted();
    assert.equal(fixture.getRefreshes(), 1);
    await assert.rejects(fixture.panel.deleteChild([]));
    assert.equal(fixture.dialogs.length, 2);
});
