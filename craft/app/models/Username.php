<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;

/**
 * Username model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Username extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string Username
     */
    public $username;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['username'], 'string', 'max' => 100],
            [['username'], 'safe', 'on' => 'search'],
        ];

        if (!Craft::$app->getConfig()->get('useEmailAsUsername')) {
            $rules[] = [['username'], 'required'];
        }

        return $rules;
    }

    /**
     * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
     * logged to the `craft/storage/logs` folder as a warning.
     *
     * In addition, we check that the username does not have any whitespace in it.
     *
     * @param null    $attributes
     * @param boolean $clearErrors
     *
     * @return boolean|null
     */
    public function validate($attributes = null, $clearErrors = true)
    {
        // Don't allow whitespace in the username.
        if (preg_match('/\s+/', $this->username)) {
            $this->addError('username', Craft::t('app', 'Spaces are not allowed in the username.'));
        }

        return parent::validate($attributes, false);
    }
}
