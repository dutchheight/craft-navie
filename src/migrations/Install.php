<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\migrations;

use dutchheight\navie\Navie;
use dutchheight\navie\records\ListRecord;
use dutchheight\navie\records\ListItemRecord;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\db\Table;
use craft\models\FieldGroup;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();
        $this->removeDefaultData();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema(ListRecord::tableName());
        if ($tableSchema === null) {
            $tablesCreated = true;

            $this->createTable(ListRecord::tableName(), [
                    'id' => $this->primaryKey(),
                    'structureId' => $this->integer()->notNull(),
                    'fieldLayoutId' => $this->integer(),
                    'name' => $this->string()->notNull(),
                    'handle' => $this->string()->notNull(),
                    'maxLevels' => $this->tinyInteger()->unsigned(),
                    'propagate' => $this->boolean()->defaultValue(true)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );

            $this->createTable(ListItemRecord::tableName(), [
                    'id' => $this->primaryKey(),
                    'listId' => $this->integer()->notNull(),
                    'elementId'=> $this->integer()->null(),
                    'type' => $this->string()->null(),
                    'url' => $this->string()->null(),
                    'target' => $this->string(15)->null(),

                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid()
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, ListRecord::tableName(), 'handle', true);
        $this->createIndex(null, ListRecord::tableName(), ['structureId', 'fieldLayoutId'], false);

        $this->createIndex(null, ListItemRecord::tableName(), 'listId', false);
        $this->createIndex(null, ListItemRecord::tableName(), 'elementId', false);
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, ListRecord::tableName(), 'fieldLayoutId', Table::FIELDLAYOUTS, 'id', 'SET NULL', null);
        $this->addForeignKey(null, ListRecord::tableName(), 'structureId', Table::STRUCTURES, 'id', 'CASCADE', null);

        $this->addForeignKey(null, ListItemRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE', null);
        $this->addForeignKey(null, ListItemRecord::tableName(), 'listId', ListRecord::tableName(), 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, ListItemRecord::tableName(), 'elementId', Table::ELEMENTS, 'id', 'SET NULL', null);
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists(ListItemRecord::tableName());
        $this->dropTableIfExists(ListRecord::tableName());
    }
}
