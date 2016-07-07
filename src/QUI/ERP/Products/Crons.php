<?php

/**
 * This file contains QUI\ERP\Products\EventHandling
 */
namespace QUI\ERP\Products;

use QUI;
use QUI\ERP\Products\Handler\Products;
use QUI\ERP\Products\Handler\Fields;

/**
 * Class EventHandling
 *
 * @package QUI\ERP\Products
 */
class Crons
{
    /**
     * Time for one product to update its cache (seconds)
     */
    const PRODUCT_CACHE_UPDATE_TIME = 3;

    /**
     * Updates cache values for all products
     *
     * @throws QUI\Exception
     */
    public static function updateProductCache()
    {
        $products = Products::getProducts();

        /** @var QUI\ERP\Products\Product\Model $Product */
        foreach ($products as $Product) {
            set_time_limit(self::PRODUCT_CACHE_UPDATE_TIME);

            try {
                $Product->updateCache();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addWarning(
                    'cron :: updateProductCache() :: Could not update cache'
                    . ' for Product #' . $Product->getId() . ' -> '
                    . $Exception->getMessage()
                );
            }
        }
    }

    /**
     * Generates tags for every entry in every product attribute list field
     * and assigns them to projects and products
     *
     * @hrows QUI\Exception
     */
    public static function generateProductAttributeListTags()
    {
        $fields = Fields::getFields(array(
            'where' => array(
                'type' => 'ProductAttributeList'
            )
        ));

        $projects = QUI::getProjectManager()->getProjects(false);

        /** @var QUI\ERP\Products\Field\Field $Field */
        foreach ($fields as $Field) {
            $options = $Field->getOptions();

            if (!isset($options['generate_tags'])
                || !$options['generate_tags']
            ) {
                continue;
            }

            if (!isset($options['entries'])) {
                QUI\System\Log::addWarning(
                    'Cron :: generateProductAttributeListTags -> Could not find'
                    . ' product attribute list entries for field #' . $Field->getId()
                );

                continue;
            }

            $tagsByLang = array();
            $tagList    = array();

            foreach ($options['entries'] as $entry) {
                foreach ($entry['title'] as $lang => $text) {
                    if (empty($lang)
                        || empty($text)
                    ) {
                        continue;
                    }

                    $tagsByLang[$lang][] = $text;
                    $tagList[]           = $text;
                }
            }

            foreach ($tagsByLang as $lang => $tags) {
                foreach ($tags as $tag) {
                    // add tags to projects
                    foreach ($projects as $project) {
                        self::addTagToProject($project, $lang, $tag);
                    }
                }
            }

            // add tags to products
            $products = $Field->getProducts();

            /** @var QUI\ERP\Products\Product\Product $Product */
            foreach ($products as $Product) {
                $tagFields = $Product->getFieldsByType('productstags.tags');

                /** @var QUI\ERP\Tags\Field $Field */
                foreach ($tagFields as $Field) {
                    if (!$Field->getOption('insert_tags')) {
                        continue;
                    }

                    foreach ($tagsByLang as $lang => $tags) {
                        $Field->addTags($tags, $lang);
                    }
                }

                $Product->save();
            }
        }
    }

    /**
     * Adds a tag to a project
     *
     * @param string $project - project name
     * @param string $lang - tag and project lang
     * @param string $tag - text of the tag
     */
    protected static function addTagToProject($project, $lang, $tag)
    {
        try {
            $Project    = QUI::getProjectManager()->getProject($project, $lang);
            $TagManager = new QUI\Tags\Manager($Project);

            $TagManager->add($tag, array('title' => $tag));
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addNotice(
                'Cron :: generateProductAttributeListTags -> Could not'
                . ' add tags to projects with lang "' . $lang . '" -> '
                . $Exception->getMessage()
            );
        }
    }
}
