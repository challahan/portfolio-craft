<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class Structure record.
 *
 * @property integer            $id        ID
 * @property integer            $maxLevels Max levels
 * @property StructureElement[] $elements  Elements
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Structure extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['maxLevels'],
                'number',
                'min' => 1,
                'max' => 65535,
                'integerOnly' => true
            ],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%structures}}';
    }

    /**
     * Returns the structure’s elements.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElements()
    {
        return $this->hasMany(StructureElement::class, ['structureId' => 'id']);
    }
}
