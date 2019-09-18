<?php

namespace dutchheight\navie\migrations;

use dutchheight\navie\records\ListItemRecord;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m190912_114133_remove_site_id_from_listitems migration.
 */
class m190912_114133_remove_site_id_from_listitems extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(ListItemRecord::tableName(), 'siteId')) {
            MigrationHelper::dropForeignKeyIfExists(ListItemRecord::tableName(), ['siteId'], $this);
            MigrationHelper::dropIndexIfExists(ListItemRecord::tableName(), ['siteId'], true, $this);

            $this->dropColumn(ListItemRecord::tableName(), 'siteId');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190912_114133_remove_site_id_from_listitems cannot be reverted.\n";
        return false;
    }
}
