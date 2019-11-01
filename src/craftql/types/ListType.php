<?php

namespace dutchheight\navie\craftql\types;

use dutchheight\navie\records\ListRecord;
use markhuot\CraftQL\Builders\Schema;

class ListType extends Schema
{
    function boot()
    {
        $this->addIntField('id');
        $this->addIntField('structureId');
        $this->addIntField('fieldLayoutId');
        $this->addIntField('maxLevels');

        $this->addStringField('name');
        $this->addStringField('handle');
        $this->addStringField('uid');

        $this->addBooleanField('propagate');

        $this->addDateField('dateCreated');
        $this->addDateField('dateUpdated');
    }

    static function criteriaResolver($root, $args, $context, $info, $criteria = null, $asArray = true)
    {
        $criteria = $criteria ?: ListRecord::find();

        foreach ($args as $key => $value) {
            $criteria = $criteria->where([$key => $value]);
        }

        return $asArray ? $criteria->all() : $criteria;
    }
}
