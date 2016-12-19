<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\elements\db\TagQuery;
use craft\app\models\TagGroup;

/**
 * Tag represents a tag element.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Tag extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName()
    {
        return Craft::t('app', 'Tag');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized()
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return TagQuery The newly created [[TagQuery]] instance.
     */
    public static function find()
    {
        return new TagQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function getSources($context = null)
    {
        $sources = [];

        foreach (Craft::$app->getTags()->getAllTagGroups() as $tagGroup) {
            $key = 'taggroup:'.$tagGroup->id;

            $sources[$key] = [
                'label' => Craft::t('site', $tagGroup->name),
                'criteria' => ['groupId' => $tagGroup->id]
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    public static function getEditorHtml(ElementInterface $element)
    {
        /** @var Tag $element */
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField',
            [
                [
                    'label' => Craft::t('app', 'Title'),
                    'siteId' => $element->siteId,
                    'id' => 'title',
                    'name' => 'title',
                    'value' => $element->title,
                    'errors' => $element->getErrors('title'),
                    'first' => true,
                    'autofocus' => true,
                    'required' => true
                ]
            ]);

        $html .= parent::getEditorHtml($element);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public static function saveElement(ElementInterface $element, $params)
    {
        /** @var Tag $element */
        return Craft::$app->getTags()->saveTag($element);
    }

    // Properties
    // =========================================================================

    /**
     * @var integer Group ID
     */
    public $groupId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['groupId'], 'number', 'integerOnly' => true];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        $tagGroup = $this->getGroup();

        if ($tagGroup) {
            return $tagGroup->getFieldLayout();
        }

        return null;
    }

    /**
     * Returns the tag's group.
     *
     * @return TagGroup|null
     */
    public function getGroup()
    {
        if ($this->groupId) {
            return Craft::$app->getTags()->getTagGroupById($this->groupId);
        }

        return null;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Returns the tag's title.
     *
     * @deprecated Deprecated in 2.3. Use [[$title]] instead.
     * @return string
     *
     * @todo       Remove this method in Craft 4.
     */
    public function getName()
    {
        Craft::$app->getDeprecator()->log('Tag::name', 'The Tag ‘name’ property has been deprecated. Use ‘title’ instead.');

        return $this->title;
    }
}
