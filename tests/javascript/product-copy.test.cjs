const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadModule(path, modules, globals = {}) {
    let module;
    const filename = resolve(__dirname, '../../bin', path + '.js');
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (definition) {return definition;},
        ...globals,
        define(name, dependencies, factory) {
            module = factory(...dependencies.map(dependency => modules[dependency]));
        }
    }, {filename});
    return module;
}

function createDialog(product = {}) {
    const elements = [];
    const requests = [];
    const document = {activeElement: null};
    const createElement = tag => {
        const events = new Map();
        const Element = {
            tag, children: [], attributes: new Map(), disabled: false, textContent: '', innerHTML: '',
            setAttribute(name, value) {this.attributes.set(name, value);},
            getAttribute(name) {return this.attributes.get(name);},
            append(...children) {children.forEach(child => this.children.push(child));},
            appendChild(child) {this.append(child);},
            addEventListener(name, listener) {events.set(name, listener);},
            dispatch(name, event) {return events.get(name)?.(event);},
            click() {return this.dispatch('click', {});},
            focus() {document.activeElement = this;},
            getClientRects: () => [{}],
            querySelector: () => Close
        };
        elements.push(Element);
        return Element;
    };
    document.createElement = createElement;
    const Root = createElement('div');
    const Content = createElement('div');
    const Close = createElement('button');
    Close.setAttribute('data-name', 'close');
    let options;
    let Window;
    let closed = false;
    const Copy = loadModule('utils/Copy', {
        Ajax: {post(endpoint, success, options) {requests.push({endpoint, success, options});}},
        Locale: {get: (group, key) => key},
        'package/quiqqer/products/bin/Fields': {FIELD_PRODUCT_NO: 3},
        'qui/controls/windows/SimpleWindow': class {
            constructor(attributes) {options = attributes; Window = this;}
            getElm() {return Root;}
            getContent() {return Content;}
            open() {options.events.onOpen();}
            close() {closed = true; options.events.onClose(); return Promise.resolve();}
        }
    }, {document});
    const promise = Copy.copy(21, product);
    const node = name => elements.find(Element => Element.getAttribute('data-name') === name);
    const radios = elements.filter(Element => Element.type === 'radio');
    const select = mode => radios.forEach(Radio => Radio.checked = Radio.value === mode);
    return {promise, node, radios, select, requests, Root, Content, Close, document, options,
        close: () => Window.close(), isClosed: () => closed};
}

test('copy dialog offers three exclusive radio choices and defaults to independent images', async () => {
    const h = createDialog();
    assert.deepEqual(h.radios.map(Radio => Radio.value), ['copy', 'share', 'empty']);
    assert.equal(h.radios.filter(Radio => Radio.checked).length, 1);
    assert.equal(h.radios[0].checked, true);
    assert.equal(new Set(h.radios.map(Radio => Radio.name)).size, 1);
    assert.equal(h.node('options').tag, 'fieldset');
    assert.equal(h.node('question').tag, 'legend');
    assert.equal(h.node('icon').getAttribute('aria-hidden'), 'true');
    assert.equal(h.document.activeElement, h.node('submit'));
    assert.equal(h.requests.length, 0);
    h.node('cancel').click();
    assert.equal(await h.promise, null);
});

test('each media choice is posted only after confirmation and returns the new product ID', async () => {
    for (const mode of ['copy', 'share', 'empty']) {
        const h = createDialog();
        h.select(mode);
        h.node('submit').click();
        assert.equal(h.requests.length, 1);
        assert.equal(h.requests[0].endpoint, 'package_quiqqer_products_ajax_products_copy');
        assert.equal(h.requests[0].options.productId, 21);
        assert.equal(h.requests[0].options.mediaMode, mode);
        h.requests[0].success(52);
        assert.equal(await h.promise, 52);
        assert.equal(h.isClosed(), true);
    }
});

test('header displays the source name and article number as plain text', async () => {
    const h = createDialog({title: '<img src=x onerror=alert(1)>', fields: [{id: 3, value: 'A-123'}]});
    assert.equal(h.node('product').textContent, '<img src=x onerror=alert(1)>');
    assert.equal(h.node('product').innerHTML, '');
    assert.equal(h.node('articleNo').textContent, 'productNo: A-123');
    assert.deepEqual(h.node('header').children, [h.node('icon'), h.node('heading'), h.node('product'), h.node('metadata')]);
    h.close();
    await h.promise;
});

