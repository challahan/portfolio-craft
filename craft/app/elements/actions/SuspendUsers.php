<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\actions;

use Craft;
use craft\app\base\ElementAction;
use craft\app\elements\db\ElementQuery;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\User;
use craft\app\helpers\Json;

/**
 * SuspendUsers represents a Suspend Users element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SuspendUsers extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel()
    {
        return Craft::t('app', 'Suspend');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);
        $userId = Json::encode(Craft::$app->getUser()->getIdentity()->id);

        $js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		type: {$type},
		batch: true,
		validateSelection: function(\$selectedItems)
		{
			for (var i = 0; i < \$selectedItems.length; i++)
			{
				if (\$selectedItems.eq(i).find('.element').data('id') == {$userId})
				{
					return false;
				}
			}

			return true;
		}
	});
})();
EOT;

        Craft::$app->getView()->registerJs($js);
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query)
    {
        /** @var ElementQuery $query */
        // Get the users that aren't already suspended
        $query->status = [
            User::STATUS_ACTIVE,
            User::STATUS_LOCKED,
            User::STATUS_PENDING,
        ];

        /** @var User[] $users */
        $users = $query->all();

        foreach ($users as $user) {
            if (!$user->getIsCurrent()) {
                Craft::$app->getUsers()->suspendUser($user);
            }
        }

        $this->setMessage(Craft::t('app', 'Users suspended.'));

        return true;
    }
}
