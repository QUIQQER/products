<?php

namespace QUITests\ERP\Products\Unit\Field\Types;

use PHPUnit\Framework\TestCase;
use QUI\ERP\Products\Field\Types\AttributeGroupFrontendValueView;
use QUI\ERP\Products\Field\Types\AttributeGroupFrontendView;

class AttributeGroupViewTest extends TestCase
{
    public function testChangeableViewRendersVisibleSelectionAndEntryState(): void
    {
        $View = new AttributeGroupFrontendView($this->getViewData('blue'));

        $html = $View->create();

        self::assertStringContainsString('data-field-type="AttributeGroup"', $html);
        self::assertStringContainsString('data-field="9204"', $html);
        self::assertStringContainsString('name="field-9204"', $html);
        self::assertStringContainsString('required="required"', $html);
        self::assertStringContainsString('value="blue"', $html);
        self::assertStringContainsString('selected="selected"', $html);
        self::assertStringContainsString('Blau', $html);
        self::assertStringContainsString('disabled="disabled"', $html);
        self::assertStringContainsString('Grün', $html);
        self::assertStringNotContainsString('Rot', $html);
    }

    public function testFixedViewRendersOnlySelectedAttributeText(): void
    {
        $data = $this->getViewData('blue');
        $data['changeable'] = false;
        $View = new AttributeGroupFrontendView($data);

        $html = $View->create();

        self::assertStringContainsString('Blau', $html);
        self::assertStringNotContainsString('<select', $html);
        self::assertStringNotContainsString('Grün', $html);
    }

    public function testFixedViewUsesPlaceholderWhenNoEntriesExist(): void
    {
        $data = $this->getViewData(null);
        $data['changeable'] = false;
        $data['options']['entries'] = [];
        $View = new AttributeGroupFrontendView($data);

        self::assertStringContainsString('---', $View->create());
    }

    public function testValueViewResolvesLocalizedAttributeTitle(): void
    {
        $View = new AttributeGroupFrontendValueView($this->getViewData('blue'));

        $html = $View->create();

        self::assertStringContainsString('Blau', $html);
        self::assertStringNotContainsString('value="blue"', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(mixed $value): array
    {
        return [
            'id' => 9204,
            'type' => 'AttributeGroup',
            'value' => $value,
            'isPublic' => true,
            'isRequired' => true,
            'changeable' => true,
            'options' => [
                'entries' => [
                    [
                        'title' => ['de' => 'Rot'],
                        'valueId' => 'red',
                        'hide' => true
                    ],
                    [
                        'title' => ['de' => 'Blau'],
                        'valueId' => 'blue',
                        'selected' => true
                    ],
                    [
                        'title' => 'Grün',
                        'valueId' => 'green',
                        'disabled' => true
                    ]
                ]
            ]
        ];
    }
}
