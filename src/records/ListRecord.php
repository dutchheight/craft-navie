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

use dutchheight\navie\Navie;

use Craft;
use craft\db\ActiveRecord;
use craft\records\Structure;
use craft\records\FieldLayout;

use yii\db\ActiveQueryInterface;

/**
 * @property int $id Record ID
 * @property int $structureId Structure ID
 * @property int $fieldLayoutId Field layout ID
 * @property int $maxLevel Max Level of structure
 * @property string $name Name
 * @property string $handle Handle
 * @property string $propagate Propagate
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class ListRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%navie_lists}}';
    }

    /**
     * Returns the list’s structure.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getStructure(): ActiveQueryInterface
    {
        return $this->hasOne(Structure::class, ['id' => 'structureId']);
    }

    /**
     * Returns the list’s fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout() : ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the list's items
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getListItems(): ActiveQueryInterface
    {
        return $this->hasMany(ListItemRecord::class, ['listId' => 'id']);
    }
}
