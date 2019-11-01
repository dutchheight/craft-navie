<?php

namespace dutchheight\navie\craftql\types;

use dutchheight\navie\craftql\types\ItemInterface;
use markhuot\CraftQL\Builders\Schema;

class ListItemType extends Schema {

    protected $interfaces = [
        ItemInterface::class,
    ];

    function boot()
    {
        $this->addFieldsByLayoutId($this->context->fieldLayoutId);
    }

    function getName(): string
    {
        return ucfirst($this->context->handle) . 'List';
    }

}
