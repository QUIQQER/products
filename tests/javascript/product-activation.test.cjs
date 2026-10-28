const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadModule(path, modules, globals = {}) {
    let definition;
    const filename = resolve(__dirname, '../../bin', `${path}.js`);
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        Class: function (value) { return value; },
        ...globals,
        define(name, dependencies, factory) {
            definition = factory(...dependencies.map(dependency => modules[dependency]));
        }
    }, {filename});
    return definition;
}

function createActivation(count = 2) {
    const requests = [];
    const dialogs = [];
    const elements = [];
    const document = {activeElement: null};
    const createElement = tag => {
        const listeners = new Map();
        const element = {
            tag, children: [], attributes: new Map(), className: '',
            setAttribute(name, value) { this.attributes.set(name, value); },
            getAttribute(name) { return this.attributes.get(name); },
            append(...children) {
                children.forEach(child => {this.children.push(child); child.parentElement = this;});
            },
            appendChild(child) { this.append(child); },
            addEventListener(name, listener) { listeners.set(name, listener); },
            dispatch(name, event) { listeners.get(name)?.(event); },
            click() { this.dispatch('click', {}); },
            focus() { document.activeElement = this; },
            querySelector() { return null; }
        };
        elements.push(element);
        return element;
    };
    document.createElement = createElement;
    const Root = createElement('div');
    const Content = createElement('div');
    let Window;
    const Activation = loadModule('utils/Activation', {
        Ajax: {
            get(endpoint, success, options) { requests.push({method: 'get', endpoint, success, options}); },
            post(endpoint, success, options) { requests.push({method: 'post', endpoint, success, options}); }
        },
        Locale: {get: (group, key) => key},
        'qui/controls/windows/SimpleWindow': class {
            constructor(options) { dialogs.push(options); this.options = options; Window = this; }
            getContent() { return Content; }
            getElm() { return Root; }
            open() { this.options.events.onOpen(); }
            close() { this.options.events.onClose(); }
        }
    }, {document});
    const start = async (ids = [21, 25]) => {
        const promise = Activation.activate(ids);
        assert.equal(requests.length, 1);
        assert.equal(requests[0].endpoint, 'package_quiqqer_products_ajax_products_getInactiveImageCount');
        assert.deepEqual(JSON.parse(requests[0].options.productIds), ids);
        requests[0].success(count);
        await Promise.resolve();
        return {promise};
    };
    const node = name => elements.find(element => element.getAttribute('data-name') === name);
    return {Activation, requests, dialogs, elements, Root, Content, document, start, node, close: () => Window.close()};
}

test('products without inactive images activate directly without a dialog', async () => {
    const h = createActivation(0);
    const {promise} = await h.start();
    assert.equal(h.dialogs.length, 0);
    assert.equal(h.requests.length, 2);
    assert.equal(h.requests[1].method, 'post');
    assert.equal(h.requests[1].options.activateImages, 0);
    h.requests[1].success();
    await promise;
});

test('confirming images activates the selected products with a numeric opt-in', async () => {
    const h = createActivation();
    const {promise} = await h.start();
    assert.equal(h.dialogs.length, 1);
    assert.equal(h.requests.length, 1);
    h.node('withImages').click();
    await Promise.resolve();
    assert.equal(h.requests[1].endpoint, 'package_quiqqer_products_ajax_products_activate');
    assert.equal(h.requests[1].options.activateImages, 1);
    assert.deepEqual(JSON.parse(h.requests[1].options.productId), [21, 25]);
    h.requests[1].success();
    await promise;
});

test('choosing without images activates products; cancel, Escape and close do not', async () => {
    for (const action of ['withoutImages', 'cancel', 'escape', 'close']) {
        const h = createActivation();
        const {promise} = await h.start();
        if (action === 'escape') {
            h.Root.dispatch('keydown', {key: 'Escape', preventDefault() {}, stopPropagation() {}});
        } else if (action === 'close') {
            h.close();
        } else {
            h.node(action).click();
        }
        await Promise.resolve();
        if (action === 'withoutImages') {
            assert.equal(h.requests.length, 2);
            assert.equal(h.requests[1].options.activateImages, 0);
            h.requests[1].success();
        } else {
            assert.equal(h.requests.length, 1);
        }
        await promise;
    }
});

