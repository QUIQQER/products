# Backend product panel extensions

This integration requires the backend extension API in `quiqqer/productsearch`
2.7 or later. The dependency constraint targets that next minor release; no
release is created by these changes. During development, use both updated
`next-2.x` checkouts together.

The product panel exposes its embedded `quiqqer/productsearch` control through
`Panel.getSearch()`. It returns `null` before injection. Extensions should use the
following global JavaScript events instead of accessing `$Search` or other private
properties:

| Event | Arguments | Timing |
| --- | --- | --- |
| `quiqqerProductsPanelCreate` | `Panel` | After the standard toolbar has been built. Add package-specific toolbar actions here. |
| `quiqqerProductsPanelSearchCreate` | `Panel, searchOptions` | Before constructing the search. Mutate `searchOptions` to supply initial extension parameters or existing Grid options. |
| `quiqqerProductsPanelSearchReady` | `Panel, Search` | Once the form and grid are ready, before the initial search. |

Events are synchronous. Register listeners in the extension package's backend
JavaScript entrypoint. Preserve existing options and event handlers when modifying
`searchOptions`. The panel supplies the context `products.panel`; other search
consumers retain their own context.

```js
QUI.addEvent('quiqqerProductsPanelSearchCreate', (Panel, options) => {
    options.searchParams = {
        ...options.searchParams,
        extensions: {
            ...options.searchParams?.extensions,
            'vendor/package': {enabled: true}
        }
    };
});

QUI.addEvent('quiqqerProductsPanelSearchReady', (Panel, Search) => {
    const Grid = Search.getResult().getGrid();
    // Grid is the existing Core grid, including its column configuration API.
});
```

The search facade provides `whenReady()`, `getForm()`, `getResult()`,
`getSearchParams()`, `setSearchParams(params)`, and `search()`. Parameter changes
restart pagination and take effect on the next search. They do not execute a
request automatically. The getters return copies of the extension parameters.

The product panel uses the opt-in `layout: 'panel'` search layout. Its top toolbar
contains the categories toggle, text search, and filter dropdown. Product creation
and selection actions live in the existing grid toolbar through
`searchOptions.gridOptions.buttons`; preserve these buttons when extending that
array. The status action deactivates an all-active selection and activates an
inactive or mixed selection.

The panel keeps its toolbar and content hidden, with its own loader visible,
until the form, grid, first result, and initial sizing are ready. The
`quiqqerProductsPanelSearchReady` event still runs before the first request, so
extensions can contribute initial parameters.

Existing product fields can already be shown through the product search settings.
Use those settings for ordinary field columns. Calculated columns, additional
filters, and server-side query extensions are described in
`quiqqer/productsearch/docs/backend-search-extensions.md`.

To run the panel API tests:

```sh
node --test tests/javascript/*.test.cjs
```