test('cancel, Escape and the close action do not copy a product', async () => {
    for (const action of ['cancel', 'escape', 'close']) {
        const h = createDialog();
        if (action === 'cancel') h.node('cancel').click();
        else if (action === 'close') h.close();
        else h.Root.dispatch('keydown', {key: 'Escape', preventDefault() {}, stopPropagation() {}});
        assert.equal(await h.promise, null);
        assert.equal(h.requests.length, 0);
    }
});

test('pending copying prevents duplicate requests and closing, and disables all choices', async () => {
    const h = createDialog();
    h.node('submit').click();
    h.node('submit').click();
    h.close();
    assert.equal(h.requests.length, 1);
    assert.equal(h.isClosed(), false);
    assert.equal(h.Root.getAttribute('aria-busy'), 'true');
    assert.equal(h.node('cancel').disabled, true);
    assert.equal(h.Close.disabled, true);
    assert.equal(h.radios.every(Radio => Radio.disabled), true);
    assert.equal(h.document.activeElement, h.Root);
    let prevented = false;
    h.Root.dispatch('keydown', {key: 'Tab', preventDefault: () => prevented = true});
    assert.equal(prevented, true);
    h.requests[0].success(52);
    assert.equal(await h.promise, 52);
    h.node('submit').click();
    assert.equal(h.requests.length, 1);
});

test('a failed copy shows plain text, keeps the dialog open and allows choosing another mode', async () => {
    const h = createDialog();
    h.node('submit').click();
    h.requests[0].options.onError(new Error('<img src=x onerror=alert(1)>'));
    await Promise.resolve();
    assert.equal(h.isClosed(), false);
    assert.equal(h.node('status').textContent, '<img src=x onerror=alert(1)>');
    assert.equal(h.node('status').innerHTML, '');
    assert.equal(h.node('submit').disabled, false);
    assert.equal(h.Close.disabled, false);
    assert.equal(h.radios.every(Radio => !Radio.disabled), true);
    h.select('empty');
    h.node('submit').click();
    assert.equal(h.requests[1].options.mediaMode, 'empty');
    h.requests[1].success(55);
    assert.equal(await h.promise, 55);
});

test('keyboard focus wraps between the close button and the copy action', async () => {
    const h = createDialog();
    h.Root.dispatch('keydown', {key: 'Tab', preventDefault() {}});
    assert.equal(h.document.activeElement, h.Close);
    h.Root.dispatch('keydown', {key: 'Tab', shiftKey: true, preventDefault() {}});
    assert.equal(h.document.activeElement, h.node('submit'));
    h.close();
    await h.promise;
});

test('handler, product editor and product list use the same copy flow and respect cancellation', async () => {
    const copied = [];
    const opened = [];
    let result = null;
    const modules = {
        Locale: {get: (group, key) => key},
        Users: {getUserBySession: () => ({})},
        'package/quiqqer/products/bin/utils/Copy': {copy: id => {copied.push(id); return Promise.resolve(result);}}
    };
    const Handler = loadModule('classes/Products', modules);
    Handler.getChild = id => Promise.resolve({id, title: 'Source product'});
    modules['package/quiqqer/products/bin/Products'] = {
        copy: id => Handler.copy(id),
        openProduct: id => {opened.push(id); return Promise.resolve();}
    };
    const Product = loadModule('controls/products/Product', modules);
    const List = loadModule('controls/products/Panel', modules);
    const Panel = {$Product: {getId: () => 21}};
    let loading = false;
    let refreshed = 0;
    const ProductList = {
        Loader: {show: () => loading = true, hide: () => loading = false},
        refresh: () => refreshed++
    };
    await Product.copy.call(Panel);
    await List.copyChild.call(ProductList, 25);
    assert.deepEqual(copied, [21, 25]);
    assert.deepEqual(opened, []);
    assert.equal(loading, false);
    result = 52;
    await Product.copy.call(Panel);
    await List.copyChild.call(ProductList, 25);
    assert.deepEqual(opened, [52, 52]);
    assert.equal(loading, false);
    assert.equal(refreshed, 1);
});
