const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function createPanel({wantedCategory = 'variants', productId = 21, parentId = productId} = {}) {
    const errors = [];
    const buttons = new Map();
    const categories = new Map();
    const grids = [];
    let selectedVariant = null;
    let loaderVisible = false;

    class Button {
        constructor(options) {
            this.options = options;
            this.visible = true;
            this.active = false;
            this.listeners = [];
        }
        show() { this.visible = true; }
        hide() { this.visible = false; }
        inject() { return this; }
        getAttribute(name) { return this.options[name]; }
        isActive() { return this.active; }
        setActive() { this.active = true; }
        addEvent(name, handler) { this.listeners.push(handler); }
        click() {
            this.options.events?.onClick?.();
            this.listeners.forEach(handler => handler(this));
        }
    }
    class Element {
        inject() { return this; }
        set() {}
        getSize() { return {x: 900, y: 600}; }
        getElement() { return this; }
    }
    class Grid {
        constructor() { grids.push(this); }
        addEvents() {}
        setHeight() {}
    }
    class Product {
        constructor(options) { this.id = options.id; }
        getId() { return this.id; }
        getVariantFields() { return Promise.resolve([]); }
    }
    const modules = {
        Locale: {get: (group, key) => key},
        Users: {getUserBySession: () => ({
            isLoaded: () => true,
            getAttribute: () => wantedCategory
        })},
        'utils/Lock': {
            isLocked: () => Promise.resolve(false),
            lock: () => Promise.resolve()
        },
        Ajax: {get: (name, callback) => callback(parentId)},
        'qui/controls/buttons/Button': Button,
        'controls/grid/Grid': Grid,
        'package/quiqqer/products/bin/classes/Product': Product
    };
    const load = name => {
        let definition;
        const filename = resolve(__dirname, `../../bin/controls/products/${name}.js`);
        vm.runInNewContext(readFileSync(filename, 'utf8'), {
            Class: function (value) { return value; },
            console: {error: error => errors.push(error)},
            Element,
            Promise,
            typeOf: value => value instanceof Button ? 'qui/controls/buttons/Button' : typeof value,
            define(name, dependencies, factory) {
                definition = factory(...dependencies.map(dependency => modules[dependency]));
            }
        }, {filename});
        return definition;
    };
    const base = load('Product');
    modules['package/quiqqer/products/bin/controls/products/Product'] = base;
    const variant = load('ProductVariant');
    const body = new Element();
    const attributes = {productId};

    for (const name of ['update', 'status', 'copy', 'actionSeparator']) {
        buttons.set(name, new Button({name}));
    }
    const panel = Object.assign(Object.create(variant), {
        $VariantFields: null,
        $BackToVariantList: null,
        $CurrentVariant: null,
        $CloseButton: new Button({}),
        $Product: new Product({id: productId}),
        $Elm: {addClass() {}},
        $loaded: false,
        Loader: {
            show() { loaderVisible = true; },
            hide() { loaderVisible = false; }
        },
        $loaderHide: base.$loaderHide,
        getBody: () => body,
        getHeader: () => ({}),
        getElm: () => ({removeClass() {}}),
        getButtons: name => buttons.get(name) ?? null,
        addButton(options) { buttons.set(options.name, new Button(options)); },
        getAttribute: name => attributes[name],
        setAttribute(name, value) { attributes[name] = value; },
        addCategory(options) { categories.set(options.name, new Button(options)); },
        getCategory: name => categories.get(name),
        getCategoryBar: () => ({
            moveChildToPos() {},
            getChildren: () => [...categories.values()]
        }),
        maximizeCategory() {},
        refresh: () => Promise.resolve(),
        $hideCategories: () => Promise.resolve(),
        refreshProductEditableFields: () => Promise.resolve(),
        refreshVariantGrid: () => Promise.resolve(),
        $createExtraMenu() {},
        $showCategory: () => Promise.resolve(),
        selectVariant(id) {
            selectedVariant = id;
            this.$BackToVariantList.show();
            return Promise.resolve();
        },
        $render() {
            // Mimic MooTools' parent dispatch for category creation, keeping the real category handlers.
            const parent = this.parent;
            this.parent = () => this.addCategory({name: 'information'});
            try {
                variant.$createCategories.call(this, {}, [], {});
            } finally {
                this.parent = parent;
            }
            return Promise.resolve();
        }
    });
    panel.parent = () => base.$onInject.call(panel);

    return {
        panel, errors, grids,
        getSelectedVariant: () => selectedVariant,
        isLoaderVisible: () => loaderVisible,
        async inject() {
            await panel.$onInject();
            await new Promise(resolve => setImmediate(resolve));
        }
    };
}

test('restoring the variants category on opening a parent creates the grid without a null-button error', async () => {
    const fixture = createPanel();
    await fixture.inject();

    assert.deepEqual(fixture.errors, []);
    assert.equal(fixture.grids.length, 1);
    assert.equal(fixture.panel.getCategory('variants').isActive(), true);
    assert.equal(fixture.panel.$VariantFields.visible, true);
    assert.equal(fixture.panel.$BackToVariantList.visible, false);
    assert.equal(fixture.panel.$SaveButton.visible, false);
    assert.equal(fixture.isLoaderVisible(), false);
});

test('opening the information category keeps the variant actions hidden until the user switches tabs', async () => {
    const fixture = createPanel({wantedCategory: 'information'});
    await fixture.inject();

    assert.deepEqual(fixture.errors, []);
    assert.equal(fixture.grids.length, 0);
    assert.equal(fixture.panel.$VariantFields.visible, false);
    assert.equal(fixture.panel.$BackToVariantList.visible, false);
    assert.equal(fixture.panel.$SaveButton.visible, true);

    fixture.panel.getCategory('variants').click();
    await new Promise(resolve => setImmediate(resolve));

    assert.deepEqual(fixture.errors, []);
    assert.equal(fixture.grids.length, 1);
    assert.equal(fixture.panel.$VariantFields.visible, true);
});

test('opening a child with the variants category remembered still selects the requested child', async () => {
    const fixture = createPanel({productId: 22, parentId: 21});
    await fixture.inject();

    assert.deepEqual(fixture.errors, []);
    assert.equal(fixture.panel.getAttribute('productId'), 21);
    assert.equal(fixture.panel.$Product.getId(), 21);
    assert.equal(fixture.getSelectedVariant(), 22);
    assert.equal(fixture.panel.$BackToVariantList.visible, true);
    assert.equal(fixture.isLoaderVisible(), false);
});
