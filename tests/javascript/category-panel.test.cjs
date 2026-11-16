const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function fixture() {
    let definition;
    const toolbar = [];
    const calls = [];
    const requests = [];
    const element = () => ({
        style: {removeProperty() {}},
        attributes: {},
        children: [],
        classList: {add() {}},
        firstElementChild: {setAttribute() {}},
        setAttribute(name, value) { this.attributes[name] = value; },
        getAttribute(name) { return this.attributes[name]; },
        appendChild(child) { this.children.push(child); },
        getSize: () => ({x: 1000, y: 600})
    });

    class Button {
        constructor(options) { this.options = options; this.element = element(); }
        getAttribute(name) { return this.options[name]; }
        getElm() { return this.element; }
        enable() { this.options.disabled = false; }
        disable() { this.options.disabled = true; }
    }

    class Grid {
        constructor(container, options) {
            this.options = options;
            this.buttons = options.buttons.map(options => new Button(options));
            this.selected = [];
        }
        getButton(name) { return this.buttons.find(Button => Button.getAttribute('name') === name); }
        addEvents(events) { this.events = events; }
        getSelectedData() { return this.selected; }
        setData(data) { this.data = data; this.selected = []; }
        setWidth(width) { this.width = width; return Promise.resolve(); }
        setHeight(height) { this.height = height; return Promise.resolve(); }
        resize() {}
    }

    class Menu {
        inject() { return this; }
        appendChild() { return this; }
    }

    const Categories = {
        getList(params) {
            requests.push(params);
            return Promise.resolve({data: [{id: 12}], total: 1});
        }
    };
    const dependencies = {
        'qui/controls/buttons/Button': Button,
        'controls/grid/Grid': Grid,
        'qui/controls/contextmenu/Menu': Menu,
        'qui/controls/contextmenu/Item': function(options) { this.options = options; },
        'package/quiqqer/products/bin/classes/Categories': function() { return Categories; },
        'package/quiqqer/products/bin/controls/categories/Sitemap': class {
            inject() { return this; }
            getSelected() { return []; }
        },
        Locale: {get: (group, key) => key}
    };
    vm.runInNewContext(readFileSync(resolve(__dirname, '../../bin/controls/categories/Panel.js'), 'utf8'), {
        Class: function(options) { return options; },
        document: {createElement: element, body: element()},
        moofx: () => ({animate(styles, options) { options.callback?.(); }}),
        define(name, names, factory) {
            definition = factory(...names.map(name => dependencies[name] ?? {}));
        }
    });

    const root = element();
    const content = element();
    const panel = Object.assign({}, definition, {
        parent() {},
        Loader: {show() {}, hide() {}},
        getId: () => 'test',
        getElm: () => root,
        getContent: () => content,
        addButton: Button => toolbar.push(Button),
        getButtons: name => toolbar.find(Button => Button.getAttribute('name') === name),
        createChild: () => calls.push(['create']),
        updateChild: id => calls.push(['edit', id]),
        deleteChild: id => calls.push(['delete', id])
    });
    panel.$onCreate();
    return {panel, toolbar, calls, requests};
}

test('only the categories toggle remains in the toolbar; named actions belong to the grid', () => {
    const {panel, toolbar} = fixture();
    assert.deepEqual(toolbar.map(Button => Button.getAttribute('name')), ['sitemap']);
    const grid = panel.$Grid;
    assert.deepEqual(Array.from(grid.buttons, Button => Button.getAttribute('name')), ['add', 'edit', 'delete']);
    assert.equal(grid.getButton('add').getAttribute('text'), 'categories.panel.button.add');
    assert.equal(grid.getButton('edit').getAttribute('text'), 'categories.panel.button.edit');
    const Delete = grid.getButton('delete');
    assert.equal(Delete.getAttribute('position'), 'right');
    assert.equal(Delete.getAttribute('title'), 'categories.panel.button.delete');
    assert.equal(Delete.getElm().attributes['aria-label'], 'categories.panel.button.delete');
});

test('selection enables edit and delete and clearing it disables them without changing their labels', () => {
    const {panel, calls} = fixture();
    const grid = panel.$Grid;
    for (const selected of [[], [{id: 12}], [], [{id: 12}, {id: 13}]]) {
        grid.selected = selected;
        grid.events.onClick();
        assert.equal(grid.getButton('edit').getAttribute('disabled'), selected.length !== 1);
        assert.equal(grid.getButton('delete').getAttribute('disabled'), selected.length !== 1);
        assert.equal(grid.getButton('delete').getAttribute('text'), 'categories.panel.button.delete');
    }
    grid.selected = [{id: 12}];
    grid.getButton('add').options.events.onClick();
    grid.getButton('edit').options.events.onClick();
    grid.getButton('delete').options.events.onClick();
    assert.deepEqual(calls, [['create'], ['edit', 12], ['delete', 12]]);
});

test('refresh clears selection actions through the grid and preserves the selected parent filter', async () => {
    const {panel, requests} = fixture();
    panel.$Sitemap.getSelected = () => [{getAttribute: () => 7}];
    panel.$Grid.selected = [{id: 12}];
    panel.$Grid.events.onClick();
    await panel.refresh();
    assert.equal(requests[0].where.parentId, 7);
    assert.equal(panel.$Grid.getButton('edit').getAttribute('disabled'), true);
    assert.equal(panel.$Grid.getButton('delete').getAttribute('disabled'), true);
});

test('the categories toggle returns a settling promise and exposes the sitemap visibility', async () => {
    const {panel, toolbar} = fixture();
    const button = toolbar[0].getElm();
    assert.equal(button.attributes['aria-expanded'], 'false');
    assert.equal(button.attributes['aria-controls'], panel.$SitemapContainer.id);
    assert.equal(panel.$SitemapContainer.inert, true);
    await panel.$onResize();
    assert.equal(panel.$Grid.width, 980);
    assert.equal(panel.$Grid.height, 580);
    await panel.toggleSitemap();
    assert.equal(button.attributes['aria-expanded'], 'true');
    assert.equal(panel.$SitemapContainer.inert, false);
    assert.equal(panel.$Grid.width, 680);
    await panel.$onResize();
    assert.equal(panel.$Grid.width, 680);
    assert.equal(panel.$Grid.height, 580);
    await panel.toggleSitemap();
    assert.equal(button.attributes['aria-expanded'], 'false');
    assert.equal(panel.$SitemapContainer.inert, true);
    assert.equal(panel.$Grid.width, 980);
});
