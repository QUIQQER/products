Produktverwaltung
========

Erstellen Sie Ihren Shop mit den QUIQQER Produkten. 
QUIQQER Produkte sind flexible Produkte welche flexible Eigenschaften besitzen können.

Paketname:

    quiqqer/products


ERP Stack
----

Wir empfehlen weitere Pakete zu installieren:

- quiqqer/areas
- quiqqer/currency
- quiqqer/discount
- quiqqer/products
- quiqqer/tax


Features (Funktionen)
--------

- Produktkategorien
- Produktfelder
- Produktverwaltung

Installation
------------

Der Paketname ist: quiqqer/products


Mitwirken
----------

- Issue Tracker: https://dev.quiqqer.com/quiqqer/products/issues
- Source Code: https://dev.quiqqer.com/quiqqer/products/tree/master


Support
-------

Falls Sie ein Fehler gefunden haben, oder Verbesserungen wünschen,
dann können Sie gerne an support@pcsg.de eine E-Mail schreiben.


Lizenz
-------



Entwickler
--------

```php

// Ein Produkt bekommen
QUI\ERP\Products\Handler\Products::getProduct( ID );

// Ein Produkt Feld bekommen
QUI\ERP\Products\Handler\Fields::getField( ID );

```

Events
======

- onQuiqqerProductsPriceFactorsInit [PriceFactors, UniqueProduct]
- onQuiqqerProductsProductCleanup
- onQuiqqerProductsFieldsClearCache
- onQuiqqerProductsCategoriesClearCache

- onQuiqqerProductsCalcListProduct [PriceFactor, Product]
- onQuiqqerProductsCalcList [Product]

Produkt Events
------

- onQuiqqerProductsProductCreate [Product]
- onQuiqqerProductsProductCopy [Product]

- onQuiqqerProductsProductSave [Product]
- onQuiqqerProductsProductUserSave [Product]

- onQuiqqerProductsProductActivate [Product]
- onQuiqqerProductsProductDeactivate [Product]
- onQuiqqerProductsProductDelete [Product]
- onQuiqqerProductsProductDeleteBegin [Product]
- onQuiqqerProductsProductCreateMediaFolder [Product]

Field Events
------

- onQuiqqerProductsFieldsCreate [Field]
- onQuiqqerProductsFieldDelete [Field]
- onQuiqqerProductsFieldDeleteSystemfield [Field]
- onQuiqqerProductsFieldSave [Field]


Kategorien Events
------

- onQuiqqerProductsCategoryCreate [Category]
- onQuiqqerProductsCategoryAddField [Category, Field]
- onQuiqqerProductsCategoryClearFields [Category]
- onQuiqqerProductsCategorySave [Category]
- onQuiqqerProductsCategoryDelete [Category]
- onQuiqqerProductsCategorySetFieldsToAllProducts [Category]
