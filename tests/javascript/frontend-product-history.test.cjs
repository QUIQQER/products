const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function loadList({count = 9, navigation = 'navigate', state = {otherControl: 'preserved'}} = {}) {
    let definition;
    const listeners = new Map();
    const writes = [];
    const renders = [];
    const events = [];
    const requests = [];
    const window = {
        location: new URL('https://shop.example/category'),
        scrollX: 0,
        scrollY: 450,
        performance: {getEntriesByType: () => [{type: navigation}]},
        requestAnimationFrame: callback => setImmediate(callback),
        addEventListener: (name, handler) => listeners.set(name, handler),
        removeEventListener: name => listeners.delete(name),
        addEvent() {},
        fireEvent() {},
        scrollTo({left, top}) { this.scrollX = left; this.scrollY = top; },
        history: {
            state,
            replaceState(state, title, url) { this.state = state; writes.push(['replace', url]); },
            pushState(state, title, url) { this.state = state; writes.push(['push', url]); }
        }
    };
    const fields = [{
        value: '',
        getFieldId: () => 1,
        getSearchValue() { return this.value; },
        isReady: () => true,
        setSearchValue(value) { this.value = value; },
        reset() { this.value = ''; }
    }];
    const context = vm.createContext({
        window,
        location: window.location,
        console,
        Promise,
        setTimeout,
        clearTimeout,
        Element: {NativeEvents: {}},
        typeOf: value => Array.isArray(value) ? 'array' : typeof value,
        Class: function (value) { return value; },
        define(name, dependencies, factory) {
            definition = factory(...dependencies.map(dependency => ({
                'qui/QUI': {Controls: {getById: () => fields[0], getControlsInElement: () => []}},
                Locale: {get: (group, key, params) => params.count},
                URI: url => ({
                    search: () => Object.fromEntries(new URL(url).searchParams),
                    query: () => Object.fromEntries(new URL(url).searchParams)
                }),
                Ajax: {get: (name, callback, params) => {requests.push(params); callback(false);}}
            })[dependency]));
        }
    });
    vm.runInContext(`
        Object.getLength = value => Object.keys(value).length;
        Object.filter = (value, filter) => Object.fromEntries(Object.entries(value).filter(([key, item]) => filter(item, key)));
        Object.toQueryString = value => new URLSearchParams(value).toString();
        Array.clone = value => Array.from(value);
        Array.prototype.combine = function (other) { for (const item of other) if (!this.includes(item)) this.push(item); return this; };
        Array.prototype.each = Array.prototype.forEach;
        String.prototype.contains = String.prototype.includes;
        JSON.decode = JSON.parse;
        JSON.encode = JSON.stringify;
        Function.prototype.delay = function (duration, bind) { return setTimeout(() => this.call(bind), duration); };
    `, Object.assign(context, {URLSearchParams}));
    const filename = resolve(__dirname, '../../bin/controls/frontend/category/ProductList.js');
    vm.runInContext(readFileSync(filename, 'utf8'), context, {filename});
    const attributes = {project: 'shop', lang: 'de', siteId: 5, categoryId: 7, view: 'gallery', productLoadNumber: 9};
    const list = Object.assign(Object.create(definition), {
        $load: true,
        $readLocationRunning: false,
        $productId: false,
        $historyVersion: 0,
        $restoringHistory: false,
        $moreButtonClicked: 3,
        $initialHistoryView: 'gallery',
        $initialHistorySort: '',
        $initialHistoryTags: [],
        $selectFields: fields,
        $selectFilter: [],
        $FilterContainer: {getElements: () => [{get: () => 'price'}]},
        $FreeText: {value: ''},
        $tags: [],
        $categories: [],
        $ContainerReal: {
            children: Array.from({length: count}, (_, i) => ({dataset: {pid: String(i + 1)}})),
            getElements: () => Array(count)
        },
        $FilterSort: {setStyle() {}},
        getAttribute: name => attributes[name],
        setAttribute: (name, value) => attributes[name] = value,
        addEvents: value => events.push(value),
        showList: () => Promise.resolve(),
        $refreshSearchCount: () => Promise.resolve(),
        $renderFilterFields() {},
        $renderSearch: async (...args) => {
            renders.push(args);
            list.$ContainerReal.children = Array.from({length: args[1] || 9}, () => ({dataset: {pid: '1'}}));
            list.$listStartIndex = args[2] || 0;
            list.$renderedListView = attributes.view;
            list.$renderedListUrl = window.location.href;
        },
        openProduct: async id => {list.$productId = id; list.$setWindowLocation();},
        $getSearchParams: () => ({})
    });
    const snapshot = (productCount, scrollY = 1400, url = window.location.href, startIndex = 0) => ({
        otherControl: 'preserved',
        quiqqerProductLists: {[list.$historyKey]: {
            url, productCount, startIndex, view: 'gallery', scrollY, scrollX: 0, moreButtonClicked: 4
        }}
    });
    return {list, definition, window, listeners, writes, renders, events, fields, attributes, requests, snapshot};
}

