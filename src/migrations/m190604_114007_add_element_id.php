<?php

namespace dutchheight\navie\migrations;

use dutchheight\navie\records\ListItemRecord;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190604_114007_add_element_id migration.
 */
class m190604_114007_add_element_id extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createColumns();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    public function createColumns()
    {
        if (!$this->db->columnExists(ListItemRecord::tableName(), 'elementId')) {
            $this->addColumn(ListItemRecord::tableName(), 'elementId', $this->integer()->null()->after('listId'));
        }
    }

    public function createIndexes()
    {
        $this->createIndex(null, ListItemRecord::tableName(), 'elementId', false);
    }

    public function addForeignKeys()
    {
        $this->addForeignKey(null, ListItemRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, ListItemRecord::tableName(), 'elementId', Table::ELEMENTS, 'id', 'SET NULL', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190604_114007_add_element_id cannot be reverted.\n";
        return false;
    }
}
