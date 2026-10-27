<?php

namespace QUI\ERP\Products\Quality;

use QUI\ERP\Products\Field\Types\InputMultiLang;
use QUI\ERP\Products\Field\Types\TextareaMultiLang;
use QUI\ERP\Products\Handler\Fields;
use QUI\ERP\Products\Product\Model;

/**
 * Inspect the stored translations without applying language fallbacks or changing products.
 */
class TranslationCheck
{
    /**
     * @param list<string> $languages
     * @return list<array{fieldId: int, fieldTitle: string, languages: list<string>}>
     */
    public function check(Model $Product, array $languages): array
    {
        $issues = [];

        foreach ($Product->getFields() as $Field) {
            if (
                !$Field instanceof InputMultiLang
                && !$Field instanceof TextareaMultiLang
            ) {
                continue;
            }

            $values = $Field->getValue();
            $values = is_array($values) ? $values : [];
            $html = $Field instanceof TextareaMultiLang && (bool)$Field->getOption('html');
            $hasTranslation = false;
            $missing = [];

            foreach ($values as $value) {
                if ($this->hasContent($value, $html)) {
                    $hasTranslation = true;
                    break;
                }
            }

            if (
                !$hasTranslation
                && !$Field->isRequired()
                && $Field->getId() !== Fields::FIELD_TITLE
            ) {
                continue;
            }

            foreach ($languages as $language) {
                if (!$this->hasContent($values[$language] ?? null, $html)) {
                    $missing[] = $language;
                }
            }

            if ($missing !== []) {
                $issues[] = [
                    'fieldId' => $Field->getId(),
                    'fieldTitle' => $Field->getTitle(),
                    'languages' => $missing
                ];
            }
        }

        return $issues;
    }

    private function hasContent(mixed $value, bool $html): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $value = (string)$value;

        if ($html) {
            if (preg_match('/<(img|video|audio|iframe|object|embed)\b/i', $value)) {
                return true;
            }

            $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return preg_match('/[^\s\x{00a0}\x{200b}\x{feff}]/u', $value) === 1;
    }
}
