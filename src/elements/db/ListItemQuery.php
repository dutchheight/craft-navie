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

use dutchheight\navie\records\ListItemRecord;

use Craft;
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
     * @var int|null Site ID
     */
    public $siteId;

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
            $table . '.siteId',
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
