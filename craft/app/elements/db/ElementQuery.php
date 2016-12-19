<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\elements\db;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use /** @noinspection PhpUndefinedClassInspection */
    craft\app\behaviors\ElementQueryBehavior;
use /** @noinspection PhpUndefinedClassInspection */
    craft\app\behaviors\ElementQueryTrait;
use craft\app\db\Connection;
use craft\app\db\FixedOrderExpression;
use craft\app\db\Query;
use craft\app\db\QueryAbortedException;
use craft\app\events\Event;
use craft\app\events\PopulateElementEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Db;
use craft\app\helpers\StringHelper;
use craft\app\models\Site;
use IteratorAggregate;
use yii\base\Arrayable;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\base\NotSupportedException;

/**
 * ElementQuery represents a SELECT SQL statement for elements in a way that is independent of DBMS.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 *
 * @property string|Site $site The site or site handle that the elements should be returned in
 *
 * @method ElementInterface|array nth($n, $db = null)
 */
class ElementQuery extends Query implements ElementQueryInterface, Arrayable, Countable, IteratorAggregate, ArrayAccess
{
    // Traits
    // =========================================================================

    use ArrayableTrait;
    use /** @noinspection PhpUndefinedClassInspection */
        ElementQueryTrait;

    // Constants
    // =========================================================================

    /**
     * @event Event An event that is triggered at the beginning of preparing an element query for the query builder.
     */
    const EVENT_BEFORE_PREPARE = 'beforePrepare';

    /**
     * @event Event An event that is triggered at the end of preparing an element query for the query builder.
     */
    const EVENT_AFTER_PREPARE = 'afterPrepare';

    /**
     * @event PopulateElementEvent The event that is triggered after an element is populated.
     */
    const EVENT_AFTER_POPULATE_ELEMENT = 'afterPopulateElement';

    // Properties
    // =========================================================================

    /**
     * @var string The name of the [[ElementInterface]] class.
     */
    public $elementType;

    /**
     * @var Query The query object created by [[prepare()]]
     * @see prepare()
     */
    public $query;

    /**
     * @var Query The subselect’s query object created by [[prepare()]]
     * @see prepare()
     */
    public $subQuery;

    /**
     * @var string|null The content table that will be joined by this query.
     */
    public $contentTable = '{{%content}}';

    /**
     * @var FieldInterface[] The fields that may be involved in this query.
     */
    public $customFields;

    // Result formatting attributes
    // -------------------------------------------------------------------------

    /**
     * @var boolean Whether to return each element as an array. If false (default), an object
     * of [[elementType]] will be created to represent each element.
     */
    public $asArray;

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var mixed The element ID(s). Prefix IDs with "not " to exclude them.
     */
    public $id;

    /**
     * @var mixed The element UID(s). Prefix UIDs with "not " to exclude them.
     */
    public $uid;

    /**
     * @var boolean Whether results should be returned in the order specified by [[id]].
     */
    public $fixedOrder;

    /**
     * @var string|string[] The status(es) that the resulting elements must have.
     */
    public $status = 'enabled';

    /**
     * @var boolean Whether to return only archived elements.
     */
    public $archived;

    /**
     * @var mixed When the resulting elements must have been created.
     */
    public $dateCreated;

    /**
     * @var mixed When the resulting elements must have been last updated.
     */
    public $dateUpdated;

    /**
     * @var integer The site ID that the elements should be returned in.
     */
    public $siteId;

    /**
     * @var boolean Whether the elements must be enabled for the chosen site.
     */
    public $enabledForSite = true;

    /**
     * @var integer|array|Element The element relation criteria.
     */
    public $relatedTo;

    /**
     * @var string The title that resulting elements must have.
     */
    public $title;

    /**
     * @var string The slug that resulting elements must have.
     */
    public $slug;

    /**
     * @var string The URI that the resulting element must have.
     */
    public $uri;

    /**
     * @var string The search term to filter the resulting elements by.
     */
    public $search;

    /**
     * @var string|string[] The reference code(s) used to identify the element(s).
     * This property is set when accessing elements via their reference tags, e.g. {entry:section/slug}.
     */
    public $ref;

    /**
     * @var mixed The eager-loading declaration
     */
    public $with;

    /**
     * @inheritdoc
     */
    public $orderBy;

    // Structure parameters
    // -------------------------------------------------------------------------

    /**
     * @var integer The structure ID that should be used to join in the structureelements table.
     */
    public $structureId;

    /**
     * @var integer The element’s level within the structure
     */
    public $level;

    /**
     * @var integer|Element The element (or its ID) that results must be an ancestor of.
     */
    public $ancestorOf;

    /**
     * @var integer The maximum number of levels that results may be separated from [[ancestorOf]].
     */
    public $ancestorDist;

    /**
     * @var integer|Element The element (or its ID) that results must be a descendant of.
     */
    public $descendantOf;

    /**
     * @var integer The maximum number of levels that results may be separated from [[descendantOf]].
     */
    public $descendantDist;

    /**
     * @var integer|Element The element (or its ID) that the results must be a sibling of.
     */
    public $siblingOf;

    /**
     * @var integer|Element The element (or its ID) that the result must be the previous sibling of.
     */
    public $prevSiblingOf;

    /**
     * @var integer|Element The element (or its ID) that the result must be the next sibling of.
     */
    public $nextSiblingOf;

