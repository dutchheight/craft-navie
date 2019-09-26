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

use yii\base\Event;

class RegisterLinkTypesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of Link Types
     */
    public $types = [];
}
