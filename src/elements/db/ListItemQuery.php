<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\elements\db;

use dutchheight\navie\records\ListRecord;
use dutchheight\navie\records\ListItemRecord;
use dutchheight\navie\models\ListModel;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class ListItemQuery extends ElementQuery
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null List ID
     */
    public $listId;

    /**
     * @var int|null Element ID
     */
    public $elementId;

    /**
     * @var bool|null Enabled
     */
    public $enabled = true;

    /**
     * @var string Type
     */
    public $type;

    /**
     * @var string Url
     */
    public $url;

    /**
     * @var string HTML target attribute
     */
    public $target;

    /**
     * @var string List Handle
     */
    public $listHandle;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->withStructure === null) {
            $this->withStructure = true;
        }
        parent::init();
    }


    /**
     * Narrows the query results based on the lists the listitem belong to.
     *
     * Possible values include:
     *
     * | Value | Fetches {elements}…
     * | - | -
     * | `'foo'` | in a list with a handle of `foo`.
     * | `'not foo'` | not in a list with a handle of `foo`.
     * | `['foo', 'bar']` | in a list with a handle of `foo` or `bar`.
     * | `['not', 'foo', 'bar']` | not in a list with a handle of `foo` or `bar`.
     * | a [[ListModel|ListModel]] object | in a list represented by the object.
     *
     * @param string|string[]|ListModel|null $value The property value
     * @return static self reference
     * @uses $listId
     */
    public function list($value)
    {
        if ($value instanceof ListModel) {
            $this->structureId = ($value->structureId ?: false);
            $this->listId = $value->id;
        } else if ($value !== null) {
            $this->listId = (new Query())
                ->select(['id'])
                ->from(ListRecord::tableName())
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->listId = null;
        }

        return $this;
    }

    /**
     * Sets the list id property
     *
     * @param integer $listId
     * @return ListItemQuery
     */
    public function listId(int $listId): ListItemQuery
    {
        $this->listId = $listId;
        return $this;
    }

    /**
     * Sets the list handle property
     *
     * @param string $listHandle
     * @return ListItemQuery
     */
    public function listHandle(string $listHandle): ListItemQuery
    {
        $this->listHandle = $listHandle;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $table = ListItemRecord::tableName();
        $this->joinElementTable('navie_listitems');

        $this->query->select([
            $table . '.id',
            $table . '.listId',
            $table . '.elementId',
            $table . '.type',
            $table . '.url',
            $table . '.target',
        ]);

        $this->_applyListIdParam();
        $this->_applyListHandleParam();

        return parent::beforePrepare();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Applies the 'listId' param to the query being prepared
     */
    private function _applyListIdParam()
    {
        if ($this->listId) {
            $this->subQuery->andWhere(Db::parseParam('navie_listitems.listId', $this->listId));
        }
    }

    /**
     * Applies the 'handle' param to the query being prepared
     */
    private function _applyListHandleParam()
    {
        if ($this->listHandle) {
            $this->subQuery->innerJoin('{{%navie_lists}} lists', '[[lists.id]] = [[navie_listitems.listId]]');
            $this->subQuery->andWhere(Db::parseParam('lists.handle', $this->listHandle));
        }
    }
}
