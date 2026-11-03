const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function createPanel({wantedCategory = 'variants', productId = 21, parentId = productId, onGridCreate} = {}) {
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
        constructor(container, options) { this.options = options; grids.push(this); }
        setData(data) { this.data = data; }
        addEvents() {}
        setHeight() {}
    }
    class Product {
        constructor(options) { this.id = options.id; }
        getId() { return this.id; }
        getVariantFields() { return Promise.resolve([]); }
    }
    const modules = {
        'qui/QUI': {fireEvent(name, args) {
            assert.equal(name, 'quiqqerProductsVariantGridCreate');
            onGridCreate?.(...args);
        }},
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
        panel, errors, grids, variant,
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

test('variant extensions configure the existing grid before construction', async () => {
    let hook;
    const fixture = createPanel({onGridCreate(Control, options, metadata) {
        hook = {Control, metadata};
        options.columnModel.push({dataIndex: 'packageQuantity', dataType: 'number', sortable: false});
        options.storageKey = 'test-variant-grid';
    }});
    await fixture.inject();

    assert.equal(hook.Control, fixture.panel);
    assert.equal(hook.metadata.context, 'products.variants');
    assert.equal(hook.metadata.productId, 21);
    assert.equal(fixture.grids[0].options.columnModel.at(-1).dataIndex, 'packageQuantity');
    assert.equal(fixture.grids[0].options.storageKey, 'test-variant-grid');
});

test('refresh preserves extension values, zero fields and pagination', async () => {
    const fixture = createPanel();
    await fixture.inject();
    const grid = fixture.grids[0];
    fixture.panel.$Product.getVariants = () => Promise.resolve({
        data: [0, -1.25, 12.3456].map((quantity, index) => ({
            id: index + 1,
            active: 1,
            defaultVariant: index === 0,
            priority: 0,
            fields: [{id: 18, value: 0}],
            packageQuantity: quantity,
            packageFlag: false,
            packageMissing: null
        })),
        page: 2,
        total: 8
    });
    await fixture.variant.refreshVariantGrid.call(fixture.panel);

    assert.deepEqual(Array.from(grid.data.data, row => row.packageQuantity), [0, -1.25, 12.3456]);
    assert.equal(grid.data.data[0].priority, 0);
    assert.equal(grid.data.data[0].packageFlag, false);
    assert.equal(grid.data.data[0].packageMissing, null);
    assert.equal(grid.data.data[0].id, 1);
    assert.equal(grid.data.page, 2);
    assert.equal(grid.data.total, 8);

    fixture.panel.$Product.getVariants = () => Promise.resolve({data: [], page: 5, total: 8});
    await fixture.variant.refreshVariantGrid.call(fixture.panel);
    assert.equal(grid.data.data.length, 0);
    assert.equal(grid.data.page, 5);
    assert.equal(grid.data.total, 8);
});