test('failed inspection or activation rejects without issuing extra requests', async () => {
    for (const stage of ['inspection', 'activation']) {
        const h = createActivation(0);
        const promise = h.Activation.activate([21]);
        const rejected = assert.rejects(promise, /permission denied/);
        if (stage === 'activation') {
            h.requests[0].success(0);
            await Promise.resolve();
        }
        h.requests.at(-1).options.onError(new Error('permission denied'));
        await rejected;
        assert.equal(h.dialogs.length, 0);
        assert.equal(h.requests.length, stage === 'inspection' ? 1 : 2);
    }
});

test('empty selections do not inspect or activate anything', async () => {
    const h = createActivation();
    await h.Activation.activate([]);
    assert.equal(h.requests.length, 0);
});

test('SimpleWindow has centered content, separate actions and native buttons', async () => {
    const h = createActivation(1);
    const {promise} = await h.start();
    assert.equal(h.dialogs[0].resizable, false);
    assert.equal(h.node('actions').children[0], h.node('cancel'));
    assert.deepEqual(h.node('activationChoices').children, [h.node('withoutImages'), h.node('withImages')]);
    assert.equal(h.node('icon').getAttribute('aria-hidden'), 'true');
    assert.equal(h.node('information').textContent, 'products.activation.images.information.single');
    assert.equal(h.Root.getAttribute('role'), 'dialog');
    assert.equal(h.Root.getAttribute('aria-labelledby'), h.node('heading').id);
    assert.equal(h.document.activeElement, h.node('withImages'));
    for (const name of ['cancel', 'withoutImages', 'withImages']) {
        assert.equal(h.node(name).tag, 'button');
        assert.equal(h.node(name).type, 'button');
    }
    h.node('cancel').click();
    await promise;
});

test('Tab wraps within the dialog and closing restores the original focus', async () => {
    const h = createActivation();
    const ReturnFocus = {isConnected: true, focus() {h.document.activeElement = this;}};
    h.document.activeElement = ReturnFocus;
    const {promise} = await h.start();
    let prevented = false;
    h.node('cancel').focus();
    h.Root.dispatch('keydown', {key: 'Tab', shiftKey: true, preventDefault() {prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(h.document.activeElement, h.node('withImages'));
    h.Root.dispatch('keydown', {key: 'Tab', preventDefault() {}});
    assert.equal(h.document.activeElement, h.node('cancel'));
    h.close();
    await promise;
    assert.equal(h.document.activeElement, ReturnFocus);
});

test('single-product and bulk APIs both use the shared confirmation flow', async () => {
    const calls = [];
    const modules = {
        'package/quiqqer/products/bin/utils/Activation': {
            activate: ids => { calls.push(Array.from(ids)); return Promise.resolve(); }
        }
    };
    const Product = loadModule('classes/Product', modules);
    let refreshed = false;
    await Product.activate.call({
        getId: () => 21,
        refresh: () => { refreshed = true; return Promise.resolve(); }
    });
    const Products = loadModule('classes/Products', modules);
    await Products.activateChildren([21, 25]);
    assert.deepEqual(calls, [[21], [21, 25]]);
    assert.equal(refreshed, true);
});

test('bulk variant activation uses the shared API and cleans up its loader', async () => {
    const calls = [];
    const Panel = loadModule('controls/products/ProductVariant', {
        Locale: {get: () => ''},
        'package/quiqqer/products/bin/Products': {
            activateChildren: ids => { calls.push(Array.from(ids)); return Promise.resolve(); }
        }
    });
    let loading = false;
    let refreshed = false;
    await Panel.$activateVariants.call({
        Loader: {show: () => loading = true},
        $Grid: {getSelectedData: () => [{id: 31}, {id: 33}]},
        refreshVariantGrid: () => refreshed = true,
        $loaderHide: () => loading = false
    });
    assert.deepEqual(calls, [[31, 33]]);
    assert.equal(loading, false);
    assert.equal(refreshed, true);
});
