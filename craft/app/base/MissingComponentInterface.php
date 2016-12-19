<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * MissingComponentInterface defines the common interface for classes that represent a missing component class.
 *
 * A class implementing this interface should also implement [[ComponentInterface]] and [[\yii\base\Arrayable]],
 * and use [[MissingComponentTrait]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface MissingComponentInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the expected component class name.
     *
     * @return string
     */
    public function getType();
}