test('a fresh visit records its own list entry without pushing a new history entry', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    const state = fixture.window.history.state;
    assert.equal(state.otherControl, 'preserved');
    assert.equal(state.quiqqerProductLists[fixture.list.$historyKey].productCount, 9);
    assert.equal(state.quiqqerProductLists[fixture.list.$historyKey].scrollY, 450);
    assert.deepEqual(fixture.renders, []);
    assert.ok(fixture.writes.every(([kind]) => kind === 'replace'));
    fixture.events[0].onDestroy();
    assert.equal(fixture.listeners.size, 0);
});

test('history restores all loaded products, scrolling and autoload progress without changing batch size', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    fixture.window.history.state = fixture.snapshot(27);
    await fixture.list.$restoreHistory(fixture.window.history.state);
    assert.deepEqual(fixture.renders, [[undefined, 27, 0]]);
    assert.equal(fixture.window.scrollY, 1400);
    assert.equal(fixture.list.$moreButtonClicked, 4);
    assert.equal(fixture.attributes.productLoadNumber, 9);
    assert.equal(fixture.list.$restoringHistory, false);
    assert.ok(fixture.writes.every(([kind]) => kind === 'replace'));
});

test('history retains the starting offset of a server-rendered later page', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    await fixture.list.$restoreHistory(fixture.snapshot(18, 1700, fixture.window.location.href, 18));
    assert.deepEqual(fixture.renders, [[undefined, 18, 18]]);
    assert.equal(fixture.window.history.state.quiqqerProductLists[fixture.list.$historyKey].startIndex, 18);
});

test('back and forward between a list and a product preserve the list entry and browser stack', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    const listState = fixture.snapshot(27);
    fixture.window.location = new URL('https://shop.example/category?p=55');
    await fixture.list.$restoreHistory(null);
    assert.equal(fixture.list.$productId, 55);
    assert.deepEqual(fixture.renders, []);
    fixture.window.location = new URL('https://shop.example/category');
    await fixture.list.$restoreHistory(listState);
    fixture.window.location = new URL('https://shop.example/category?p=55');
    await fixture.list.$restoreHistory(null);
    fixture.window.location = new URL('https://shop.example/category');
    await fixture.list.$restoreHistory(listState);
    assert.deepEqual(fixture.renders, [[undefined, 27, 0]]);
    assert.equal(fixture.window.scrollY, 1400);
    assert.ok(fixture.writes.every(([kind]) => kind === 'replace'));
});

