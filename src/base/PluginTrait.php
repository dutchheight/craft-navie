<?php

/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\base;

use dutchheight\navie\Navie;
use dutchheight\navie\services\ListService;

use Craft;

use yii\log\Logger;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
trait PluginTrait
{
    // Public Properties
    // =========================================================================

    /**
     * @var Navie
     */
    public static $plugin;

    /**
     * @var Settings
     */
    public static $settings;

    // Public Methods
    // =========================================================================

    /**
     * Returns the list services
     *
     * @return ListService
     */
    public function getLists(): ListService
    {
        return $this->get('lists');
    }

    public static function info($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'navie');
    }

    public static function warning($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_WARNING, 'navie');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'navie');
    }

    // Protected Methods
    // =========================================================================

    protected function registerComponents()
    {
        $this->setComponents([
            'lists' => ListService::class
        ]);
    }
}
