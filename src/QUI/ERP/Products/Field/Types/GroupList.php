<?php

/**
 * This file contains QUI\ERP\Products\Field\Types\GroupList
 */
namespace QUI\ERP\Products\Field\Types;

use QUI;

/**
 * Class GroupList
 *
 * Beschreibung des GroupList Typs:
 * In diesem Feld ist es Möglich eine Gruppe auszuwählen.
 * Wenn dieser Typ einem Produkt zugewiesen ist,
 * kann nun aus der zugewiesenen Gruppe ein benutzer ausgewählt werden-
 * Nutzbar zB. für Hersteller und Lieferanten
 *
 * D.h. Hersteller und Lieferanten sind Benutzer
 *
 * @package QUI\ERP\Products\Field
 * @todo Benutzergruppe beim Setup anlegen
 * @todo Einstellung zur Verfügung stellen, Lieferant und Hersteller gruppe festlegen
 */
class GroupList extends QUI\ERP\Products\Field\Field
{
    public function __construct($fieldId, array $params)
    {
        $this->setAttributes(array(
            'groupId' => false,
            'multipleUsers' => true
        ));

        parent::__construct($fieldId, $params);
    }

    public function getBackendView()
    {
        // TODO: Implement getBackendView() method.
    }

    public function getFrontendView()
    {
        // TODO: Implement getFrontendView() method.
    }

    /**
     * @return string
     */
    public function getJavaScriptControl()
    {
        return 'package/quiqqer/products/bin/controls/fields/types/GroupList';
    }

    /**
     * Check the value
     * is the value valid for the field type?
     *
     * @param mixed $value
     * @throws \QUI\Exception
     */
    public function validate($value)
    {
        // TODO: Implement validate() method.
    }

    /**
     * Cleanup the value, so the value is valid
     *
     * @param mixed $value
     * @return mixed
     * @throws \QUI\Exception
     */
    public function cleanup($value)
    {
        // TODO: Implement cleanup() method.

        return $value;
    }
}
