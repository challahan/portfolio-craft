<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\fields;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\helpers\Json;
use yii\db\Schema;

/**
 * Table represents a Table field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Table extends Field
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Table');
    }

    // Properties
    // =========================================================================

    /**
     * @var array The columns that should be shown in the table
     */
    public $columns;

    /**
     * @var array The default row values that new elements should have
     */
    public $defaults = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getContentColumnType()
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        $columns = $this->columns;
        $defaults = $this->defaults;

        if (!$columns) {
            $columns = [
                'col1' => [
                    'heading' => '',
                    'handle' => '',
                    'type' => 'singleline'
                ]
            ];

            // Update the actual settings model for getInputHtml()
            $this->columns = $columns;
        }

        if ($defaults === null) {
            $defaults = ['row1' => []];
        }

        $columnSettings = [
            'heading' => [
                'heading' => Craft::t('app', 'Column Heading'),
                'type' => 'singleline',
                'autopopulate' => 'handle'
            ],
            'handle' => [
                'heading' => Craft::t('app', 'Handle'),
                'code' => true,
                'type' => 'singleline'
            ],
            'width' => [
                'heading' => Craft::t('app', 'Width'),
                'code' => true,
                'type' => 'singleline',
                'width' => 50
            ],
            'type' => [
                'heading' => Craft::t('app', 'Type'),
                'class' => 'thin',
                'type' => 'select',
                'options' => [
                    'singleline' => Craft::t('app', 'Single-line Text'),
                    'multiline' => Craft::t('app', 'Multi-line text'),
                    'number' => Craft::t('app', 'Number'),
                    'checkbox' => Craft::t('app', 'Checkbox'),
                    'lightswitch' => Craft::t('app', 'Lightswitch'),
                ]
            ],
        ];

        Craft::$app->getView()->registerJsResource('js/TableFieldSettings.js');
        Craft::$app->getView()->registerJs('new Craft.TableFieldSettings('.
            Json::encode(Craft::$app->getView()->namespaceInputName('columns'), JSON_UNESCAPED_UNICODE).', '.
            Json::encode(Craft::$app->getView()->namespaceInputName('defaults'), JSON_UNESCAPED_UNICODE).', '.
            Json::encode($columns, JSON_UNESCAPED_UNICODE).', '.
            Json::encode($defaults, JSON_UNESCAPED_UNICODE).', '.
            Json::encode($columnSettings, JSON_UNESCAPED_UNICODE).
            ');');

        $columnsField = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => Craft::t('app', 'Table Columns'),
                    'instructions' => Craft::t('app', 'Define the columns your table should have.'),
                    'id' => 'columns',
                    'name' => 'columns',
                    'cols' => $columnSettings,
                    'rows' => $columns,
                    'addRowLabel' => Craft::t('app', 'Add a column'),
                    'initJs' => false
                ]
            ]);

        $defaultsField = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'editableTableField',
            [
                [
                    'label' => Craft::t('app', 'Default Values'),
                    'instructions' => Craft::t('app', 'Define the default values for the field.'),
                    'id' => 'defaults',
                    'name' => 'defaults',
                    'cols' => $columns,
                    'rows' => $defaults,
                    'initJs' => false
                ]
            ]);

        return $columnsField.$defaultsField;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, $element)
    {
        $input = '<input type="hidden" name="'.$this->handle.'" value="">';

        $tableHtml = $this->_getInputHtml($value, $element, false);

        if ($tableHtml) {
            $input .= $tableHtml;
        }

        return $input;
    }

    /**
     * @inheritdoc
     */
    public function prepareValue($value, $element)
    {
        if (is_string($value) && !empty($value)) {
            $value = Json::decode($value);
        }

        if (is_array($value) && $this->columns) {
            // Make the values accessible from both the col IDs and the handles
            foreach ($value as &$row) {
                foreach ($this->columns as $colId => $col) {
                    if ($col['handle']) {
                        $row[$col['handle']] = (isset($row[$colId]) ? $row[$colId] : null);
                    }
                }
            }

            return $value;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function prepareValueForDb($value, $element)
    {
        if (is_array($value)) {
            // Drop the string row keys
            $value = array_values($value);

            // Drop the column handle values
            if ($this->columns) {
                foreach ($value as &$row) {
                    foreach ($this->columns as $colId => $col) {
                        if ($col['handle']) {
                            unset($row[$col['handle']]);
                        }
                    }
                }
            }
        }

        return parent::prepareValueForDb($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, $element)
    {
        return $this->_getInputHtml($value, $element, true);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the field's input HTML.
     *
     * @param mixed                 $value
     * @param ElementInterface|null $element
     * @param boolean               $static
     *
     * @return string
     */
    private function _getInputHtml($value, $element, $static)
    {
        $columns = $this->columns;

        if ($columns) {
            // Translate the column headings
            foreach ($columns as &$column) {
                if (!empty($column['heading'])) {
                    $column['heading'] = Craft::t('site', $column['heading']);
                }
            }

            if ($this->isFresh($element)) {
                $defaults = $this->defaults;

                if (is_array($defaults)) {
                    $value = array_values($defaults);
                }
            }

            $id = Craft::$app->getView()->formatInputId($this->handle);

            return Craft::$app->getView()->renderTemplate('_includes/forms/editableTable',
                [
                    'id' => $id,
                    'name' => $this->handle,
                    'cols' => $columns,
                    'rows' => $value,
                    'static' => $static
                ]);
        }

        return null;
    }
}
