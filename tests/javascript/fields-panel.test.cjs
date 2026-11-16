const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function element() {
    return {
        style: {removeProperty() {}},
        classList: {add() {}},
        attributes: {},
        children: [],
        firstElementChild: {setAttribute() {}},
        setAttribute(name, value) { this.attributes[name] = value; },
        appendChild(child) { this.children.push(child); },
        inject() { return this; },
        getBoundingClientRect: () => ({right: 760, bottom: 90, width: 200})
    };
}

function load(path, dependencies) {
    let definition;
    vm.runInNewContext(readFileSync(resolve(__dirname, '../../bin/controls/fields', path), 'utf8'), {
        Class: function (value) { return value; },
        Element: function () { return element(); },
        document: {createElement: element},
        window: {innerWidth: 800, scrollX: 0, scrollY: 0},
        define(name, names, factory) {
            definition = factory(...names.map(name => dependencies[name] ?? {}));
        }
    });
    return definition;
}

function fixture() {
    const toolbar = [];
    const calls = [];
    const root = element();
    const menu = {
        getElm: element,
        addEvents(events) { this.events = events; },
        setPosition(left, top) { this.position = {left, top}; }
    };
    class Button {
        constructor(options) { this.options = options; this.element = element(); }
        getAttribute(name) { return this.options[name]; }
        getElm() { return this.element; }
        getContextMenu(callback) { callback(menu); }
        enable() { this.options.disabled = false; }
        disable() { this.options.disabled = true; }
    }
    class Search {
        constructor(options) {
            this.options = options;
            this.selected = [];
            this.grid = {
                options: {page: 1},
                getButton: name => options.buttons.find(Button => Button.getAttribute('name') === name)
            };
        }
        inject() { return this; }
        getSelected() { return this.selected; }
        getGrid() { return this.grid; }
        setAttribute(name, value) { this.options[name] = value; }
        refresh() {
            this.options.events.onRefreshBegin();
            return Promise.resolve().then(() => this.options.events.onRefresh());
        }
    }
    const definition = load('Panel.js', {
        'qui/controls/buttons/Button': Button,
        'package/quiqqer/products/bin/controls/fields/FieldTypeSelect': Button,
        'package/quiqqer/products/bin/controls/fields/search/Search': Search,
        'package/quiqqer/products/bin/classes/Fields': function () {},
        Locale: {get: (group, key) => key}
    });
    const panel = Object.assign({}, definition, {
        getElm: () => root,
        getContent: element,
        addButton: Button => toolbar.push(Button),
        Loader: {show() {}, hide() {}},
        createChild: () => calls.push(['create']),
        updateChild: id => calls.push(['edit', id]),
        deleteChild: ids => calls.push(['delete', ...ids])
    });
    panel.$onCreate();
    return {panel, toolbar, calls, menu};
}

test('named field actions belong to the grid and only the filter stays in the toolbar', () => {
    const {panel, toolbar} = fixture();
    assert.deepEqual(toolbar.map(Button => Button.getAttribute('name')), ['filter']);
    const Search = panel.$Search;
    const Grid = Search.getGrid();
    assert.equal(Search.options.showFieldTypeFilter, false);
    assert.deepEqual(Array.from(Search.options.buttons, Button => Button.getAttribute('name')), ['add', 'edit', 'delete']);
    assert.equal(Grid.getButton('add').getAttribute('text'), 'fields.panel.button.create');
    assert.equal(Grid.getButton('edit').getAttribute('text'), 'fields.panel.button.edit');
    const Delete = Grid.getButton('delete');
    assert.equal(Delete.getAttribute('position'), 'right');
    assert.equal(Delete.getElm().attributes['aria-label'], 'fields.panel.button.delete');
    assert.equal(panel.$Filter.getAttribute('text'), 'quiqqer.products.control.search.title');
    assert.equal(panel.$Filter.getElm().children[0].attributes['aria-hidden'], 'true');
});

test('edit needs one selection, delete supports several and empty selection cannot execute actions', () => {
    const {panel, calls} = fixture();
    const Search = panel.$Search;
    const Grid = Search.getGrid();
    for (const selected of [[], [12], [12, 13], []]) {
        Search.selected = selected;
        Search.options.events.onClick();
        assert.equal(Grid.getButton('edit').getAttribute('disabled'), selected.length !== 1);
        assert.equal(Grid.getButton('delete').getAttribute('disabled'), selected.length === 0);
        Grid.getButton('edit').options.events.onClick();
        Grid.getButton('delete').options.events.onClick();
        Search.options.events.onSubmit();
    }
    Grid.getButton('add').options.events.onClick();
    assert.deepEqual(calls, [['edit', 12], ['delete', 12], ['edit', 12], ['delete', 12, 13], ['create']]);
});

