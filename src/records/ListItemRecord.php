<?php

/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\records;

use Craft;
use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

/**
 * @property int $id Record ID
 * @property int $listId List ID
 * @property string $type Type
 * @property string $url Url
 * @property string $target Target
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class ListItemRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%navie_listitems}}';
    }

    /**
     * Returns the list item's element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement() : ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the list item's list
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getList(): ActiveQueryInterface
    {
        return $this->hasOne(ListRecord::class, ['id' => 'listId']);
    }
}
