<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * Component is the base class for classes representing Craft components in terms of objects.
 *
 * @property string $type The class name that should be used to represent the field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Component extends Model implements ComponentInterface
{
    // Static
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName()
    {
        $classNameParts = explode('\\', static::class);
        $displayName = array_pop($classNameParts);

        return $displayName;
    }

    /**
     * @inheritdoc
     */
    public static function classHandle()
    {
        $classNameParts = explode('\\', static::class);
        $handle = array_pop($classNameParts);

        return strtolower($handle);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return static::class;
    }
}
