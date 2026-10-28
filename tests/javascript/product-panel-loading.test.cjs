const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function createFixture() {
    const node = () => ({
        children: [], style: {}, attributes: new Map(), inert: false, isConnected: true,
        classList: {add() {}},
        append(...children) {children.forEach(child => this.appendChild(child));},
        appendChild(child) {this.children.push(child); child.parentElement = this;},
        setAttribute(name, value) {this.attributes.set(name, value);},
        getAttribute(name) {return this.attributes.get(name);},
        contains(child) {return child === this || this.children.some(entry => entry.contains(child));},
        getBoundingClientRect() {return this.bounds;},
        focus() {document.activeElement = this;}
    });
    const document = {createElement: node, body: node()};
    const Root = node();
    const Header = node();
    const Categories = node();
    const Content = node();
    const Overlay = node();
    const Save = node();
    Header.appendChild(Save);
    Root.append(Header, Categories, Content, Overlay);
    Root.bounds = {left: 20, top: 30, width: 1200, height: 900};
    Content.bounds = {left: 220, top: 130, width: 1000, height: 800};
    document.activeElement = Save;
    const loaderEvents = new Map();
    const panelEvents = new Map();
    const attributes = new Map();
    const events = target => ({
        addEvents(listeners) {Object.entries(listeners).forEach(([name, listener]) => target.set(name, listener));},
        removeEvent(name, listener) {if (target.get(name) === listener) target.delete(name);}
    });
    const Loader = {
        ...events(loaderEvents),
        getElm: () => Overlay,
        setAttribute: (name, value) => attributes.set(name, value),
        show: () => loaderEvents.get('show')?.(),
        hide: () => loaderEvents.get('hide')?.()
    };
    const Panel = {...events(panelEvents), Loader, getElm: () => Root, getContent: () => Content};
    let ProductPanelLoading;
    let observer;
    const filename = resolve(__dirname, '../../bin/utils/ProductPanelLoading.js');
    vm.runInNewContext(readFileSync(filename, 'utf8'), {
        document, queueMicrotask,
        ResizeObserver: class {
            constructor(callback) {this.callback = callback; this.observed = []; observer = this;}
            observe(element) {this.observed.push(element);}
            disconnect() {this.disconnected = true;}
        },
        define(name, dependencies, factory) {
            ProductPanelLoading = factory({get: (group, key) => key});
        }
    }, {filename});
    const loading = new ProductPanelLoading(Panel);
    return {loading, Loader, Root, Header, Categories, Content, Overlay, Save, document,
        attributes, panelEvents, loaderEvents, observer};
}

test('loader stays transparent and masks only the form rectangle', () => {
    const h = createFixture();
    h.Loader.show();
    assert.equal(h.Overlay.style.background, 'transparent');
    assert.equal(h.attributes.get('styles').background, 'transparent');
    assert.equal(h.loading.Cover.style.left, '200px');
    assert.equal(h.loading.Cover.style.top, '100px');
    assert.equal(h.loading.Cover.style.width, '1000px');
    assert.equal(h.loading.Cover.style.height, '800px');
    assert.equal(h.loading.Message.getAttribute('role'), 'status');
    assert.equal(h.loading.Message.textContent, 'products.product.loading');
});

test('saving disables interaction without clearing the panel and restores previous inert state', () => {
    const h = createFixture();
    h.Categories.inert = true;
    const originalChildren = [...h.Root.children];
    h.loading.setSaving();
    h.Loader.show();
    h.Loader.show();
    assert.equal(h.Root.getAttribute('aria-busy'), 'true');
    assert.deepEqual(h.Root.children, originalChildren);
    assert.equal(h.Header.inert, true);
    assert.equal(h.Content.inert, true);
    assert.equal(h.Overlay.inert, false);
    assert.equal(h.loading.Message.textContent, 'products.product.saving');
    h.document.activeElement = h.document.body;
    h.Loader.hide();
    assert.equal(h.Root.getAttribute('aria-busy'), 'false');
    assert.equal(h.Header.inert, false);
    assert.equal(h.Content.inert, false);
    assert.equal(h.Categories.inert, true);
    assert.equal(h.document.activeElement, h.Save);
    h.Loader.show();
    assert.equal(h.loading.Message.textContent, 'products.product.loading');
});

test('cover follows resizing and sidebar width changes', () => {
    const h = createFixture();
    h.Loader.show();
    h.Content.bounds = {left: 80, top: 150, width: 1140, height: 780};
    h.observer.callback();
    assert.equal(h.loading.Cover.style.left, '60px');
    assert.equal(h.loading.Cover.style.top, '120px');
    assert.equal(h.loading.Cover.style.width, '1140px');
    assert.equal(h.loading.Cover.style.height, '780px');
    assert.deepEqual(h.observer.observed, [h.Root, h.Content]);
});

test('timeout recovery recreates the status card and destruction removes listeners', async () => {
    const h = createFixture();
    h.Loader.show();
    const Cover = h.loading.Cover;
    h.Overlay.children = [];
    Cover.parentElement = null;
    await Promise.resolve();
    assert.notEqual(h.loading.Cover, Cover);
    assert.equal(h.Overlay.children.length, 1);
    h.panelEvents.get('destroy')();
    await Promise.resolve();
    assert.equal(h.Content.inert, false);
    assert.equal(h.observer.disconnected, true);
    assert.equal(h.loaderEvents.size, 0);
    assert.equal(h.panelEvents.size, 0);
});
