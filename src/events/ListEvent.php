<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\events;

use dutchheight\navie\models\ListModel;
use yii\base\Event;

class ListEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var dutchheight\navie\models\ListModel|null The list model associated with the event.
     */
    public $list;

    /**
     * @var bool Whether the list is brand new
     */
    public $isNew = false;
}