test('rapid history changes serialize restoration and discard an obsolete transition', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    let finishRead;
    fixture.list.$readWindowLocation = () => new Promise(resolve => finishRead = resolve);
    const first = fixture.list.$restoreHistory(fixture.snapshot(18));
    await new Promise(resolve => setImmediate(resolve));
    const second = fixture.list.$restoreHistory(fixture.snapshot(36, 2200));
    finishRead();
    await first;
    await new Promise(resolve => setImmediate(resolve));
    finishRead();
    await second;
    assert.deepEqual(fixture.renders, [[undefined, 36, 0]]);
    assert.equal(fixture.window.scrollY, 2200);
});

test('returning to an entry without filters clears later text, price and sorting values', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    fixture.list.$FreeText.value = 'new search';
    fixture.fields[0].value = {from: 100, to: 200};
    fixture.attributes.sort = 'title DESC';
    await fixture.list.$restoreHistory(fixture.snapshot(9));
    assert.equal(fixture.list.$FreeText.value, '');
    assert.equal(fixture.fields[0].value, '');
    assert.equal(fixture.attributes.sort, '');
    fixture.window.location = new URL('https://shop.example/category?f=%7B%221%22%3A%7B%22from%22%3A20%7D%7D&search=old');
    await fixture.list.$restoreHistory(fixture.snapshot(18));
    assert.equal(fixture.list.$FreeText.value, 'old');
    assert.deepEqual({...fixture.fields[0].value}, {from: 20});
});

test('an unrelated snapshot is ignored and failed restoration releases the guard for retrying', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    await fixture.list.$restoreHistory(fixture.snapshot(500, 9000, 'https://shop.example/other'));
    assert.deepEqual(fixture.renders, [[undefined, undefined, 0]]);
    assert.equal(fixture.window.scrollY, 0);
    fixture.list.$renderSearch = () => Promise.reject(new Error('offline'));
    await assert.rejects(fixture.list.$restoreHistory(fixture.snapshot(27)), /offline/);
    assert.equal(fixture.list.$restoringHistory, false);
});

test('render requests restore the saved limit and offset and continue after all restored products', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    await fixture.definition.$renderSearch.call(fixture.list, undefined, 27, 18);
    assert.equal(fixture.requests[0].productLoadNumber, 27);
    assert.equal(JSON.parse(fixture.requests[0].searchParams).limitOffset, 18);
    fixture.list.$listStartIndex = 18;
    await fixture.definition.$renderSearch.call(fixture.list, true);
    assert.equal(fixture.requests[1].productLoadNumber, 9);
    assert.equal(JSON.parse(fixture.requests[1].searchParams).limitOffset, 27);
});

test('field searches preserve configured defaults so cleared filters stay cleared on Back', () => {
    const fixture = loadList();
    fixture.attributes.searchfields = {2: 'configured default'};
    fixture.fields[0].value = {from: 100};
    const first = fixture.definition.$getSearchParams.call(fixture.list);
    assert.equal(first.fields[1].from, 100);
    assert.deepEqual(fixture.attributes.searchfields, {2: 'configured default'});
    fixture.list.$resetHistoryFilters();
    const restored = fixture.definition.$getSearchParams.call(fixture.list);
    assert.deepEqual({...restored.fields}, {2: 'configured default'});
});

test('a history visit restores its snapshot on import, while normal reloads use the fresh list', async () => {
    const state = {quiqqerProductLists: {
        [JSON.stringify(['shop', 'de', 5, 7])]: {
            url: 'https://shop.example/category', productCount: 36, scrollY: 2100, startIndex: 0
        }
    }};
    const history = loadList({navigation: 'back_forward', state});
    await history.list.$installHistory();
    assert.deepEqual(history.renders, [[undefined, 36, 0]]);
    assert.equal(history.window.scrollY, 2100);

    const fresh = loadList({navigation: 'reload', state});
    await fresh.list.$installHistory();
    assert.deepEqual(fresh.renders, []);
});

