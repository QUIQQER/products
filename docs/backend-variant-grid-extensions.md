# Backend variant grid extensions

The product editor and ERP variant selection dialog expose the same extension
events. Register listeners in the extending package. No stock-management package
is required by either grid.

## JavaScript: `quiqqerProductsVariantGridCreate`

Arguments: `Control, gridOptions, metadata`.

Runs synchronously before the existing Core grid is constructed. Mutate
`gridOptions.columnModel` to add columns; preserve the existing columns and other
options, including buttons. Metadata contains the numeric `productId` of the
parent and one of these contexts:

| Context | View |
| --- | --- |
| `products.variants` | Variants tab in the product editor |
| `erp.variant-selection` | Variant selection when adding an ERP article |

```js
QUI.addEvent('quiqqerProductsVariantGridCreate', (Control, options, metadata) => {
    if (!['products.variants', 'erp.variant-selection'].includes(metadata.context)) {
        return;
    }

    options.columnModel.push({
        header: QUILocale.get('vendor/package', 'column.quantity'),
        dataIndex: 'vendorPackageQuantity',
        dataType: 'number',
        width: 120,
        sortable: false
    });
});
```

Use a package-specific `dataIndex`. Additional top-level row properties are
preserved when rows are passed to the grid, including zero, negative numbers,
fractions, `false`, and `null`. Standard columns retain their existing display
transformations. The `fields` member and standard property names are reserved;
do not replace them with extension data. `getVariantFields()` continues to
describe the attributes defining a variant.

## PHP: `onQuiqqerProductsVariantGridData`

Register this event in the extending package's `events.xml`.

Arguments: `array &$rows, array $variantIds, VariantParent $Parent, string $context`.

Both AJAX endpoints dispatch the same event after constructing their standard
rows. They use the contexts above. The product editor supplies only the current
page; the ERP selection dialog supplies all selectable variants. IDs are integers
in row order. The event also runs for an empty result, with empty rows and IDs.
Non-variant products keep their existing endpoint behavior.

```php
public static function onVariantGridData(
    array &$rows,
    array $variantIds,
    VariantParent $Parent,
    string $context
): void {
    $values = self::loadQuantities($variantIds);

    foreach ($rows as &$row) {
        $row['vendorPackageQuantity'] = $values[(int)$row['id']] ?? null;
    }

    unset($row);
}
```

Load additional values in a batch. Preserve IDs, row ordering, standard fields,
row count, and pagination. This event enriches cells; it does not implement
filtering or sorting. Set custom columns to `sortable: false`: sorting the current
page alone would produce incorrect global ordering. Custom server-side sorting
and filtering require separate query hooks and are not provided by this API.

The endpoints retain their admin permission checks. Context is a routing value,
not permission to access sensitive data. Enforce any additional permissions in
the listener. The event does not run for frontend variant endpoints.

The ERP changes dispatch the existing QUI event mechanism directly, so older
product installations do not require a new PHP helper or JavaScript module.
Consumers covering both views need versions of both packages containing these
hooks. Without a listener, no additional column or value is introduced.

## Main product overview

The main product overview already uses the backend search extension API. It has
the `products.panel` context and exposes `quiqqerProductsSearchGridCreate` for
columns and `onQuiqqerProductsBackendSearchGridData` for PHP row enrichment.
`quiqqerProductsSearchResultData` allows JavaScript cell formatting. These hooks
remain available alongside the variant hooks; register the appropriate listeners
to cover all three views. See [Backend product panel extensions](backend-panel-extensions.md)
and `quiqqer/productsearch/docs/backend-search-extensions.md` for their signatures,
filtering, and server-side sorting hooks.

## Validation

PHP integration tests exercise enrichment after pagination and on empty results.
JavaScript tests exercise both views with a fake extension, including zero,
negative and fractional values. Run each package's local PHPCS, PHPStan, PHPUnit,
and JavaScript checks. For `products`, run `node --test tests/javascript/*.test.cjs`.
The complete ERP JavaScript suite requires
`node --experimental-vm-modules --test tests/javascript/*.test.cjs` for its PDF tests.
