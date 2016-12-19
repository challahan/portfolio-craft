<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\elements\Category;

/**
 * Categories represents a Categories field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Categories extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Categories');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType()
    {
        return Category::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel()
    {
        return Craft::t('app', 'Add a category');
    }

    // Properties
    // =========================================================================

    /**
     * Whether to allow multiple source selection in the settings.
     *
     * @var boolean $allowMultipleSources
     */
    protected $allowMultipleSources = false;

    /**
     * The JS class that should be initialized for the input.
     *
     * @var string|null $inputJsClass
     */
    protected $inputJsClass = 'Craft.CategorySelectInput';

    /**
     * Template to use for field rendering
     *
     * @var string
     */
    protected $inputTemplate = '_components/fieldtypes/Categories/input';

    /**
     * Whether the elements have a custom sort order.
     *
     * @var boolean $sortable
     */
    protected $sortable = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        // Make sure the field is set to a valid category group
        if ($this->source) {
            /** @var Category $class */
            $class = static::elementType();
            $source = $class::getSourceByKey($this->source, 'field');
        }

        if (empty($source)) {
            return '<p class="error">'.Craft::t('app', 'This field is not set to a valid category group.').'</p>';
        }

        return parent::getInputHtml($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element)
    {
        $value = $this->getElementValue($element);

        // Make sure something was actually posted
        if ($value !== null) {
            $ids = $value->ids();

            // Fill in any gaps
            $ids = Craft::$app->getCategories()->fillGapsInCategoryIds($ids);

            Craft::$app->getRelations()->saveRelations($this, $element, $ids);
        }
    }
}