test('failed history requests preserve their snapshot, and a saved gallery view beats a later session default', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    const saved = fixture.snapshot(27);
    saved.quiqqerProductLists[fixture.list.$historyKey].view = 'gallery';
    fixture.list.$initialHistoryView = 'list';
    fixture.window.history.state = saved;
    fixture.list.$renderSearch = () => Promise.reject(new Error('offline'));
    await assert.rejects(fixture.list.$restoreHistory(saved), /offline/);
    assert.equal(fixture.attributes.view, 'gallery');
    assert.equal(fixture.window.history.state, saved);
});

test('a pending filter count cannot start a fresh first-page render after history navigation', async () => {
    const fixture = loadList();
    let finishCount;
    Object.assign(fixture.list, {
        $FilterResultInfo: {set() {}},
        fireEvent() {},
        $hideContainerWithLoader() {},
        refreshClearFilterButtonStatus() {},
        $refreshSearchCount: () => new Promise(resolve => finishCount = resolve)
    });
    fixture.list.$onFilterChange();
    await new Promise(resolve => setTimeout(resolve, 220));
    assert.equal(typeof finishCount, 'function');
    fixture.list.$historyVersion++;
    fixture.list.$restoringHistory = true;
    finishCount();
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(fixture.renders, []);
});

test('history refreshes its count despite an older request and ignores that older response', async () => {
    const fixture = loadList();
    const pending = [];
    const counts = [];
    fixture.list.search = () => new Promise(resolve => pending.push(resolve));
    fixture.list.$FilterResultInfo = {set: (name, value) => counts.push(value)};
    const first = fixture.definition.$refreshSearchCount.call(fixture.list);
    await new Promise(resolve => setTimeout(resolve, 120));
    fixture.list.$historyVersion++;
    fixture.list.$restoringHistory = true;
    const second = fixture.definition.$refreshSearchCount.call(fixture.list);
    await new Promise(resolve => setTimeout(resolve, 120));
    assert.equal(pending.length, 2);
    pending[1](18);
    await second;
    pending[0](99);
    await first;
    assert.deepEqual(counts, [18]);
});

test('switching view preserves the outgoing rendered view until the new view has loaded', async () => {
    const fixture = loadList();
    await fixture.list.$installHistory();
    fixture.attributes.view = 'list';
    fixture.list.$saveHistoryState();
    const saved = fixture.window.history.state.quiqqerProductLists[fixture.list.$historyKey];
    assert.equal(saved.view, 'gallery');
});

test('returning from a product reuses all loaded rows and controls without any search requests', async () => {
    const fixture = loadList({count: 54});
    await fixture.list.$installHistory();
    const state = fixture.window.history.state;
    const children = fixture.list.$ContainerReal.children;
    let counts = 0;
    fixture.list.$refreshSearchCount = async () => counts++;
    fixture.window.location = new URL('https://shop.example/category?p=55');
    await fixture.list.$restoreHistory(null);
    fixture.window.location = new URL('https://shop.example/category');
    await fixture.list.$restoreHistory(state);
    assert.equal(fixture.list.$ContainerReal.children, children);
    assert.deepEqual(fixture.renders, []);
    assert.equal(counts, 0);
    assert.equal(fixture.window.scrollY, 450);
});

test('equally sized lists with different filters or views are fetched instead of reused', async () => {
    for (const change of ['url', 'view', 'offset']) {
        const fixture = loadList({count: 27});
        await fixture.list.$installHistory();
        const saved = fixture.snapshot(27);
        if (change === 'url') {
            fixture.list.$renderedListUrl = 'https://shop.example/category?search=other';
        } else if (change === 'view') {
            fixture.list.$renderedListView = 'list';
        } else {
            fixture.list.$listStartIndex = 27;
        }
        let counts = 0;
        fixture.list.$refreshSearchCount = async () => counts++;
        await fixture.list.$restoreHistory(saved);
        assert.deepEqual(fixture.renders, [[undefined, 27, 0]], change);
        assert.equal(counts, 1, change);
    }
});