test('changing the field type resets pagination and clears selection actions on refresh', async () => {
    const {panel} = fixture();
    const Search = panel.$Search;
    const Grid = Search.getGrid();
    Search.selected = [12];
    Search.options.events.onClick();
    Grid.options.page = 4;
    panel.$Filter.options.events.onFilterChange(panel.$Filter, 'BoolType');
    assert.equal(Search.options.fieldTypeFilter, 'BoolType');
    assert.equal(Grid.options.page, 1);
    assert.equal(Grid.getButton('edit').getAttribute('disabled'), true);
    assert.equal(Grid.getButton('delete').getAttribute('disabled'), true);
    await panel.refresh();
    assert.equal(Grid.getButton('edit').getAttribute('disabled'), true);
});

test('the toolbar filter exposes its menu state and aligns the menu to the right', () => {
    const {panel, menu} = fixture();
    const Button = panel.$Filter.getElm();
    assert.equal(Button.attributes['aria-expanded'], 'false');
    menu.events.onShow();
    assert.equal(Button.attributes['aria-expanded'], 'true');
    assert.deepEqual(menu.position, {left: 560, top: 98});
    menu.events.onHide();
    assert.equal(Button.attributes['aria-expanded'], 'false');
});

test('the shared field search retains its filter by default and accepts grid actions for the panel', () => {
    class Grid {
        constructor(container, options) { this.options = options; }
        addEvents() {}
    }
    class Filter { constructor(options) { this.options = options; } }
    const definition = load('search/Search.js', {
        'controls/grid/Grid': Grid,
        'package/quiqqer/products/bin/controls/fields/FieldTypeSelect': Filter,
        Locale: {get: (group, key) => key}
    });
    for (const showFieldTypeFilter of [true, false]) {
        const custom = {name: 'create'};
        const options = {...definition.options, buttons: [custom]};
        if (!showFieldTypeFilter) options.showFieldTypeFilter = false;
        const Search = Object.assign({}, definition, {
            getAttribute: name => options[name],
            setAttribute(name, value) { options[name] = value; },
            refresh() { this.refreshed = true; }
        });
        Search.create();
        const Grid = Search.getGrid();
        assert.equal(Grid.options.buttons.length, showFieldTypeFilter ? 2 : 1);
        assert.equal(Grid.options.buttons.at(-1), custom);
        if (showFieldTypeFilter) {
            Grid.options.page = 4;
            Grid.options.buttons[0].options.events.onFilterChange(null, 'Input');
            assert.equal(Grid.options.page, 1);
            assert.equal(options.fieldTypeFilter, 'Input');
            assert.equal(Search.refreshed, true);
        }
    }
});

test('clearing the field type restores the panel label while the shared control keeps its default', () => {
    const definition = load('FieldTypeSelect.js', {Locale: {get: (group, key) => key}});
    for (const filterLabel of ['Filter', undefined]) {
        const options = {filterLabel};
        const Filter = {
            getAttribute: name => options[name],
            setAttribute(name, value) { options[name] = value; },
            fireEvent() {}
        };
        definition.$onChange.call(Filter, null, {getAttribute: () => 'BoolType'});
        assert.equal(options.text, 'fieldtype.BoolType');
        definition.$onChange.call(Filter, null, {getAttribute: () => ''});
        assert.equal(options.text, filterLabel ?? 'categories.window.fieldtype.filter');
    }
});

test('failed field loading exposes an error event so the panel can clear its loader', async () => {
    const definition = load('search/Search.js', {
        'package/quiqqer/products/bin/Fields': {getList: () => Promise.reject(new Error('offline'))}
    });
    const events = [];
    const Search = {
        $Grid: {options: {page: 1, perPage: 150}},
        getAttribute: () => false,
        fireEvent: name => events.push(name)
    };
    await assert.rejects(definition.refresh.call(Search), /offline/);
    assert.deepEqual(events, ['refreshBegin', 'refreshError']);
});
