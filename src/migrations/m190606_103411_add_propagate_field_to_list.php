<?php

namespace dutchheight\navie\migrations;

use dutchheight\navie\records\ListRecord;

use Craft;
use craft\db\Migration;

/**
 * m190606_103411_add_propagate_field_to_list migration.
 */
class m190606_103411_add_propagate_field_to_list extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createColumns();
    }

    public function createColumns()
    {
        if (!$this->db->columnExists(ListRecord::tableName(), 'propagate')) {
            $this->addColumn(ListRecord::tableName(), 'propagate', $this->boolean()->defaultValue(true)->notNull()->after('maxLevels'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190606_103411_add_propagate_field_to_list cannot be reverted.\n";
        return false;
    }
}
