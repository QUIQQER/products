<?php

/**
 * This file contains \QUI\ERP\Products\MCP\Provider
 */

namespace QUI\ERP\Products\MCP;

use Mcp\Server\Builder;
use QUI\AI\MCP\ProviderInterface;
use QUI\AI\MCP\Server;
use QUI\ERP\Products\MCP\Category\CreateCategory;
use QUI\ERP\Products\MCP\Category\DeleteCategory;
use QUI\ERP\Products\MCP\Category\GetCategory;
use QUI\ERP\Products\MCP\Category\SearchCategories;
use QUI\ERP\Products\MCP\Category\UpdateCategory;
use QUI\ERP\Products\MCP\Field\CreateField;
use QUI\ERP\Products\MCP\Field\DeleteField;
use QUI\ERP\Products\MCP\Field\GetField;
use QUI\ERP\Products\MCP\Field\ListFieldTypes;
use QUI\ERP\Products\MCP\Field\SearchFields;
use QUI\ERP\Products\MCP\Field\UpdateField;
use QUI\ERP\Products\MCP\Product\ActivateProduct;
use QUI\ERP\Products\MCP\Product\CopyProduct;
use QUI\ERP\Products\MCP\Product\CreateProduct;
use QUI\ERP\Products\MCP\Product\DeactivateProduct;
use QUI\ERP\Products\MCP\Product\DeleteProduct;
use QUI\ERP\Products\MCP\Product\GetProduct;
use QUI\ERP\Products\MCP\Product\GetProductPermissions;
use QUI\ERP\Products\MCP\Product\SearchProducts;
use QUI\ERP\Products\MCP\Product\UpdateProduct;
use QUI\ERP\Products\MCP\Product\UpdateProductPermissions;
use QUI\ERP\Products\MCP\Variant\BulkVariantAction;
use QUI\ERP\Products\MCP\Variant\CreateVariant;
use QUI\ERP\Products\MCP\Variant\GenerateVariants;
use QUI\ERP\Products\MCP\Variant\GetVariantInheritance;
use QUI\ERP\Products\MCP\Variant\ListProductTypes;
use QUI\ERP\Products\MCP\Variant\ListVariants;
use QUI\ERP\Products\MCP\Variant\SetDefaultVariant;
use QUI\ERP\Products\MCP\Variant\UpdateVariantInheritance;
use QUI\MCP\ToolInterface;
use QUI\Permissions\Permission;
use Throwable;

/**
 * Products MCP provider
 */
class Provider implements ProviderInterface
{
    /**
     * @var array<ToolInterface>
     */
    protected array $tools;

    public function __construct()
    {
        $this->tools = [
            new SearchProducts(),
            new GetProduct(),
            new CreateProduct(),
            new CopyProduct(),
            new UpdateProduct(),
            new ActivateProduct(),
            new DeactivateProduct(),
            new DeleteProduct(),
            new GetProductPermissions(),
            new UpdateProductPermissions(),
            new SearchCategories(),
            new GetCategory(),
            new CreateCategory(),
            new UpdateCategory(),
            new DeleteCategory(),
            new ListFieldTypes(),
            new SearchFields(),
            new GetField(),
            new CreateField(),
            new UpdateField(),
            new DeleteField(),
            new ListProductTypes(),
            new ListVariants(),
            new CreateVariant(),
            new GenerateVariants(),
            new SetDefaultVariant(),
            new GetVariantInheritance(),
            new UpdateVariantInheritance(),
            new BulkVariantAction()
        ];
    }

    public function register(Builder $serverBuilder): void
    {
        if (!$this->canUseMcp()) {
            return;
        }

        foreach ($this->tools as $Tool) {
            $Tool->register($serverBuilder);
        }
    }

    protected function canUseMcp(): bool
    {
        try {
            Permission::checkPermission(
                AbstractTool::PRODUCTS_MCP_PERMISSION,
                Server::getRequestUser()
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