    /**
     * @var integer|Element The element (or its ID) that the results must be positioned before.
     */
    public $positionedBefore;

    /**
     * @var integer|Element The element (or its ID) that the results must be positioned after.
     */
    public $positionedAfter;

    // Deprecated Properties
    // -------------------------------------------------------------------------

    /**
     * @var string Child field
     * @deprecated in 3.0. Use [[relatedTo]] instead.
     */
    public $childField;

    /**
     * @var array Child of
     * @deprecated in 3.0. Use [[relatedTo]] instead.
     */
    public $childOf;

    /**
     * @var integer Depth
     * @deprecated in 3.0. Use [[relatedTo]] instead.
     */
    public $depth;

    /**
     * @var string Parent field
     * @deprecated in 3.0. Use [[relatedTo]] instead.
     */
    public $parentField;

    /**
     * @var array Parent of
     * @deprecated in 3.0. Use [[relatedTo]] instead.
     */
    public $parentOf;

    // For internal use
    // -------------------------------------------------------------------------

    /**
     * @var Element[] The cached element query result
     * @see setCachedResult()
     */
    private $_result;

    /**
     * @var Element[] The criteria params that were set when the cached element query result was set
     * @see setCachedResult()
     */
    private $_resultCriteria;

    /**
     * @var array
     */
    private $_searchScores;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string $elementType The element type class associated with this query
     * @param array  $config      Configurations to be applied to the newly created query object
     */
    public function __construct($elementType, $config = [])
    {
        $this->elementType = $elementType;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if ($name === 'order') {
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element parameter has been deprecated. Use “orderBy” instead.');

            return isset($this->orderBy);
        }

        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        switch ($name) {
            case 'locale': {
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element parameter has been deprecated. Use “site” or “siteId” instead.');

                if ($this->siteId) {
                    $site = Craft::$app->getSites()->getSiteById($this->siteId);

                    if ($site) {
                        return $site->handle;
                    }
                }

                return null;
            }
            case 'order': {
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element parameter has been deprecated. Use “orderBy” instead.');

                return $this->orderBy;
            }
            default: {
                return parent::__get($name);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'site':
                $this->site($value);
                break;
            case 'localeEnabled':
                Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The “localeEnabled” element parameter has been deprecated. Use “enabledForSite” instead.');
                $this->enabledForSite($value);
                break;
            case 'locale':
                Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element parameter has been deprecated. Use “site” or “siteId” instead.');
                $this->site($value);
                break;
            case 'order':
                Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element parameter has been deprecated. Use “orderBy” instead.');
                $this->orderBy = $value;
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if ($name === 'order') {
            Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element parameter has been deprecated. Use “orderBy” instead.');

            if (count($params) == 1) {
                $this->orderBy = $params[0];
            } else {
                $this->orderBy = $params;
            }

            return $this;
        }

        return parent::__call($name, $params);
    }

    /**
     * Required by the IteratorAggregate interface.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->all());
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param integer|string $name The offset to check
     *
     * @return boolean
     */
    public function offsetExists($name)
    {
        if (is_numeric($name)) {
            $offset = $this->offset;
            $limit = $this->limit;

            $this->offset = $name;
            $this->limit = 1;

            $exists = $this->exists();

            $this->offset = $offset;
            $this->limit = $limit;

            return $exists;
        }

        return $this->__isset($name);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param integer|string $name The offset to get
     *
     * @return mixed The element at the given offset
     */
    public function offsetGet($name)
    {
        if (is_numeric($name)) {
            return $this->nth($name);
        }

        return $this->__get($name);
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param string $name  The offset to set
     * @param mixed  $value The value
     *
     * @return void
     * @throws NotSupportedException if $name is numeric
     */
    public function offsetSet($name, $value)
    {
        if (is_numeric($name)) {
            throw new NotSupportedException('ElementQuery does not support setting an element using array syntax.');
        } else {
            $this->__set($name, $value);
        }
    }

    /**
     * Required by the ArrayAccess interface.
     *
     * @param string $name The offset to unset
     *
     * @throws NotSupportedException if $name is numeric
     */
    public function offsetUnset($name)
    {
        if (is_numeric($name)) {
            throw new NotSupportedException('ElementQuery does not support unsetting an element using array syntax.');
        } else {
            return $this->__unset($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        /** @noinspection PhpUndefinedClassInspection */
        return [
            'customFields' => ElementQueryBehavior::class,
        ];
    }

    // Element criteria parameter setters
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function configure($criteria)
    {
        // Be forgiving of empty params
        if (!empty($criteria)) {
            Craft::configure($this, $criteria);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function asArray($value = true)
    {
        $this->asArray = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function id($value)
    {
        $this->id = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function uid($value)
    {
        $this->uid = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fixedOrder($value = true)
    {
        $this->fixedOrder = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function status($value)
    {
        $this->status = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function archived($value = true)
    {
        $this->archived = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dateCreated($value = true)
    {
        $this->dateCreated = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dateUpdated($value = true)
    {
        $this->dateUpdated = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     */
    public function site($value)
    {
        if ($value instanceof Site) {
            $this->siteId = $value->id;
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if (!$site) {
                throw new Exception('Invalid site hadle: '.$value);
            }

            $this->siteId = $site->id;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function siteId($value)
    {
        $this->siteId = $value;

        return $this;
    }

    /**
     * Sets the [[locale]] property.
     *
     * @param string $value The property value
     *
     * @return $this self reference
     * @deprecated in 3.0. Use [[site]] or [[siteId]] instead.
     */
    public function locale($value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::locale()', 'The “locale” element parameter has been deprecated. Use “site” or “siteId” instead.');
        $this->site($value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function enabledForSite($value = true)
    {
        $this->enabledForSite = $value;

        return $this;
    }

    /**
     * Sets the [[enabledForSite]] property.
     *
     * @param mixed $value The property value (defaults to true)
     *
     * @return $this self reference
     * @deprecated in 3.0. Use [[enabledForSite]] instead.
     */
    public function localeEnabled($value = true)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::localeEnabled()', 'The “localeEnabled” element parameter has been deprecated. Use “enabledForSite” instead.');
        $this->enabledForSite = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function relatedTo($value)
    {
        $this->relatedTo = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function title($value)
    {
        $this->title = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function slug($value)
    {
        $this->slug = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function uri($value)
    {
        $this->uri = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function search($value)
    {
        $this->search = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function ref($value)
    {
        $this->ref = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function with($value)
    {
        $this->with = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function structureId($value)
    {
        $this->structureId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function level($value)
    {
        $this->level = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function ancestorOf($value)
    {
        $this->ancestorOf = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function ancestorDist($value)
    {
        $this->ancestorDist = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function descendantOf($value)
    {
        $this->descendantOf = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function descendantDist($value)
    {
        $this->descendantDist = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function siblingOf($value)
    {
        $this->siblingOf = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function prevSiblingOf($value)
    {
        $this->prevSiblingOf = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function nextSiblingOf($value)
    {
        $this->nextSiblingOf = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function positionedBefore($value)
    {
        $this->positionedBefore = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function positionedAfter($value)
    {
        $this->positionedAfter = $value;

        return $this;
    }

    // Query preparation/execution
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     *
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if ($this->id !== null && empty($this->id)) {
            throw new QueryAbortedException();
        }

        /** @var Element $class */
        $class = $this->elementType;

        // Make sure the siteId param is set
        if (!$class::isLocalized()) {
            // The criteria *must* be set to the primary site ID
            $this->siteId = Craft::$app->getSites()->getPrimarySite()->id;
        } else if (!$this->siteId) {
            // Default to the current site
            $this->siteId = Craft::$app->getSites()->currentSite->id;
        }

        // Build the query
        // ---------------------------------------------------------------------

        $this->query = new Query();
        $this->subQuery = new Query();

        // Give other classes a chance to make changes up front
        if (!$this->beforePrepare()) {
            throw new QueryAbortedException();
        }

        if ($this->select) {
            $this->query->select = $this->select;
        } else {
            $this->query->addSelect([
                'elements.id',
                'elements.uid',
                'elements.type',
                'elements.enabled',
                'elements.archived',
                'elements.dateCreated',
                'elements.dateUpdated',
                'elements_i18n.slug',
                'elements_i18n.uri',
                'enabledForSite' => 'elements_i18n.enabled',
            ]);
        }

        $this->query
            ->from(['subquery' => $this->subQuery])
            ->innerJoin('{{%elements}} elements', 'elements.id = subquery.elementsId')
            ->innerJoin('{{%elements_i18n}} elements_i18n', 'elements_i18n.id = subquery.elmentsI18nId');

        $this->subQuery
            ->addSelect([
                'elementsId' => 'elements.id',
                'elmentsI18nId' => 'elements_i18n.id',
            ])
            ->from(['elements' => '{{%elements}}'])
            ->innerJoin('{{%elements_i18n}} elements_i18n', 'elements_i18n.elementId = elements.id')
            ->andWhere('elements_i18n.siteId = :siteId')
            ->andWhere($this->where)
            ->offset($this->offset)
            ->limit($this->limit)
            ->addParams($this->params)
            ->addParams([':siteId' => $this->siteId]);

        if ($class::hasContent() && $this->contentTable) {
            $this->customFields = $class::getFieldsForElementsQuery($this);
            $this->_joinContentTable($class);
        } else {
            $this->customFields = null;
        }

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam('elements.id', $this->id, $this->subQuery->params));
        }

        if ($this->uid) {
            $this->subQuery->andWhere(Db::parseParam('elements.uid', $this->uid, $this->subQuery->params));
        }

        if ($this->archived) {
            $this->subQuery->andWhere('elements.archived = 1');
        } else {
            $this->subQuery->andWhere('elements.archived = 0');
            $this->_applyStatusParam($class);
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateCreated', $this->dateCreated, $this->subQuery->params));
        }

        if ($this->dateUpdated) {
            $this->subQuery->andWhere(Db::parseDateParam('elements.dateUpdated', $this->dateUpdated, $this->subQuery->params));
        }

        if ($this->title && $class::hasTitles()) {
            $this->subQuery->andWhere(Db::parseParam('content.title', $this->title, $this->subQuery->params));
        }

        if ($this->slug) {
            $this->subQuery->andWhere(Db::parseParam('elements_i18n.slug', $this->slug, $this->subQuery->params));
        }

        if ($this->uri) {
            $this->subQuery->andWhere(Db::parseParam('elements_i18n.uri', $this->uri, $this->subQuery->params));
        }

        if ($this->enabledForSite) {
            $this->subQuery->andWhere('elements_i18n.enabled = 1');
        }

        $this->_applyRelatedToParam();
        $this->_applyStructureParams($class);
        $this->_applySearchParam($builder->db);
        $this->_applyOrderByParams($builder->db);

        // Give other classes a chance to make changes up front
        if (!$this->afterPrepare()) {
            throw new QueryAbortedException();
        }

        // Pass the query back
        return $this->query;
    }

    /**
     * @inheritdoc
     *
     * @return ElementInterface[]|array The resulting elements.
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        // Should we set a search score on the elements?
        if ($this->_searchScores !== null) {
            foreach ($rows as $row) {
                if (isset($this->_searchScores[$row['id']])) {
                    $row['searchScore'] = $this->_searchScores[$row['id']];
                }
            }
        }

        $elements = $this->_createElements($rows);

        return $elements;
    }

    /**
     * @inheritdoc
     */
    public function count($q = '*', $db = null)
    {
        $cachedResult = $this->getCachedResult();

        if ($cachedResult !== null) {
            return count($cachedResult);
        }

        return parent::count($q, $db);
    }

    /**
     * @inheritdoc
     */
    public function all($db = null)
    {
        $cachedResult = $this->getCachedResult();

        if ($cachedResult !== null) {
            return $cachedResult;
        }

        return parent::all($db);
    }

    /**
     * @inheritdoc
     */
    public function one($db = null)
    {
        $row = parent::one($db);

        if ($row !== false) {
            return $this->_createElement($row) ?: null;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function ids($db = null)
    {
        // TODO: Remove this in Craft 4
        // Make sure $db is not a list of attributes
        if ($this->_setAttributes($db)) {
            Craft::$app->getDeprecator()->log('ElementQuery::ids($attributes)', 'Passing a list of parameters to the ids() element query function is now deprecated. Set the parameters before calling ids().');
            $db = null;
        }

        return $this->column('elements.id', $db);
    }

    /**
     * Returns the resulting elements set by [[setCachedResult()]], if the criteria params haven’t changed since then.
     *
     * @return ElementInterface[]|null $elements The resulting elements, or null if setCachedResult() was never called or the criteria has changed
     * @see setCachedResult()
     */
    public function getCachedResult()
    {
        if ($this->_result === null) {
            return null;
        }

        // Make sure the criteria hasn't changed
        if ($this->_resultCriteria !== $this->toArray([], [], false)) {
            $this->_result = null;
            return null;
        }

        return $this->_result;
    }

    /**
     * Sets the resulting elements.
     *
     * If this is called, [[all()]] will return these elements rather than initiating a new SQL query,
     * as long as none of the parameters have changed since setCachedResult() was called.
     *
     * @param ElementInterface[] $elements The resulting elements.
     *
     * @see getCachedResult()
     */
    public function setCachedResult($elements)
    {
        $this->_result = $elements;
        $this->_resultCriteria = $this->toArray([], [], false);
    }

    // Arrayable methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = array_unique(array_merge(
            array_keys(Craft::getObjectVars($this)),
            array_keys(Craft::getObjectVars($this->getBehavior('customFields')))
        ));
        $fields = array_combine($fields, $fields);
        unset($fields['query'], $fields['subQuery'], $fields['owner']);

        return $fields;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * Sets the [[orderBy]] property.
     *
     * @param string $value The property value
     *
     * @return $this self reference
     * @deprecated in Craft 3.0. Use [[orderBy()]] instead.
     */
    public function order($value)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::order()', 'The “order” element parameter has been deprecated. Use “orderBy” instead.');

        return $this->orderBy($value);
    }

    /**
     * Returns all elements that match the criteria.
     *
     * @param array $attributes Any last-minute parameters that should be added.
     *
     * @return ElementInterface[] The matched elements.
     * @deprecated in Craft 3.0. Use all() instead.
     */
    public function find($attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::find()', 'The find() function used to query for elements is now deprecated. Use all() instead.');
        $this->_setAttributes($attributes);

        return $this->all();
    }

    /**
     * Returns the first element that matches the criteria.
     *
     * @param array|null $attributes
     *
     * @return ElementInterface|null
     * @deprecated in Craft 3.0. Use one() instead.
     */
    public function first($attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::first()', 'The first() function used to query for elements is now deprecated. Use one() instead.');
        $this->_setAttributes($attributes);

        return $this->one();
    }

    /**
     * Returns the last element that matches the criteria.
     *
     * @param array|null $attributes
     *
     * @return ElementInterface|null
     * @deprecated in Craft 3.0. Use nth() instead.
     */
    public function last($attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::last()', 'The last() function used to query for elements is now deprecated. Use nth() instead.');
        $this->_setAttributes($attributes);
        $count = $this->count();
        $offset = $this->offset;
        $this->offset = 0;
        $result = $this->nth($count - 1);
        $this->offset = $offset;

        return $result;
    }

    /**
     * Returns the total elements that match the criteria.
     *
     * @param array|null $attributes
     *
     * @return integer
     * @deprecated in Craft 3.0. Use count() instead.
     */
    public function total($attributes = null)
    {
        Craft::$app->getDeprecator()->log('ElementQuery::total()', 'The total() function used to query for elements is now deprecated. Use count() instead.');
        $this->_setAttributes($attributes);

        return $this->count();
    }

    // Protected Methods
    // =========================================================================

    /**
     * This method is called at the beginning of preparing an element query for the query builder.
     *
     * The main Query object being prepared for the query builder is available via [[query]].
     *
     * The subselect’s Query object being prepared is available via [[subQuery]].
     *
     * The role of the subselect query is to apply conditions to the query and narrow the result set down to
     * just the elements that should actually be returned.
     *
     * The role of the main query is to join in any tables that should be included in the results, and select
     * all of the columns that should be included in the results.
     *
     * @return boolean Whether the query should be prepared and returned to the query builder.
     * If false, the query will be cancelled and no results will be returned.
     * @see prepare()
     * @see afterPrepare()
     */
    protected function beforePrepare()
    {
        $event = new Event();
        $this->trigger(self::EVENT_BEFORE_PREPARE, $event);

        return $event->isValid;
    }

    /**
     * This method is called at the end of preparing an element query for the query builder.
     *
     * It is called at the beginning of [[prepare()]], right after [[query]] and [[subQuery]] have been created.
     *
     * @return boolean Whether the query should be prepared and returned to the query builder.
     * If false, the query will be cancelled and no results will be returned.
     * @see prepare()
     * @see beforePrepare()
     */
    protected function afterPrepare()
    {
        $event = new Event();
        $this->trigger(self::EVENT_AFTER_PREPARE, $event);

        return $event->isValid;
    }

    /**
     * Joins in a table with an `id` column that has a foreign key pointing to `craft_elements`.`id`.
     *
     * @param string $table The unprefixed table name. This will also be used as the table’s alias within the query.
     */
    protected function joinElementTable($table)
    {
        $joinTable = '{{%'.$table.'}} '.$table;
        $this->query->innerJoin($joinTable, $table.'.id = subquery.elementsId');
        $this->subQuery->innerJoin($joinTable, $table.'.id = elements.id');
    }

    // Private Methods
    // =========================================================================

    /**
     * Joins the content table into the query being prepared.
     *
     * @param Element $class
     *
     * @throws QueryAbortedException
     */
    private function _joinContentTable($class)
    {
        // Join in the content table on both queries
        $this->subQuery->innerJoin($this->contentTable.' content', 'content.elementId = elements.id');
        $this->subQuery->addSelect(['contentId' => 'content.id']);
        $this->subQuery->andWhere('content.siteId = :siteId');

        $this->query->innerJoin($this->contentTable.' content', 'content.id = subquery.contentId');

        // Select the content table columns on the main query
        $this->query->addSelect(['contentId' => 'content.id']);

        if ($class::hasTitles()) {
            $this->query->addSelect('content.title');
        }

        if (is_array($this->customFields)) {
            $contentService = Craft::$app->getContent();
            $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
            $fieldAttributes = $this->getBehavior('customFields');

            foreach ($this->customFields as $field) {
                /** @var Field $field */
                if ($field->hasContentColumn()) {
                    $this->query->addSelect('content.'.$this->_getFieldContentColumnName($field));
                }

                $handle = $field->handle;

                // In theory all field handles will be accounted for on the ElementQueryBehavior, but just to be safe...
                if (isset($fieldAttributes->$handle)) {
                    $fieldAttributeValue = $fieldAttributes->$handle;
                } else {
                    $fieldAttributeValue = null;
                }

                // Set the field's column prefix on the Content service.
                if ($field->columnPrefix) {
                    $contentService->fieldColumnPrefix = $field->columnPrefix;
                }

                $fieldResponse = $field->modifyElementsQuery($this, $fieldAttributeValue);

                // Set it back
                $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

                // Need to bail early?
                if ($fieldResponse === false) {
                    throw new QueryAbortedException();
                }
            }
        }
    }

    /**
     * Applies the 'status' param to the query being prepared.
     *
     * @param Element $class
     *
     * @throws QueryAbortedException
     */
    private function _applyStatusParam($class)
    {
        if ($this->status && $class::hasStatuses()) {
            $statusConditions = [];
            $statuses = ArrayHelper::toArray($this->status);

            foreach ($statuses as $status) {
                $status = StringHelper::toLowerCase($status);

                // Is this a supported status?
                if (in_array($status, array_keys($class::getStatuses()))) {
                    if ($status == Element::STATUS_ENABLED) {
                        $statusConditions[] = 'elements.enabled = 1';
                    } else if ($status == Element::STATUS_DISABLED) {
                        $statusConditions[] = 'elements.enabled = 0';
                    } else {
                        $elementStatusCondition = $class::getElementQueryStatusCondition($this, $status);

                        if ($elementStatusCondition) {
                            $statusConditions[] = $elementStatusCondition;
                        } else if ($elementStatusCondition === false) {
                            throw new QueryAbortedException();
                        }
                    }
                }
            }

            if ($statusConditions) {
                if (count($statusConditions) == 1) {
                    $statusCondition = $statusConditions[0];
                } else {
                    array_unshift($statusConditions, 'or');
                    $statusCondition = $statusConditions;
                }

                $this->subQuery->andWhere($statusCondition);
            }
        }
    }

    /**
     * Applies the 'relatedTo' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyRelatedToParam()
    {
        // Convert the old childOf and parentOf params to the relatedTo param
        // childOf(element)  => relatedTo({ source: element })
        // parentOf(element) => relatedTo({ target: element })

        // TODO: Remove this code in Craft 4
        /** @noinspection PhpDeprecationInspection */
        if (!$this->relatedTo && ($this->childOf || $this->parentOf)) {
            $this->relatedTo = ['and'];

            /** @noinspection PhpDeprecationInspection */
            if ($this->childOf) {
                /** @noinspection PhpDeprecationInspection */
                $this->relatedTo[] = [
                    'sourceElement' => $this->childOf,
                    'field' => $this->childField
                ];
            }

            /** @noinspection PhpDeprecationInspection */
            if ($this->parentOf) {
                /** @noinspection PhpDeprecationInspection */
                $this->relatedTo[] = [
                    'targetElement' => $this->parentOf,
                    'field' => $this->parentField
                ];
            }

            Craft::$app->getDeprecator()->log('element_old_relation_params', 'The ‘childOf’, ‘childField’, ‘parentOf’, and ‘parentField’ element params have been deprecated. Use ‘relatedTo’ instead.');
        }

        if ($this->relatedTo) {
            $relationParamParser = new ElementRelationParamParser();
            $relConditions = $relationParamParser->parseRelationParam($this->relatedTo, $this->subQuery);

            if ($relConditions === false) {
                throw new QueryAbortedException();
            }

            $this->subQuery->andWhere($relConditions);

            // If there's only one relation criteria and it's specifically for grabbing target elements, allow the query
            // to order by the relation sort order
            if ($relationParamParser->getIsRelationFieldQuery()) {
                $this->subQuery->addSelect('sources1.sortOrder');
            }
        }
    }

    /**
     * Applies the structure params to the query being prepared.
     *
     * @param Element $class
     *
     * @throws QueryAbortedException
     */
    private function _applyStructureParams($class)
    {
        if ($this->structureId) {
            $this->query
                ->addSelect([
                    'structureelements.root',
                    'structureelements.lft',
                    'structureelements.rgt',
                    'structureelements.level',
                ])
                ->innerJoin('{{%structureelements}} structureelements', 'structureelements.elementId = subquery.elementsId');

            $this->subQuery
                ->innerJoin('{{%structureelements}} structureelements', 'structureelements.elementId = elements.id')
                ->andWhere(['structureelements.structureId' => $this->structureId]);

            if ($this->ancestorOf !== null) {
                $this->_normalizeStructureParamValue('ancestorOf', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.lft < :ancestorOf_lft',
                        'structureelements.rgt > :ancestorOf_rgt',
                        'structureelements.root = :ancestorOf_root'
                    ])
                    ->addParams([
                        ':ancestorOf_lft' => $this->ancestorOf->lft,
                        ':ancestorOf_rgt' => $this->ancestorOf->rgt,
                        ':ancestorOf_root' => $this->ancestorOf->root
                    ]);

                if ($this->ancestorDist) {
                    $this->subQuery
                        ->andWhere('structureelements.level >= :ancestorOf_level')
                        ->addParams([':ancestorOf_level' => $this->ancestorOf->level - $this->ancestorDist]);
                }
            }

            if ($this->descendantOf !== null) {
                $this->_normalizeStructureParamValue('descendantOf', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.lft > :descendantOf_lft',
                        'structureelements.rgt < :descendantOf_rgt',
                        'structureelements.root = :descendantOf_root'
                    ])
                    ->addParams([
                        ':descendantOf_lft' => $this->descendantOf->lft,
                        ':descendantOf_rgt' => $this->descendantOf->rgt,
                        ':descendantOf_root' => $this->descendantOf->root
                    ]);

                if ($this->descendantDist) {
                    $this->subQuery
                        ->andWhere('structureelements.level <= :descendantOf_level')
                        ->addParams([':descendantOf_level' => $this->descendantOf->level + $this->descendantDist]);
                }
            }

            if ($this->siblingOf !== null) {
                $this->_normalizeStructureParamValue('siblingOf', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.level = :siblingOf_level',
                        'structureelements.root = :siblingOf_root',
                        'structureelements.elementId != :siblingOf_elementId'
                    ])
                    ->addParams([
                        ':siblingOf_level' => $this->siblingOf->level,
                        ':siblingOf_root' => $this->siblingOf->root,
                        ':siblingOf_elementId' => $this->siblingOf->id
                    ]);

                if ($this->siblingOf->level != 1) {
                    /** @var Element $parent */
                    $parent = $this->siblingOf->getParent();

                    if (!$parent) {
                        throw new QueryAbortedException();
                    }

                    $this->subQuery
                        ->andWhere([
                            'and',
                            'structureelements.lft > :siblingOf_lft',
                            'structureelements.rgt < :siblingOf_rgt'
                        ])
                        ->addParams([
                            ':siblingOf_lft' => $parent->lft,
                            ':siblingOf_rgt' => $parent->rgt
                        ]);
                }
            }

            if ($this->prevSiblingOf !== null) {
                $this->_normalizeStructureParamValue('prevSiblingOf', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.level = :prevSiblingOf_level',
                        'structureelements.rgt = :prevSiblingOf_rgt',
                        'structureelements.root = :prevSiblingOf_root'
                    ])
                    ->addParams([
                        ':prevSiblingOf_level' => $this->prevSiblingOf->level,
                        ':prevSiblingOf_rgt' => $this->prevSiblingOf->lft - 1,
                        ':prevSiblingOf_root' => $this->prevSiblingOf->root
                    ]);
            }

            if ($this->nextSiblingOf !== null) {
                $this->_normalizeStructureParamValue('nextSiblingOf', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.level = :nextSiblingOf_level',
                        'structureelements.lft = :nextSiblingOf_lft',
                        'structureelements.root = :nextSiblingOf_root'
                    ])
                    ->addParams([
                        ':nextSiblingOf_level' => $this->nextSiblingOf->level,
                        ':nextSiblingOf_lft' => $this->nextSiblingOf->rgt + 1,
                        ':nextSiblingOf_root' => $this->nextSiblingOf->root
                    ]);
            }

            if ($this->positionedBefore !== null) {
                $this->_normalizeStructureParamValue('positionedBefore', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.rgt < :positionedBefore_rgt',
                        'structureelements.root = :positionedBefore_root'
                    ])
                    ->addParams([
                        ':positionedBefore_rgt' => $this->positionedBefore->lft,
                        ':positionedBefore_root' => $this->positionedBefore->root
                    ]);
            }

            if ($this->positionedAfter !== null) {
                $this->_normalizeStructureParamValue('positionedAfter', $class);

                $this->subQuery
                    ->andWhere([
                        'and',
                        'structureelements.lft > :positionedAfter_lft',
                        'structureelements.root = :positionedAfter_root'
                    ])
                    ->addParams([
                        ':positionedAfter_lft' => $this->positionedAfter->rgt,
                        ':positionedAfter_root' => $this->positionedAfter->root
                    ]);
            }

            // TODO: Remove this code in Craft 4
            /** @noinspection PhpDeprecationInspection */
            if (!$this->level && $this->depth) {
                /** @noinspection PhpDeprecationInspection */
                $this->level = $this->depth;
                /** @noinspection PhpDeprecationInspection */
                $this->depth = null;
                Craft::$app->getDeprecator()->log('element_depth_param', 'The ‘depth’ element param has been deprecated. Use ‘level’ instead.');
            }

            if ($this->level) {
                $this->subQuery->andWhere(Db::parseParam('structureelements.level', $this->level, $this->subQuery->params));
            }
        }
    }

    /**
     * Normalizes a structure param value to either an Element object or false.
     *
     * @param string $property The parameter’s property name.
     * @param string $class    The element class
     *
     * @throws QueryAbortedException if the element can't be found
     */
    private function _normalizeStructureParamValue($property, $class)
    {
        /** @var Element $class */
        if ($this->$property !== false && !$this->$property instanceof ElementInterface) {
            $this->$property = $class::find()
                ->id($this->$property)
                ->siteId($this->siteId)
                ->one();

            if ($this->$property === null) {
                $this->$property = false;
            }
        }

        if ($this->$property === false) {
            throw new QueryAbortedException();
        }
    }

    /**
     * Applies the 'search' param to the query being prepared.
     *
     * @param Connection $db
     *
     * @throws QueryAbortedException
     */
    private function _applySearchParam($db)
    {
        $this->_searchScores = null;

        if ($this->search) {
            // Get the element IDs
            $limit = $this->query->limit;
            $offset = $this->query->offset;
            $subLimit = $this->subQuery->limit;
            $subOffset = $this->subQuery->offset;

            $this->query->limit = null;
            $this->query->offset = null;
            $this->subQuery->limit = null;
            $this->subQuery->offset = null;

            $elementIds = $this->query->column('elements.id');
            $searchResults = Craft::$app->getSearch()->filterElementIdsByQuery($elementIds, $this->search, true, $this->siteId, true);

            $this->query->limit = $limit;
            $this->query->offset = $offset;
            $this->subQuery->limit = $subLimit;
            $this->subQuery->offset = $subOffset;

            // No results?
            if (!$searchResults) {
                throw new QueryAbortedException();
            }

            $filteredElementIds = array_keys($searchResults);

            if ($this->orderBy === ['score' => SORT_ASC]) {
                // Order the elements in the exact order that the Search service returned them in
                $orderBy = [
                    new FixedOrderExpression('elements.id', $filteredElementIds, $db)
                ];

                $this->query->orderBy($orderBy);
                $this->subQuery->orderBy($orderBy);
            }

            $this->subQuery->andWhere(['in', 'elements.id', $filteredElementIds]);

            $this->_searchScores = $searchResults;
        }
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @param Connection $db
     *
     * @throws QueryAbortedException
     */
    private function _applyOrderByParams($db)
    {
        if ($this->orderBy === false) {
            return;
        }

        if ($this->orderBy === null) {
            if ($this->fixedOrder) {
                $ids = ArrayHelper::toArray($this->id);

                if (!$ids) {
                    throw new QueryAbortedException;
                }

                $this->orderBy = [new FixedOrderExpression('elements.id', $ids, $db)];
            } else if ($this->structureId) {
                $this->orderBy = 'structureelements.lft';
            } else {
                $this->orderBy = 'elements.dateCreated desc';
            }
        }

        if (!empty($this->orderBy) && $this->orderBy !== ['score' => SORT_ASC] && empty($this->query->orderBy)) {
            // In case $this->orderBy was set directly instead of via orderBy()
            $orderBy = $this->normalizeOrderBy($this->orderBy);
            $orderByColumns = array_keys($orderBy);

            $orderColumnMap = [];

            if (is_array($this->customFields)) {
                // Add the field column prefixes
                foreach ($this->customFields as $field) {
                    if ($field::hasContentColumn()) {
                        $orderColumnMap[$field->handle] = 'content.'.$this->_getFieldContentColumnName($field);
                    }
                }
            }

            // Prevent “1052 Column 'id' in order clause is ambiguous” MySQL error
            $orderColumnMap['id'] = 'elements.id';

            foreach ($orderColumnMap as $orderValue => $columnName) {
                // Are we ordering by this column name?
                $pos = array_search($orderValue, $orderByColumns);

                if ($pos !== false) {
                    // Swap it with the mapped column name
                    $orderByColumns[$pos] = $columnName;
                    $orderBy = array_combine($orderByColumns, $orderBy);
                }
            }
        }

        if (!empty($orderBy)) {
            $this->query->orderBy($orderBy);
            $this->subQuery->orderBy($orderBy);
        }
    }

    /**
     * Returns a field’s corresponding content column name.
     *
     * @param FieldInterface $field
     *
     * @return string
     */
    private function _getFieldContentColumnName(FieldInterface $field)
    {
        /** @var Field $field */
        return ($field->columnPrefix ?: 'field_').$field->handle;
    }

    /**
     * Converts found rows into element instances
     *
     * @param array $rows
     *
     * @return array|Element[]
     */
    private function _createElements($rows)
    {
        $elements = [];

        if ($this->asArray) {
            if ($this->indexBy === null) {
                return $rows;
            }

            foreach ($rows as $row) {
                if (is_string($this->indexBy)) {
                    $key = $row[$this->indexBy];
                } else {
                    $key = call_user_func($this->indexBy, $row);
                }

                $elements[$key] = $row;
            }
        } else {
            /** @var Element $lastElement */
            $lastElement = null;

            foreach ($rows as $row) {

                $element = $this->_createElement($row);

                if ($element === false) {
                    continue;
                }

                // Add it to the elements array
                if ($this->indexBy === null) {
                    $elements[] = $element;
                } else {
                    if (is_string($this->indexBy)) {
                        $key = $element->{$this->indexBy};
                    } else {
                        $key = call_user_func($this->indexBy, $element);
                    }

                    $elements[$key] = $element;
                }

                // setNext() / setPrev()
                if ($lastElement) {
                    $lastElement->setNext($element);
                    $element->setPrev($lastElement);
                } else {
                    $element->setPrev(false);
                }

                $lastElement = $element;
            }

            $lastElement->setNext(false);

            // Should we eager-load some elements onto these?
            if ($this->with) {
                Craft::$app->getElements()->eagerLoadElements($this->elementType, $elements, $this->with);
            }
        }

        return $elements;
    }

    /**
     * Converts a found row into an element instance.
     *
     * @param array $row
     *
     * @return ElementInterface|boolean
     */
    private function _createElement($row)
    {
        // Do we have a placeholder for this element?
        $element = Craft::$app->getElements()->getPlaceholderElement($row['id'], $this->siteId);

        if ($element !== null) {
            return $element;
        }

        /** @var Element $class */
        $class = $this->elementType;

        // Instantiate the element
        $row['siteId'] = $this->siteId;

        if ($this->structureId) {
            $row['structureId'] = $this->structureId;
        }

        /** @var Element $element */
        $element = $class::create($row);

        // Verify that an element was returned
        if (!$element || !($element instanceof ElementInterface)) {
            return false;
        }

        // Set the custom field values
        if ($class::hasContent() && $this->contentTable) {
            // Separate the content values from the main element attributes
            $fieldValues = [];

            if ($this->customFields) {
                foreach ($this->customFields as $field) {
                    /** @var Field $field */
                    if ($field->hasContentColumn()) {
                        // Account for results where multiple fields have the same handle, but from
                        // different columns e.g. two Matrix block types that each have a field with the
                        // same handle
                        $colName = $this->_getFieldContentColumnName($field);

                        if (!isset($fieldValues[$field->handle]) || (empty($fieldValues[$field->handle]) && !empty($row[$colName]))) {
                            $fieldValues[$field->handle] = $row[$colName];
                        }
                    }
                }
            }

            $element->setFieldValues($fieldValues);
        }

        // Fire an 'afterPopulateElement' event
        $this->trigger(self::EVENT_AFTER_POPULATE_ELEMENT, new PopulateElementEvent([
            'element' => $element,
            'row' => $row
        ]));

        return $element;
    }

    /**
     * Batch-sets attributes. Used by [[find()]], [[first()]], [[last()]], [[ids()]], and [[total()]].
     *
     * @param mixed $attributes
     *
     * @return boolean Whether $attributes was an array
     * @todo Remvoe this in Craft 4, along with the methods that call it.
     */
    private function _setAttributes($attributes)
    {
        if (is_array($attributes) || $attributes instanceof \IteratorAggregate) {
            foreach ($attributes as $name => $value) {
                if ($this->canSetProperty($name)) {
                    $this->$name = $value;
                }
            }

            return true;
        }

        return false;
    }
}
