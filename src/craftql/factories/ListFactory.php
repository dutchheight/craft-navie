<?php

namespace dutchheight\navie\craftql\factories;

use dutchheight\navie\craftql\types\ListItemType;
use markhuot\CraftQL\Factories\BaseFactory;

class ListFactory extends BaseFactory
{
    function make($raw, $request)
    {
        return new ListItemType($request, $raw);
    }

    function can($id, $mode = 'query')
    {
        return true;
    }
}